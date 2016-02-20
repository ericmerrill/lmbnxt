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

namespace enrol_lmb\local\xml;

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

    protected $mappingjson;

    protected $mappings;

    const TYPE = 'person';
    const MAPPING_PATH = '/enrol/lmb/classes/local/xml/mappings/person.json';

    public function __construct() {
        $this->load_mappings();
    }

    public function process_xml_to_data($xmlobj) {
        $class = '\\enrol_lmb\\local\\data\\'.static::TYPE;
        $this->dataobj = new $class();

        // First we are going to use the simple static mappings.
        $this->apply_mappings($xmlobj);

        return $this->dataobj;
    }

    protected function process_userid_node($node, $mapping) {
        if (!$type = $node->get_attribute('useridtype')) {
            return;
        }

        if (!isset($this->dataobj->userid)) {
            $this->dataobj->userid = array();
        }

        $userid = new \stdClass();
        $userid->userid = $node->get_value();
        $userid->password = $node->get_attribute('password');
        $userid->pwencryptiontype = $node->get_attribute('pwencryptiontype');

        $this->dataobj->userid[$type] = $userid;
    }

    protected function process_telephone_node($node, $mapping) {
        // Standard voice line.
        if ($node->get_attribute('teltype') == 1) {
            $this->dataobj->televoice = $node->get_value();
            return;
        }

        // Mobile phone number.
        if ($node->get_attribute('teltype') == 3) {
            $this->dataobj->telemobile = $node->get_value();
            return;
        }
    }

}
