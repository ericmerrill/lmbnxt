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
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class replacemembershiprequest extends base_lis {
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/mms2p0/wsdl11/sync/imsmms_v2p0";

    const MAPPING_PATH = '/enrol/lmb/classes/local/xml/mappings/replacememberrequest.json';

    const DATA_CLASS = '\\enrol_lmb\\local\\data\\member_person';
    // TODO loop handling - confirm not needed in spec.
    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }
//
//
//     use trait_timeframe;
//     /**
//      * The data object path for this object.
//      */
//     const DATA_CLASS = '\\enrol_lmb\\local\\data\\member_person';
//
//     /**
//      * Path to this objects mappings.
//      */
//     const MAPPING_PATH = '/enrol/lmb/classes/local/xml/mappings/member_person.json';
//
//     /**
//      * Basic constructor.
//      */
//     public function __construct() {
//         $this->load_mappings();
//     }
//
//     /**
//      * Proccess a role node.
//      *
//      * @param xml_node|array $node The XML node to process, or array of nodes
//      * @param array $mapping The mapping for the field
//      */
//     public function process_role_node($node, $mapping) {
//         $this->dataobj->roletype = $node->get_attribute('ROLETYPE');
//
//         $this->apply_mappings($node, $mapping['mappings']);
//     }
}
