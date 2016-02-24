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
 * Test helper classes.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A base testcase for XML tests.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

/**
 * A XML object that is testable.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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

/**
 * A data class that works for testing.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_test extends \enrol_lmb\local\data\base {

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('dbkey', 'additional', 'defkey', 'nondefkey');

    protected $handlers = array('double' => 'handler_double',
                                'boolean' => 'handler_boolean',
                                'date' => 'handler_date');


    protected $defaults = array('defkey' => 'Default value');

    public function log_id() {
    }

    protected function get_record() {

    }

    protected function update_if_needed() {

    }

    protected function handler_double($name, $value) {
        return $value * 2;
    }

}

/**
 * A logging helper for capturing and testing logging output.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logging_helper extends \enrol_lmb\logging {
    /** var string The captured output buffer */
    protected $testoutputbuffer = '';

    /**
     * Basic constructor that makes a new object and stores it in the instance.
     */
    public function __construct() {
        self::test_set_instance($this);
        parent::__construct();
    }

    /**
     * Overrides the default object and captures the output.
     *
     * @param string $line The line to print
     */
    protected function print_line($line) {
        $this->testoutputbuffer .= $line."\n";
    }

    /**
     * Returns the output buffer and clears its contents.
     *
     * @return string
     */
    public function test_get_flush_buffer() {
        $output = $this->testoutputbuffer;
        $this->testoutputbuffer = '';
        return $output;
    }

    /**
     * Overrides the default object and captures the output.
     *
     * @param mixed $in The instances to set, or null to clear.
     */
    public static function test_set_instance($in) {
        // Make sure both logging and logging_helper have the same instance set.
        \enrol_lmb\logging::$instance = $in;
        self::$instance = $in;
    }

    /**
     * Resets the depth of the object.
     */
    public function test_reset_level() {
        // Make sure both logging and logging_helper have the same instance set.
        $this->depth = 0;
    }

}
