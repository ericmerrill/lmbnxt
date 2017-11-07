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
class group_course extends base {
    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\course';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/xml/mappings/group_course.json';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    /**
     * Proccess a relationship node.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_relationship_node($node, $mapping) {
        switch (strtolower($node->LABEL->get_value())) {
            case 'college':
                $this->dataobj->collegesdidsource = $node->SOURCEDID->SOURCE->get_value();
                $this->dataobj->collegesdid = $node->SOURCEDID->ID->get_value();
                break;
            case 'department':
                $this->dataobj->deptsdidsource = $node->SOURCEDID->SOURCE->get_value();
                $this->dataobj->deptsdid = $node->SOURCEDID->ID->get_value();
                break;
            default:
                return;
        }
    }

}
