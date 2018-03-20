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
use enrol_lmb\local\moodle;
use enrol_lmb_plugin;

require_once($CFG->dirroot.'/enrol/lmb/lib.php');

/**
 * Abstract object for converting a data object to Moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment extends base {
    /**
     * This function takes a data object and attempts to apply it to Moodle.
     *
     * @param data\base $data A data object to process.
     */
    public function convert_to_moodle(\enrol_lmb\local\data\base $data) {
        global $DB;

        if (!($data instanceof data\person_member)) {
            throw new \coding_exception('Expected instance of data\person_member to be passed.');
        }

        $this->data = $data;

        // Check that user exsits.
        $user = moodle\user::get_user_for_sdid($this->data->membersdid);
        if (empty($user)) {
            // TODO - respond with error?
            logging::instance()->log_line("Moodle user could not be found.", logging::ERROR_WARN);
            return;
        }

        // Check that course exists.
        $course = moodle\course::get_course_for_sdid($this->data->groupsdid);
        if (empty($course)) {
            // TODO - respond with error?
            logging::instance()->log_line("Moodle course could not be found.", logging::ERROR_WARN);
            return;
        }

        // Now get the role id.
        $roleid = $this->get_moodle_role_id();
        if (empty($roleid)) {
            logging::instance()->log_line("No role mapping found for ".$this->data->roletype.".", logging::ERROR_NOTICE);
            return;
        }

        $enrol = new enrol_lmb_plugin();
        $instances = $enrol->get_current_instances($data->groupsdid, $course);

        if (empty($instances)) {
            // Nope, couldn't get or make an enrol instance.
            logging::instance()->log_line("Could not find or create the enrol instance for the course.", logging::ERROR_WARN);
            return;
        }


        // TODO - group memberships.



        foreach ($instances as $instance) {
            $extra = '';
            if ($data->status) {
                $starttime = 0;
                $endtime = 0;
                if ($this->settings->get('restrictenroldates')) {
                    // If we have restrict dates set, and they have dates, then use them.
                    if (!empty($data->begindate)) {
                        $starttime = $data->begindate;
                    }
                    if (!empty($data->enddate)) {
                        $endtime = $data->enddate;
                    }
                }

                $sql = "SELECT * FROM {user_enrolments} ue
                          JOIN {role_assignments} ra
                            ON ue.userid = ra.userid AND ue.enrolid = ra.itemid AND ra.component = 'enrol_lmb'
                         WHERE ue.enrolid = :enrolid
                           AND ue.userid = :userid
                           AND ue.timestart = :start
                           AND ue.timeend = :end
                           AND ra.roleid = :roleid";


                $params = ['enrolid' => $instance->id,
                           'userid' => $user->id,
                           'start' => $starttime,
                           'end' => $endtime,
                           'roleid' => $roleid];

                if ($DB->record_exists_sql($sql, $params)) {
                    // It alrady exists, so we can skip it.
                    continue;
                }

                // TODO - Recover grades.
                if (!empty($instance->customchar2)) {
                    $extra = ' in '.$instance->customchar2;
                }
                logging::instance()->log_line("Enrolling user {$this->data->membersdid}".$extra);
                $enrol->enrol_user($instance, $user->id, $roleid, $starttime, $endtime, ENROL_USER_ACTIVE, true);
            } else {
                // TODO - We need to handle multiple overlapping role assign/unassign. Unenroll and unassign are different...
                if (!empty($instance->customchar2)) {
                    $extra = ' from '.$instance->customchar2;
                }
                logging::instance()->log_line("Unenrolling user {$this->data->membersdid}".$extra);
                $enrol->unenrol_user($instance, $user->id);
            }
        }
    }

    /**
     * Get the Moodle role id for a given IMS Role number.
     *
     * @return false|int The role id, or false
     */
    protected function get_moodle_role_id() {
        $imsrole = $this->data->roletype;
        $roleid = $this->settings->get('imsrolemap'.$imsrole);

        if (is_null($roleid)) {
            // This means the setting isn't set.
            $roleid = self::get_default_role_id($imsrole);
        }

        if (!$roleid) {
            return false;
        }

        return $roleid;
    }

    /**
     * Get the default role id for the provided role number
     *
     * @param string $imsrole
     * @return false|int The role id, or false.
     */
    public static function get_default_role_id($rolenumber) {
        // Default mappings.
        $imsmappings = ['01' => 'student',
                        '02' => 'editingteacher',
                        '03' => 'student',
                        '04' => 'student',
                        '05' => 'student'];

        $archetype = false;
        if (isset($imsmappings[$rolenumber]) && !empty($imsmappings[$rolenumber])) {
            $archetype = $imsmappings[$rolenumber];
        } else {
            return false;
        }

        if ($role = get_archetype_roles($archetype)) {
            $role = reset($role);
            return $role->id;
        }

        return false;
    }
}
