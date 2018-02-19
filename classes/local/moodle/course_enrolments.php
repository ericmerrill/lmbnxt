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

require_once($CFG->dirroot.'/user/lib.php');

/**
 * Abstract object for converting a data object to Moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_enrolments extends base {

    /**
     * Reprocess all the enrolments for a given sourcedid.
     *
     * @param string $sdid The course to reprocess for.
     * @param int|false $status The status to process. False for all statuses.
     */
    public static function reprocess_enrolments_for_section_sdid($sdid, $status = 1) {
        global $DB;

        $params = ['groupsdid' => $sdid];
        if (is_numeric($status)) {
            $params['status'] = $status;
        }

        $members = $DB->get_recordset(data\person_member::TABLE, $params);

        $enrolment = new enrolment();
        foreach ($members as $memberrec) {
            $member = new data\person_member();
            $member->load_from_record($memberrec);
            $member->log_id();

            $enrolment->convert_to_moodle($member);
        }

        $members->close();
    }

    /**
     * Reprocess all the enrolments for a given sourcedid.
     *
     * @param string $sdid The course to reprocess for.
     * @param int|false $status The status to process. False for all statuses.
     */
    public static function reprocess_enrolments_for_user_sdid($sdid, $status = 1) {
        global $DB;

        $params = ['membersdid' => $sdid];
        if (is_numeric($status)) {
            $params['status'] = $status;
        }

        $members = $DB->get_recordset(data\person_member::TABLE, $params);

        $enrolment = new enrolment();
        foreach ($members as $memberrec) {
            $member = new data\person_member();
            $member->load_from_record($memberrec);
            $member->log_id();

            $enrolment->convert_to_moodle($member);
        }

        $members->close();
    }
}
