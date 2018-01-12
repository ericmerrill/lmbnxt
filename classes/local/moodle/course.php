<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * An object for converting data to moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\moodle;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\logging;
use enrol_lmb\settings;
use enrol_lmb\local\data;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Abstract object for converting a data object to a Moodle course.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends base {
    protected $record = null;

    /**
     * This function takes a data object and attempts to apply it to Moodle.
     *
     * @param data\base $data A data object to process.
     */
    public function convert_to_moodle(data\base $data) {
        global $DB;

        $this->data = $data;

        $sdid = $data->sdid;

        // First see if we are going to be working with an existing or new course.
        $new = false;
        $course = $this->find_existing_course();
        if (empty($course)) {
            $new = true;
            $course = $this->create_new_course_object();
        }

        // Set the titles if new or forcing.
        if ($new || (bool)$this->settings->get('forcetitle')) {
            $course->fullname = $this->create_course_title($this->settings->get('coursetitle'));
        }
        if ($new || (bool)$this->settings->get('forceshorttitle')) {
            $course->shortname = $this->create_course_title($this->settings->get('courseshorttitle'));
        }

        // We always update dates.
        if (empty($this->data->begindate)) {
            if (!isset($course->startdate)) {
                $course->startdate = 0;
            }
        } else {
            $course->startdate = $this->data->begindate;
        }
        // A course can only have an end date if it has a start date.
        if (empty($course->startdate)) {
            $course->enddate = 0;
        } else {
            if (empty($this->data->enddate)) {
                if (!isset($course->enddate)) {
                    $course->enddate = 0;
                }
            } else {
                $course->enddate = $this->data->enddate;
            }
        }

        // Here we update the number of sections.
        if ($new || $this->settings->get('forcecomputesections')) {
            $newsectioncount = $this->calculate_section_count();
            if ($newsectioncount !== false) {
                $course->numsections = $newsectioncount;
            }
        }

        // Here we set the category
        if ($new || $this->settings->get('forcecat')) {
            // TODO Category finder.
            $course->category = $this->get_category_id();
        }

        try {
            if ($new) {
                logging::instance()->log_line('Creating new Moodle course');
                $course = create_course($course);

            } else {
                logging::instance()->log_line('Updating Moodle course');
                update_course($course);
            }
            // Update the count of sections.
            // We can just use the presense of numsections to tell us if we need to do this or not.
            if (!empty($course->numsections)) {
                $sectioncount = $DB->count_records('course_sections', array('course' => $course->id));
                // Remove 1 to account for the general section.
                $sectioncount -= 1;

                if ($course->numsections > $sectioncount) {
                    course_create_sections_if_missing($course->id, range(0, $course->numsections));
                }
            }
        } catch (\moodle_exception $e) {
            // TODO - catch exception and pass back up to message.
            $error = 'Fatal exception while inserting/updating course. '.$e->getMessage();
            logging::instance()->log_line($error, logging::ERROR_MAJOR);
            throw $e;
        }
    }

    protected function find_existing_course() {
        global $DB;

        return $DB->get_record('course', array('idnumber' => $this->data->sdid));
    }

    protected function create_new_course_object() {
        // Get the standard default course settings.
        $course = get_config('moodlecourse');

        $course->idnumber = $this->data->sdid;

        $course->visible = $this->calculate_visibility();

        return $course;
    }

    /**
     * Calculates the number of sections (or weeks) that are in a course based on the start and end dates.
     *
     * @return false|int The number of sections, or false if we can't determine it. Use existing or default.
     */
    protected function calculate_section_count() {
        if (!$this->settings->get('computesections')) {
            return false;
        }

        if (empty($begindate = $this->data->begindate) || empty($enddate = $this->data->enddate)) {
            // Can't compute if we don't have both dates.
            return false;
        }

        $maxsections = get_config('moodlecourse', 'maxsections');

        // Take the difference, convert it to frational weeks, then take the ceiling of that.
        $length = $enddate - $begindate;
        $length = ceil(($length/(24*3600)/7));

        // No less than 1, and no more than maxsections.
        if ($length < 1) {
            return false;
        } else if ($length > $maxsections) {
            return $maxsections;
        }

        return $length;
    }

    /**
     * Calculates the visibility setting for this new course.
     *
     * @return int 0 if the course is hidden, 1 if it is visible.
     */
    protected function calculate_visibility() {
        $visible = 1;

        switch ($this->settings->get('coursehidden')) {
            case (settings::CREATE_COURSE_HIDDEN):
                $visible = 0;
                break;
            case (settings::CREATE_COURSE_CRON):
                // Get the time at the start of today (this is day based).
                $curtime = time();
                $todaytime = mktime(0, 0, 0, date('n', $curtime), date('j', $curtime), date('Y', $curtime));
                $time = $todaytime + ($this->settings->get('cronunhidedays') * 86400);

                if ($this->data->begindate > $time) {
                    $visible = 0;
                } else {
                    $visible = 1;
                }
                break;
            case (settings::CREATE_COURSE_VISIBLE):
                $visible = 1;
                break;
        }

        return $visible;
    }

    protected function get_category_id() {
        // TODO.
        return 1;
    }

    /**
     * Does subsitution to create a course title based on the passed spec.
     *
     * Substitutions are:
     *   [SOURCEDID] - Same as [CRN].[TERM]
     *   [CRN] - The course/section number
     *   [TERM] - The term code
     *   [TERMNAME] - The full name of the term
     *   [LONG] - The same as [DEPT]-[NUM]-[SECTION]
     *   [FULL] - The full title of the course
     *   [RUBRIC] - The same as [DEPT]-[NUM]
     *   [DEPT] - The short department code
     *   [NUM] - The department code for the course
     *   [SECTION] - The section number of the course
     *
     * @param string $spec
     * @return string
     */
    protected function create_course_title($spec) {
        $title = str_replace('[SOURCEDID]', $this->data->sdid, $spec);
        $title = str_replace('[CRN]', $this->data->crn, $title);
        $title = str_replace('[TERM]', $this->data->termsdid, $title);
        $title = str_replace('[LONG]', $this->data->rubric, $title);
        $title = str_replace('[FULL]', $this->data->title, $title);
        $title = str_replace('[RUBRIC]', '[DEPT]-[NUM]', $title);
        $title = str_replace('[DEPT]', $this->data->deptsdid, $title);
        $title = str_replace('[NUM]', $this->data->coursenumber, $title);
        $title = str_replace('[SECTION]', $this->data->sectionnumber, $title);

        // Only do the heavy lifting if we really need it.
        if (strpos($title, '[TERMNAME]') !== false) {
            if ($term = data\term::get_term($this->data->termsdid)) {
                $termname = $term->description;
            } else {
                // Fall back just onto the term short code.
                $termname = $this->data->termsdid;
            }
            $title = str_replace('[TERMNAME]', $termname, $title);
        }

        return $title;
    }
}
