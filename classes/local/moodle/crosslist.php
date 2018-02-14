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

    protected $firstsection = null;

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
        if (!empty($this->sections)) {
            $this->firstsection = reset($this->sections);
        }

        $settings = $this->settings;

        // First see if we are going to be working with an existing or new course.
        $new = false;
        $course = $this->find_existing_course();
        if (empty($course)) {
            $new = true;
            $course = $this->create_new_course_object();
            if ($this->firstsection) {
                $course->visible = $this->calculate_visibility($this->firstsection->begindate);
            }
        }

        // We always force the title on crosslists, because they are subject to a lot of change.
        // TODO - Make a new setting.
        $course->fullname = $this->create_crosslist_title($settings->get('xlstitle'),
                $settings->get('xlstitlerepeat'), $settings->get('xlstitledivider'));
        $course->shortname = $this->create_crosslist_title($settings->get('xlsshorttitle'),
                $settings->get('xlsshorttitlerepeat'), $settings->get('xlsshorttitledivider'));


        // We always update dates.
        $course->startdate = $this->get_crosslist_start_date();

        // A course can only have an end date if it has a start date.
        if (empty($course->startdate)) {
            $course->enddate = 0;
        } else {
            $course->enddate = $this->get_crosslist_end_date();
        }

        // Here we update the number of sections.
        if ($new || $this->settings->get('forcecomputesections')) {
            $newsectioncount = $this->calculate_section_count($course->startdate, $course->enddate);
            if ($newsectioncount !== false) {
                $course->numsections = $newsectioncount;
            }
        }

        // Here we set the category
        if ($new || $this->settings->get('forcecat')) {
            // TODO Category finder.
            if (!empty($this->firstsection)) {
                $course->category = category::get_category_id($this->firstsection);
            } else {
                $course->category = 1;
            }
        }

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

    /**
     * Handles the processing of a merge crosslist - including adding or removing members.
     */
    protected function process_merge_crosslist() {
        $enrol = new enrol_lmb_plugin();

        $members = $this->data->get_existing_members();

        foreach ($members as $member) {
            if ((int)$member->status === (int)$member->moodlestatus) {
                // This has already been processed, so we can skip it.
                continue;
            }

            $section = $member->get_section();
            if (empty($section)) {
                continue;
            }
            if ((int)$member->status === 1) {
                // We are adding a new crosslist member.
                $xlsinstance = $enrol->get_instance($this->course, $section->sdid);
                $newstatus = 1;

                // Enrol all existing enrolments in the new course.
                if ($xlsinstance) {
                    logging::instance()->log_line("Adding existing enrolments to crosslist.");
                    logging::instance()->start_level();
                    course_enrolments::reprocess_enrolments_for_section_sdid($section->sdid);
                    logging::instance()->end_level();
                } else {
                    $newstatus = 0;
                    $error = "Could not find or create the enrol instance for the crosslist.";
                    logging::instance()->log_line($error, logging::ERROR_WARN);
                }

                // Remove all users from the child course.
                $sectioninstance = $enrol->get_instance_for_section_sdid($section->sdid);
                if ($sectioninstance) {
                    logging::instance()->log_line("Removing enrolments from child course.");
                    logging::instance()->start_level();
                    $enrol->unenrol_all_users($sectioninstance, false); // TODO - True...
                    logging::instance()->end_level();
                }

                $member->moodlestatus = $newstatus;

                $member->save_to_db();

            } else if ((int)$member->status === 0) {
                $newstatus = 0;
                $enrolinstance = $enrol->get_instance($this->course, $section->sdid, false);
                if ($enrolinstance) {
                    // Add users back to the child course.
                    logging::instance()->log_line("Adding existing enrolments to child course.");
                    logging::instance()->start_level();
                    course_enrolments::reprocess_enrolments_for_section_sdid($section->sdid);
                    logging::instance()->end_level();

                    // Remove all enrolments from the crosslist course.
                    logging::instance()->log_line("Removing enrolments from crosslist course.");
                    logging::instance()->start_level();
                    $enrol->unenrol_all_users($enrolinstance, false);
                    logging::instance()->end_level();
                } else {
                    $newstatus = 1;
                }

                $member->moodlestatus = $newstatus;
                $member->save_to_db();
            } else {
                $error = "Member {$member->sdid} has unknown status \"{$member->status}\"";
                logging::instance()->log_line($error, logging::ERROR_WARN);
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
            $section = $member->get_section();
            if ($section && $member->status == $status) {
                $results[] = $section;
            }
        }

        return $results;
    }

    /**
     * Determines the start time for a crosslisted course - the earliest start date
     * of any child course.
     *
     * @return int The start time in unit time format
     */
    public function get_crosslist_start_date() {
        $times = [];
        foreach ($this->sections as $section) {
            $begin = $section->begindate;
            if ($begin) {
                $times[] = $begin;
            }
        }

        if (empty($times)) {
            // We didn't get a start time, so just do now...
            return 0;
        }

        // Sort them smallest to biggest.
        sort($times);

        // Get the top one.
        return $times[0];
    }

    /**
     * Determines the end time for a crosslisted course - the latest end date
     * of any child course.
     *
     * @return int The end time in unit time format
     */
    public function get_crosslist_end_date() {
        $times = [];
        foreach ($this->sections as $section) {
            $end = $section->enddate;
            if ($end) {
                $times[] = $end;
            }
        }

        if (empty($times)) {
            // We didn't get a start time, so just do now...
            return 0;
        }

        // Sort them biggest to smallest.
        rsort($times);

        // Get the top one.
        return $times[0];
    }

    /**
     * Create a crosslist title based on the provided values.
     *
     * @param string $titlespec The title setting to use
     * @param string $repeatedtitle The repeated title setting
     * @param string $divider The seperator that goes between the repeats
     * @return string
     */
    public function create_crosslist_title($titlespec, $repeattitle, $divider) {
        $title = $titlespec;

        // If we have a first section, then use that for any direct bits in the name.
        if (!empty($this->firstsection)) {
            $title = $this->create_course_title($title, $this->firstsection);
        }

        if (strpos($titlespec, '[REPEAT]') !== false) {
            $titles = [];
            // Now we are going to repeat, making a title for each course section.
            if (!empty($this->sections)) {
                foreach ($this->sections as $section) {
                    $titles[] = $this->create_course_title($repeattitle, $section);
                }
            }

            // Then join them together with the divider.
            $repeatedtitle = implode($divider, $titles);
            $title = str_replace('[REPEAT]', $repeatedtitle, $title);
        }

        $title = str_replace('[XLSID]', $this->data->sdid, $title);

        // Limited to 254 characters.
        return substr($title, 0, 254);
    }
}
