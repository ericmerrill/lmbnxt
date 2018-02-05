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
     * @param false|string $customid A custom ID, so we can have multiple records.
     * @param bool $create If true, create the missing instance.
     * @return false|stdClass The instance record, or false if none.
     */
    public function get_instance($courseorid, $customid = false, $create = true) {
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

        $params = ['courseid' => $courseid, 'enrol' => 'lmb'];
        if ($customid) {
            $params['customchar1'] = $customid;
        }

        // Try to find an existing instance.
        $instance = $DB->get_record('enrol', $params);

        // If not found, we need to make one.
        if (!$instance) {
            if (!$course) {
                // Try to load the course record.
                $course = $DB->get_record('course', ['id' => $courseid]);
            }

            $instance = $this->create_instance($course, $customid);
        }

        return $instance;
    }

    public function create_instance($course, $customid = false) {
        global $DB;

        if (!$course) {
            // If we still don't have one, then we need to give up.
            return false;
        }

        $fields = [];
        if ($customid) {
            $fields['name'] = get_string('enrolcustomname', 'enrol_lmb', $customid);
            $fields['customchar1'] = $customid;
        }

        $instanceid = $this->add_instance($course, $fields);
        if (empty($instanceid)) {
            // If it wasn't created, then give up.
            return false;
        }

        // Now we need to get the record.
        $instance = $DB->get_record('enrol', ['id' => $instanceid]);

        return $instance;
    }

    public function unenrol_all_users($instance, $keepteachers = false) {
        global $DB;
        if (!$keepteachers) {
            $enrols = $DB->get_recordset('user_enrolments', ['enrolid' => $instance->id]);

            foreach ($enrols as $enrol) {
                $this->unenrol_user($instance, $enrol->userid);
            }

            $enrols->close();
        } else {
            debugging("TODO");
        }
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
