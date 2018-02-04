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
 * An activity to interface with WebEx.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('enrollib.php');

class enrol_lmb_plugin extends enrol_plugin {

    /**
     * Get or make the enrol instance record for the passed course id.
     *
     * @param int|stdClass $courseorid The course id or the DB record for the course.
     * @return false|stdClass The instance record, or false if none.
     */
    public function get_instance($courseorid) {
        global $DB;

        if (is_numeric($courseorid)) {
            $courseid = $courseorid;
            $course = false;
        } else if (($courseorid instanceof stdClass) && isset($courseorid->id)) {
            $course = $courseorid;
            $courseid = $courseorid->id;
        } else {
            debugging("Expected stdClass or int passed to enrol_lmb_plugin->get_instance().", DEBUG_DEVELOPER);
            return false;
        }

        // Try to find an existing instance.
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'lmb']);

        // If not found, we need to make one.
        if (!$instance) {
            if (!$course) {
                // Try to load the course record.
                $course = $DB->get_record('course', ['id' => $courseid]);
            }
            if (!$course) {
                // If we still don't have one, then we need to give up.
                return false;
            }

            $instanceid = $this->add_instance($course);
            if (empty($instanceid)) {
                // If it wasn't created, then give up.
                return false;
            }

            // Now we need to get the record.
            $instance = $DB->get_record('enrol', ['id' => $instanceid]);
        }

        return $instance;
    }

    // Base class overrides.

    public function can_add_instance($courseid) {
        return false;
    }

    public function can_edit_instance($instance) {
        return false;
    }

    // TODO - what to do with this?
//     public function get_newinstance_link($courseid) {
//         return NULL;
//     }

    public function can_hide_show_instance($instance) {
        return false;
    }

    public function get_unenrolself_link($instance) {
        return NULL;
    }

    // TODO - Need to do things to impliment expirations I think...
}
