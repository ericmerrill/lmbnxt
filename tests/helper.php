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
 * A XML helper testcase.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
abstract class xml_helper extends advanced_testcase {

    /**
     * Returns a xml_node for a given xml string.
     *
     * @param string $xml The xml to work on
     * @return xml_node|null The xml node
     */
    protected function get_node_for_xml($xml) {
        $parser = new \enrol_lmb\parser();
        $parser->add_type('tests');
        $parser->process_string($xml);
        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();

        return $node;
    }

    /**
     * Returns a xml_node for a given xml path.
     *
     * @param string $path The xml path to work on
     * @return xml_node|null The xml node
     */
    protected function get_node_for_file($path) {
        $parser = new \enrol_lmb\parser();
        $parser->add_type('tests');
        $parser->process_file($path);
        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();

        return $node;
    }
}

class xml_tester extends \enrol_lmb\local\xml\base {
    const MAPPING_PATH = '/enrol/lmb/tests/fixtures/testmapping.json';

    public function __construct() {
        $this->load_mappings();
    }

    public function process_xml_to_data($xmlobj) {
        $this->dataobj = new data_test();

        // First we are going to use the simple static mappings.
        $this->apply_mappings($xmlobj);

        return $this->dataobj;
    }

    protected function process_node_5($node, $mapping) {
        $this->dataobj->node5 = $node->get_value();
    }
}

class data_test extends \enrol_lmb\local\data\base {

}
