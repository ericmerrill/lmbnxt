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
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\processors\xml;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person extends base {
    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\person';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/xml/mappings/person.json';

    const CATEGORY = 'person';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    public function get_moodle_converter() {
        return new moodle\user();
    }

    /**
     * Process userid nodes into the data object.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_userid_node($node, $mapping) {
        if (!$type = $node->get_attribute('USERIDTYPE')) {
            // We need a type to do anything with this.
            return;
        }

        if (!isset($this->dataobj->userid)) {
            // This is going to be an array, so create it.
            $this->dataobj->userid = array();
        }

        $userid = new \stdClass();
        $userid->userid = $node->get_value();
        $userid->password = $node->get_attribute('PASSWORD');
        $userid->pwencryptiontype = $node->get_attribute('PWENCRYPTIONTYPE');

        switch ($type) {
            case 'Logon ID':
                $this->dataobj->logonid = $userid->userid;
                break;
            case 'SCTID':
                $this->dataobj->sctid = $userid->userid;
                break;
            case 'Email ID':
                $this->dataobj->emailid = $userid->userid;
                break;
        }

        $this->dataobj->userid[$type] = $userid;
    }

    /**
     * Process telephone nodes.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_telephone_node($node, $mapping) {
        // Standard voice line.
        if ($node->get_attribute('TELTYPE') == 1) {
            $this->dataobj->televoice = $node->get_value();
            return;
        }

        // Mobile phone number.
        if ($node->get_attribute('TELTYPE') == 3) {
            $this->dataobj->telemobile = $node->get_value();
            return;
        }
    }

    /**
     * Proccess an institute role node.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_instituterole_node($node, $mapping) {
        $type = $node->get_attribute('INSTITUTIONROLETYPE');


        $primary = $node->get_attribute('PRIMARYROLE');
        if (!empty($primary) && (strcasecmp($primary, 'Yes') === 0)) {
            $this->dataobj->primaryrole = $type;
        }

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
    }

    protected function post_mappings() {
        parent::post_mappings();

        // Nickname may come in "Nickname Family" format. We want just the nickname.
        if (!empty($this->dataobj->nickname) && !empty($this->dataobj->familyname)) {
            $fullnick = $this->dataobj->nickname;
            $family = $this->dataobj->familyname;

            if (preg_match('|^(.*) '.$family.'$|', $fullnick, $matches)) {
                $this->dataobj->nickname = $matches[1];
            }
        }
    }

}
