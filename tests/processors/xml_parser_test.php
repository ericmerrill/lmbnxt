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
 * Tests for the xml parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\xml_node;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class xml_parser_testcase extends xml_helper {
    public function test_parser() {
        $xml = '<tests><n1 a1="va1">vn1</n1><n2><nc>vnc1</nc><nc>vnc2</nc><nc>vnc3</nc></n2>'.
               '<n3 a1="va1" a2="va2"><nc>vnc1</nc></n3></tests>';
        $node = $this->get_node_for_xml($xml);

        // Check the root node.
        $this->assertEquals('TESTS', $node->get_name());

        // Node n1.
        $this->assertTrue(isset($node->N1));
        $this->assertInstanceOf(xml_node::class, $node->N1);
        $this->assertTrue($node->N1->has_data());
        $this->assertEquals('vn1', $node->N1->get_value());
        $this->assertEquals('N1', $node->N1->get_name());
        $this->assertEquals('va1', $node->N1->get_attribute('A1'));

        // Node n2.
        $this->assertTrue(isset($node->N2));
        $this->assertInstanceOf(xml_node::class, $node->N2);
        $this->assertFalse($node->N2->has_data());
        $this->assertNull($node->N2->get_value());

        // Children of n2.
        $this->assertInternalType('array', $node->N2->NC);
        $this->assertCount(3, $node->N2->NC);
        $this->assertEquals('vnc1', $node->N2->NC[0]->get_value());
        $this->assertEquals('vnc2', $node->N2->NC[1]->get_value());
        $this->assertEquals('vnc3', $node->N2->NC[2]->get_value());

        // Node n3.
        $this->assertTrue(isset($node->N3));
        $this->assertInstanceOf(xml_node::class, $node->N3);
        $this->assertTrue($node->N3->has_data());
        $this->assertInternalType('array', $node->N3->get_attributes());
        $this->assertCount(2, $node->N3->get_attributes());
        $this->assertEquals('va1', $node->N3->get_attributes()['A1']);
        $this->assertEquals('va2', $node->N3->get_attributes()['A2']);

    }

    public function test_enterprise_path() {
        $xml = '<enterprise><tests><n1>V</n1></tests></enterprise>';
        $node = $this->get_node_for_xml($xml);

        $this->assertInstanceOf(xml_node::class, $node);
        $this->assertInstanceOf(xml_node::class, $node->N1);
        $this->assertEquals('V', $node->N1->get_value());
    }

    public function test_parse_file() {
        global $CFG;

        // No file error.
        $this->resetDebugging();
        $parser = new \enrol_lmb\parser();
        $parser->add_type('tests');
        $this->assertFalse($parser->process_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/does_not_exist.xml'));
        $this->assertDebuggingCalled();

        // Now a working file.
        $parser = new \enrol_lmb\parser();
        $parser->add_type('tests');
        $this->assertTrue($parser->process_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/basic_file.xml'));
        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();

        $this->assertInstanceOf(xml_node::class, $node);
        $this->assertInstanceOf(xml_node::class, $node->N1);
        $this->assertEquals('V', $node->N1->get_value());
    }

    public function test_set_controller() {
        $parser = new \enrol_lmb\parser();
        $controller = new \enrol_lmb\controller();
        $parser->set_controller($controller);
        $this->assertAttributeEquals($controller, 'controller', $parser);
    }

    public function test_multiple_objects() {
        global $CFG;

        $parser = new \enrol_lmb\parser();
        $parser->add_type('tests');
        $this->assertTrue($parser->process_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/multi_good_form.xml'));
        $this->assertDebuggingNotCalled();

        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();
        $this->assertEquals('V2', $node->N1->get_value());

        $parser = new \enrol_lmb\parser();
        $parser->add_type('tests');
        $this->assertTrue($parser->process_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/multi_poor_form.xml'));
        $this->assertDebuggingNotCalled();

        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();
        $this->assertEquals('V2', $node->N1->get_value());

        $xml = '<?xml version="1.0" encoding="UTF-8"?><enterprise><tests><n1>V1</n1></tests>'.
               '<tests><n1>V2</n1></tests></enterprise>';
        $node = $this->get_node_for_xml($xml);
        $this->assertEquals('V2', $node->N1->get_value());

        $xml = '<tests><n1>V1</n1></tests><tests><n1>V2</n1></tests>';
        $node = $this->get_node_for_xml($xml);
        $this->assertEquals('V2', $node->N1->get_value());
    }

    public function test_parser_errors() {
        global $CFG;

        $parser = new \enrol_lmb\parser();

        // Check no parser exception.
        try {
            $parser->process();
            $this->fail("Expected exception not thrown.");
        } catch (Exception $ex) {
            $this->assertInstanceOf('progressive_parser_exception', $ex);
            $this->assertStringStartsWith('error/undefined_parser_processor', $ex->getMessage());
        }

        // Check no file/contents exception.
        $processor = new \enrol_lmb\parse_processor(null);
        $parser->set_processor($processor);
        try {
            $parser->process();
            $this->fail("Expected exception not thrown.");
        } catch (Exception $ex) {
            $this->assertInstanceOf('progressive_parser_exception', $ex);
            $this->assertStringStartsWith('error/undefined_xml_to_parse', $ex->getMessage());
        }

        // Check already used exception.
        $parser->set_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/multi_good_form.xml');
        $parser->process();
        try {
            $parser->process();
            $this->fail("Expected exception not thrown.");
        } catch (Exception $ex) {
            $this->assertInstanceOf('progressive_parser_exception', $ex);
            $this->assertStringStartsWith('error/progressive_parser_already_used', $ex->getMessage());
        }
    }
}
