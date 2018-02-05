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
use enrol_lmb_plugin;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Abstract object for converting a data object to a Moodle course.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crosslist extends course {
    protected $record = null;

    protected $sections = null;

    protected $course = null;

    /**
     * This function takes a data object and attempts to apply it to Moodle.
     *
     * @param data\base $data A data object to process.
     */
    public function convert_to_moodle(data\base $data) {
        global $DB;

        if (!($data instanceof data\crosslist)) {
            throw new \coding_exception('Expected instance of data\section to be passed.');
        }

        $this->data = $data;
        $this->sections = $this->get_member_sections();

        $settings = $this->settings;

        // First see if we are going to be working with an existing or new course.
        $new = false;
        $course = $this->find_existing_course();
        if (empty($course)) {
            $new = true;
            $course = $this->create_new_course_object();
        }

        $course->fullname = $this->create_crosslist_title($settings->get('xlstitle'),
                $settings->get('xlstitlerepeat'), $settings->get('xlstitledivider'));
        $course->shortname = $this->create_crosslist_title($settings->get('xlsshorttitle'),
                $settings->get('xlsshorttitlerepeat'), $settings->get('xlsshorttitledivider'));

        // Set the titles if new or forcing.
//         if ($new || (bool)$this->settings->get('forcetitle')) {
//             $course->fullname = $this->create_course_title($this->settings->get('coursetitle'));
//         }
//         if ($new || (bool)$this->settings->get('forceshorttitle')) {
//             $course->shortname = $this->create_course_title($this->settings->get('courseshorttitle'));
//         }

        // We always update dates.

        $course->startdate = 0;
//         if (empty($this->data->begindate)) {
//             if (!isset($course->startdate)) {
//                 $course->startdate = 0;
//             }
//         } else {
//             $course->startdate = $this->data->begindate;
//         }
        // A course can only have an end date if it has a start date.
//         if (empty($course->startdate)) {
//             $course->enddate = 0;
//         } else {
//             if (empty($this->data->enddate)) {
//                 if (!isset($course->enddate)) {
//                     $course->enddate = 0;
//                 }
//             } else {
//                 $course->enddate = $this->data->enddate;
//             }
//         }

        // Here we update the number of sections.
//         if ($new || $this->settings->get('forcecomputesections')) {
//             $newsectioncount = $this->calculate_section_count();
//             if ($newsectioncount !== false) {
//                 $course->numsections = $newsectioncount;
//             }
//         }

        // Here we set the category
//         if ($new || $this->settings->get('forcecat')) {
//             // TODO Category finder.
//             $course->category = $this->get_category_id();
//         }

        $course->category = 1;

        // TODO - Recalculate visibility based on changes in start date.

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

        $this->course = $course;

        if ($this->data->type == data\crosslist::GROUP_TYPE_MERGE) {
            $this->process_merge_crosslist();
        } else if ($this->data->type == data\crosslist::GROUP_TYPE_META) {
            $this->process_meta_crosslist();
        }

    }

    protected function process_merge_crosslist() {
        $enrol = new enrol_lmb_plugin();

        // First add instances.
        foreach ($this->sections as $section) {
            $enrolinstance = $enrol->get_instance($this->course, $section->sdid, false);
            if (!$enrolinstance) {
                $enrolinstance = $enrol->create_instance($this->course, $section->sdid);
                // TODO - if new, enrol users, remove from child course.

                // Unenrol users from the child course.

            }

        }

        // Then remove any that shouldn't be there.
        $inactive = $this->get_member_sections(0);
        foreach ($inactive as $section) {
            $enrolinstance = $enrol->get_instance($this->course, $section->sdid, false);
            if ($enrolinstance) {
                // TODO - if found move users to child course.
            }
        }
    }

    protected function process_meta_crosslist() {

    }

    protected function add_enrol_instance_to_course($sdid) {

    }


    /**
     * Return an array of section data objects for the members of this crosslist.
     *
     * @param int $status The status to get records for.
     * @return data\section[]
     */
    protected function get_member_sections($status = 1) {
        $results = [];
        $members = $this->data->get_existing_members();
        foreach ($members as $member) {
            if (empty($member->status)) {
                continue;
            }
            $section = $member->get_section();
            if ($section) {
                $results[] = $section;
            }
        }

        return $results;
    }

    /**
     * If exists, find an existing course that matches this data object.
     *
     * @return false|\stdClass
     */
//     protected function find_existing_course() {
//     }

    /**
     * Returns a course record for the passed sdid.
     *
     * @param string $sdid
     * @return false|\stdClass
     */
//     public static function get_course_for_sdid($sdid) {
//     }

    /**
     * Create a new base course object.
     *
     * @return \stdClass
     */
//     protected function create_new_course_object() {
//     }

    /**
     * Calculates the number of sections (or weeks) that are in a course based on the start and end dates.
     *
     * @return false|int The number of sections, or false if we can't determine it. Use existing or default.
     */
//     protected function calculate_section_count() {
//         if (!$this->settings->get('computesections')) {
//             return false;
//         }
//
//         if (empty($begindate = $this->data->begindate) || empty($enddate = $this->data->enddate)) {
//             // Can't compute if we don't have both dates.
//             return false;
//         }
//
//         $maxsections = get_config('moodlecourse', 'maxsections');
//
//         // Take the difference, convert it to frational weeks, then take the ceiling of that.
//         $length = $enddate - $begindate;
//         $length = ceil(($length/(24*3600)/7));
//
//         // No less than 1, and no more than maxsections.
//         if ($length < 1) {
//             return false;
//         } else if ($length > $maxsections) {
//             return $maxsections;
//         }
//
//         return $length;
//     }

    /**
     * Calculates the visibility setting for this new course.
     *
     * @return int 0 if the course is hidden, 1 if it is visible.
     */
//     protected function calculate_visibility() {
//         $visible = 1;
//
//         switch ($this->settings->get('coursehidden')) {
//             case (settings::CREATE_COURSE_HIDDEN):
//                 $visible = 0;
//                 break;
//             case (settings::CREATE_COURSE_CRON):
//                 // Get the time at the start of today (this is day based).
//                 $curtime = time();
//                 $todaytime = mktime(0, 0, 0, date('n', $curtime), date('j', $curtime), date('Y', $curtime));
//                 $time = $todaytime + ($this->settings->get('cronunhidedays') * 86400);
//
//                 if ($this->data->begindate > $time) {
//                     $visible = 0;
//                 } else {
//                     $visible = 1;
//                 }
//                 break;
//             case (settings::CREATE_COURSE_VISIBLE):
//                 $visible = 1;
//                 break;
//         }
//
//         return $visible;
//     }

//     protected function get_category_id() {
//         return category::get_category_id($this->data);
//     }

    public function create_crosslist_title($titlespec, $repeattitle, $divider) {
        $section = reset($this->sections);
        $title = $this->create_course_title($titlespec, $section);

        if (strpos($titlespec, '[REPEAT]') !== false) {
            $titles = [];

            foreach ($this->sections as $section) {
                $titles[] = $this->create_course_title($repeattitle, $section);
            }
            $repeatedtitle = implode($divider, $titles);
            $title = str_replace('[REPEAT]', $repeatedtitle, $title);
        }

        $title = str_replace('[XLSID]', $this->data->sdid, $title);

        // Limited to 254 characters.
        return substr($title, 0, 254);
    }
}
