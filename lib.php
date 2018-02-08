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

use enrol_lmb\local\data;
use enrol_lmb\logging;

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
            if (!$create) {
                return false;
            }
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
        $fields['customchar2'] = $course->idnumber;
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

    /**
     * Unenrol all users for a given instance.
     *
     * @param stdClass $instance The enrol instance object
     * @param bool $keepteachers If true, teachers can stay in the course if there is content TODO
     */
    public function unenrol_all_users(stdClass $instance, $keepteachers = false) {
        global $DB;
        if (!$keepteachers) {
            $sql = "SELECT u.id AS userid, u.idnumber FROM {user_enrolments} ue
                      JOIN {user} u ON u.id = ue.userid
                      WHERE ue.enrolid = :enrolid";
            $enrols = $DB->get_recordset_sql($sql, ['enrolid' => $instance->id]);

            foreach ($enrols as $enrol) {
                logging::instance()->log_line("Unenroling user {$enrol->idnumber} from {$instance->customchar2}");
                $this->unenrol_user($instance, $enrol->userid);
            }

            $enrols->close();
        } else {
            debugging("TODO");
        }
    }

    /**
     * Gets the enrol instance for the provided sdid
     *
     * @param string $sdid The course sdid
     * @param stdClass $course The course object if known. Saves a DB hit.
     * @return stdClass|false The instance, or false if not found
     */
    public function get_instance_for_section_sdid($sdid, stdClass $course = null) {
        global $DB;

        if (empty($course) || empty($course->idnumber) || strcasecmp($course->idnumber, $sdid) !== 0) {
            $course = $DB->get_record('course', ['idnumber' => $sdid]);
        }

        if (empty($course)) {
            return false;
        }

        $instance = $this->get_instance($course);
        if ($instance) {
            return $instance;
        }

        return false;
    }

    /**
     * Get instances to use to enrol/unenrol users for a given group sdid. Accounds for crosslists and such.
     *
     * @param string $groupsdid The course sdid
     * @param stdClass $course The course object if known. Saves a DB hit.
     * @return stdClass[] An array of instances. Empty array if none.
     */
    public function get_current_instances($groupsdid, stdClass $course = null) {
        global $DB;
        $crosslists = data\crosslist_member::get_members_for_section_sdid($groupsdid);

        $instances = [];

        if (empty($crosslists)) {
            $instance = $this->get_instance_for_section_sdid($groupsdid, $course);
            if ($instance) {
                $instances[] = $instance;
            }
        } else {
            $enrol = new enrol_lmb_plugin();
            foreach ($crosslists as $member) {
                $groupcourse = $DB->get_record('course', ['idnumber' => $member->groupsdid]);
                if (empty($groupcourse)) {
                    continue;
                }
                $instance = $enrol->get_instance($groupcourse, $member->sdid);
                if ($instance) {
                    $instances[] = $instance;
                }
            }
        }

        return $instances;
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
