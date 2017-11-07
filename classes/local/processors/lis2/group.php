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

namespace enrol_lmb\local\processors\lis2;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\types;

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group extends base {
    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/gms2p0/wsdl11/sync/imsgms_v2p0";

    /**
     * The data object path for this object.
     */
    const DATA_CLASS = false;

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = false;

    /**
     * Processes the passed xml_node into a data object of the current type.
     *
     * @param xml_node $node The node to work on
     * @return array|enrol_lmb\local\data\person
     */
    public function process_xml_to_data($node) {
        // ID what group type for the XML, then pass on to the correct converter.
        if (!isset($node->GROUPRECORD->GROUP->GROUPTYPE->TYPEVALUE->ID)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_grouptype_not_found');
        }

        switch (strtolower($node->GROUPRECORD->GROUP->GROUPTYPE->TYPEVALUE->ID->get_value())) {
            case 'term':
                $term = types::get_processor_for_class('\\enrol_lmb\\local\\processors\\lis2\\group_term');
                return $term->process_xml_to_data($node);
        }

        throw new \enrol_lmb\local\exception\message_exception('exception_grouptype_not_found');
    }

}
