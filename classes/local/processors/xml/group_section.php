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

use enrol_lmb\local\moodle;

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_section extends base {
    use trait_timeframe;

    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\section';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/xml/mappings/group_section.json';

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
            case 'course':
                $this->dataobj->coursesdidsource = $node->SOURCEDID->SOURCE->get_value();
                $this->dataobj->coursesdid = $node->SOURCEDID->ID->get_value();
                break;
            case 'term':
                $this->dataobj->termsdidsource = $node->SOURCEDID->SOURCE->get_value();
                $this->dataobj->termsdid = $node->SOURCEDID->ID->get_value();
                break;
            default:
                return;
        }
    }

    /**
     * Proccess a recurring event node.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_recurringevent_node($node, $mapping) {
        $mappings = $mapping['mappings'];

        $event = new \stdClass();
        foreach ($mappings as $key => $field) {
            if (isset($node->$key)) {
                $event->$field = $node->$key->get_value();
            }
        }

        if (isset($this->dataobj->events)) {
            $this->dataobj->events[] = $event;
        } else {
            $this->dataobj->events = array($event);
        }
    }

    /**
     * Process the rubic node to extract extra data.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_rubric_node($node, $mapping) {
        $rubric = $node->get_value();
        $this->dataobj->rubric = $rubric;

        if (preg_match('|([a-z0-9]*)-([a-z0-9]*)-([a-z0-9]*)|i', $rubric, $matches)) {
            $this->dataobj->deptsdid = $matches[1];
            $this->dataobj->rubricdept = $matches[1];
            $this->dataobj->coursenumber = $matches[2];
            $this->dataobj->sectionnumber = $matches[3];
        }
    }

}
