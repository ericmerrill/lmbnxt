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
 * Tests for the LIS xml parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\lis2;
use enrol_lmb\local\response;
use enrol_lmb\local\exception;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class lis2_base_test extends xml_helper {
    public function test_check_lis_namespace() {
        $node = $this->get_node_for_xml('<replaceMembershipRequest></replaceMembershipRequest>');

        // First try with a undefined namespace.
        $converter = new lis2\base();
        try {
            $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertContains('NAMESPACE_DEF must be defined', $ex->getMessage());
        }

        // Now try a good converter, but we don't have a namespace defined in the node.
        $converter = new lis2\group_term();
        try {
            $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertContains('LIS message namespace incorrect', $ex->getMessage());
        }

        // Now an unknown namespace.
        $node->set_attribute('XMLNS', 'Unknown');
        try {
            $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertContains('LIS message namespace incorrect', $ex->getMessage());
        }
    }

    public function test_get_response_object() {
        // First with an empty namespace.
        $converter = new lis2\base();

        $response = $converter->get_response_object();
        $this->assertInstanceOf(response\lis2::class, $response);
        $this->assertAttributeEmpty('namespace', $response);

        // Now one with a namespace.
        $converter = new lis2\group_term();

        $response = $converter->get_response_object();
        $this->assertInstanceOf(response\lis2::class, $response);
        $this->assertAttributeEquals(lis2\group_term::NAMESPACE_DEF, 'namespace', $response);
    }
}
