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

use enrol_lmb\local\processors\xml\trait_timeframe;
use enrol_lmb\local\data\term;
use enrol_lmb\local\moodle;

/**
 * Class for working with course section message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person extends base {
    use trait_timeframe;

    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/pms2p0/wsdl11/sync/imspms_v2p0";

    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\person';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/lis2/mappings/person.json';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    protected function process_formname_node($node, $mapping) {
        $isset = isset($node->FORMNAMETYPE->INSTANCEVALUE->TEXTSTRING);

        // Only work with the full name.
        if ($isset && (strcasecmp('Full', $node->FORMNAMETYPE->INSTANCEVALUE->TEXTSTRING->get_value()) === 0)) {
            if (isset($node->FORMATTEDNAME->TEXTSTRING)) {
                $this->dataobj->fullname = $node->FORMATTEDNAME->TEXTSTRING->get_value();
            }
        }
    }

    protected function process_name_node($node, $mapping) {
        $isset = isset($node->NAMETYPE->INSTANCEVALUE->TEXTSTRING);

        // We are only going to work with the full name.
        if ($isset && (strcasecmp('Full', $node->NAMETYPE->INSTANCEVALUE->TEXTSTRING->get_value()) === 0)) {
            $parts = $node->PARTNAME;
            if (!is_array($parts)) {
                $parts = [$parts];
            }

            foreach ($parts as $part) {
                $this->process_partname_node($part);
            }
        }
    }

    protected function process_contactinfo_node($node, $mapping) {
        if (!isset($node->CONTACTINFOTYPE->INSTANCEIDENTIFIER->TEXTSTRING) || !isset($node->CONTACTINFOVALUE->TEXTSTRING)) {
            return;
        }

        // We are only going to work with the full name.
        if (strcasecmp('EmailPrimary', $node->CONTACTINFOTYPE->INSTANCEIDENTIFIER->TEXTSTRING->get_value()) === 0) {
            $this->dataobj->email = $node->CONTACTINFOVALUE->TEXTSTRING->get_value();
        }
    }

    protected function process_institutionrole_node($node, $mapping) {
        $isset = isset($node->INSTITUTIONROLETYPE->INSTANCEIDENTIFIER->TEXTSTRING);
        if (!$isset || !isset($node->INSTITUTIONROLETYPE->INSTANCEVALUE->TEXTSTRING)) {
            return;
        }

        $type = $node->INSTITUTIONROLETYPE->INSTANCEIDENTIFIER->TEXTSTRING->get_value();
        switch ($type) {
            case ('Student'):
                $this->dataobj->rolestudent = 1;
                break;
            case ('Faculty'):
                $this->dataobj->rolefaculty = 1;
                break;
            case ('Staff'):
                $this->dataobj->rolestaff = 1;
                break;
            case ('Alumni'):
                $this->dataobj->rolealumni = 1;
                break;
            case ('ProspectiveStudent'):
                $this->dataobj->roleprospectivestudent = 1;
                break;
        }

        if (isset($node->PRIMARYROLETYPE) && (strcasecmp('true', $node->PRIMARYROLETYPE->get_value()) === 0)) {
            // Save the primary role type.
            $this->dataobj->primaryrole = $type;
        }
    }

    protected function process_partname_node($part) {
        if (!isset($part->INSTANCENAME->TEXTSTRING) || !isset($part->INSTANCEVALUE->TEXTSTRING)) {
            // We can't do anything without the part name and value.
            return;
        }
        $partname = $part->INSTANCENAME->TEXTSTRING->get_value();

        // Based on the part name, we are going to save the value to a different location.
        switch ($partname) {
            case "First":
            case "Given":
                $destination = 'givenname';
                break;
            case "Last":
            case "Family":
                $destination = 'familyname';
                break;
            case "Middle":
                $destination = 'middlename';
                break;
            case "Prefix":
                $destination = 'prefix';
                break;
            case "Suffix":
                $destination = 'suffix';
                break;
            case "Nickname":
                $destination = 'nickname';
                break;
            default:
                $destination = false;
        }

        if ($destination === false) {
            // We don't know this name parts destination.
            return;
        }

        $this->dataobj->$destination = $part->INSTANCEVALUE->TEXTSTRING->get_value();
    }

    protected function process_extensionfield_node($node, $mapping) {
        if (!isset($node->FIELDNAME) || !isset($node->FIELDVALUE)) {
            // We need these fields - they should always be there.
            return;
        }

        $fieldname = $node->FIELDNAME->get_value();

        $extension = new \stdClass();
        $extension->value = $node->FIELDVALUE->get_value();
        if (isset($node->FIELDTYPE)) {
            $extension->type = $node->FIELDTYPE->get_value();
        } else {
            $extension->type = false;
        }

        $this->dataobj->extension[$fieldname] = $extension;

        switch ($fieldname) {
            case "BannerID":
                $this->dataobj->sctid = $extension->value;
                break;
            case "SourcedId":
                // This is a good backup method for making sure we get the true sourcedid.
                $this->dataobj->sdid = $extension->value;
                break;
        }
    }

    protected function process_userid_node($node, $mapping) {
        if (!isset($node->USERIDVALUE->TEXTSTRING) || !isset($node->USERIDTYPE->TEXTSTRING)) {
            // We need the userid value and the type to continue.
            return;
        }

        $userid = new \stdClass();
        $userid->userid = $node->USERIDVALUE->TEXTSTRING->get_value();
        if (isset($node->PASSWORD->TEXTSTRING)) {
            $userid->password = $node->PASSWORD->TEXTSTRING->get_value();
        } else {

        }
        if (isset($node->PWENCRYPTIONTYPE->TEXTSTRING)) {
            $userid->pwencryptiontype = $node->PWENCRYPTIONTYPE->TEXTSTRING->get_value();
        }

        $useridtype = $node->USERIDTYPE->TEXTSTRING->get_value();
        $this->dataobj->userid[$useridtype] = $userid;

        switch ($useridtype) {
            case "Logon ID":
                $this->dataobj->logonid = $userid->userid;
                break;
            case "Email ID":
                $this->dataobj->emailid = $userid->userid;
                break;
            case "SCTID":
            case "Banner ID":
                $this->dataobj->sctid = $userid->userid;
                break;
        }
    }

}
