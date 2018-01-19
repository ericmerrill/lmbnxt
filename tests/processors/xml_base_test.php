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

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

use enrol_lmb\local\processors\xml;
use enrol_lmb\local\response;
use enrol_lmb\local\exception;

class xml_base_testcase extends xml_helper {
    // TODO - This doesn't seem like the right testing here. This is more of node testing.
    public function test_base_mapping() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/testmapping.xml');

        $converter = new xml_tester();
        $node = $converter->process_xml_to_data($node);

        $this->assertEquals('V1', $node->node1);
        $this->assertEquals('CV2', $node->childnode);
        $this->assertEquals('V3', $node->node3);
        $this->assertFalse(isset($node->node4));
        $this->assertEquals('V5', $node->node5);
        $this->assertEquals('AV6', $node->node6);
        $this->assertInternalType('array', $node->node7);
        $this->assertCount(2, $node->node7);
        $this->assertEquals('V71', $node->node7[0]);
        $this->assertEquals('V72', $node->node7[1]);
        $this->assertFalse(isset($node->node9));
        $this->assertEquals('V102', $node->node10);
        $this->assertEquals('NC4', $node->node11c);
    }

    public function test_get_response_object() {
        $converter = new xml\group_term();

        $response = $converter->get_response_object();
        $this->assertInstanceOf(response\xml::class, $response);
    }

    public function test_process_field() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/person.xml');
        $converter = new \enrol_lmb\local\processors\xml\person();

        // Pass a bad mapping array.
        $this->run_protected_method($converter, 'process_field', array($node, []));
        $this->assertDebuggingCalled();

        $node = $this->get_node_for_xml('<replaceMembershipRequest>t</replaceMembershipRequest>');
        $mapping = array('lmbinternal' => 1, 'type' => 'bool', 'objname' => 'boolresult');

        try {
            $this->run_protected_method($converter, 'process_field', array($node, $mapping));
            $this->fail("Expected exception not thrown.");
        } catch (exception\message_exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Could not convert data', $ex->getMessage());
        }
    }
}
