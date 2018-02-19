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
 * Works on types of messages.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\processors\lis2;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\data;

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person_member_delete extends base {
    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/mms2p0/wsdl11/sync/imsmms_v2p0";

    /**
     * Path to the mapping file.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/lis2/mappings/person_member_delete.json';

    /**
     * Data class used by this type.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\person_member';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    protected function post_mappings() {
        global $DB;

        parent::post_mappings();

        // Without a message reference, there isn't anything we can do.
        if (empty($this->dataobj->messagereference)) {
            $this->dataobj = false;
            return;
        }

        $messageref = $this->dataobj->messagereference;

        // Now we are going to try and find an existing.
        $record = $DB->get_record(data\person_member::TABLE, ['messagereference' => $messageref]);

        if ($record) {
            $this->dataobj->load_from_record($record);
        } else {
            // If we can't find an existing one by message ref, time to take more drastic messasures.
            // We know the message ref from banner is usually in XX-role-groupsdid-usersdid format.
            $found = preg_match('|^.*-([a-z0-9]*)-CS([0-9\.]*)-([a-z0-9]*)$|i', $messageref, $matches);

            if (!$found || count($matches) !== 4) {
                // We don't know the format if we get here, so we can't guess.
                $this->dataobj = false;
                return;
            }

            $rolename = $matches[1];
            $imsrole = person_member::get_roletype_for_name($rolename);

            $groupsdid = $matches[2];
            $usersdid = $matches[3];

            $params = ['membersdid' => $usersdid, 'groupsdid' => $groupsdid, 'roletype' => $imsrole];
            $record = $DB->get_record(data\person_member::TABLE, $params);

            if ($record) {
                $this->dataobj->load_from_record($record);
                // If we got here, that means the message reference wasn't on it. We should fix that.
                $this->dataobj->messagereference = $messageref;
            } else {
                // Now just fill the data in from what we know.
                $this->dataobj->lis_roletype = $rolename;
                $this->dataobj->roletype = $imsrole;
                $this->dataobj->membersdid = $usersdid;
                $this->dataobj->groupsdid = $groupsdid;
            }
        }

        // Now, set the information we know based on this information.
        $this->dataobj->status = 0;
        $this->dataobj->lis_status = 'Inactive';

        return;
    }
}
