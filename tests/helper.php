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

use enrol_lmb\settings;

/**
 * A base testcase for XML tests.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class xml_helper extends advanced_testcase {

    public function setUp() {
        settings_helper::reset();
    }

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
        // By not setting a controller, it won't process, just gives us the nodes.
        $parser = new \enrol_lmb\parser();
        $parser->add_type('tests');
        $parser->process_file($path);
        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();

        return $node;
    }

    protected function run_protected_method($obj, $name, $args = []) {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    protected function get_protected_attribute($obj, $name) {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * Update a protected property. If you pass a classname, will update static, if object, will update instance.
     */
    protected function set_protected_property($obj, $name, $value) {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
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
class xml_tester extends \enrol_lmb\local\processors\xml\base {
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


    protected $defaults = array('defkey' => 'Default value', 'addkey' => 'Additional key default');

    public function log_id() {
    }

    protected function get_record() {

    }

    protected function update_if_needed() {

    }

    protected function handler_double($name, $value) {
        return $value * 2;
    }

    /**
     * Allows us to directly update a value in a data object, bypassing handlers.
     */
    public static function set_value($obj, $key, $value) {
        $obj->record->$key = $value;
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

class settings_helper extends \enrol_lmb\settings {
    public static function temp_set($key, $value) {
        $settings = \enrol_lmb\settings::get_settings();
        $settings->settings->$key = $value;
    }

    public static function reset() {
        \enrol_lmb\settings::$settingobj = null;
    }
}
