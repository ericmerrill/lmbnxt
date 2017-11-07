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

use enrol_lmb\local\processors\xml;
use enrol_lmb\local\exception;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class xml_membership_test extends xml_helper {
    /**
     * Check some error handling in the membership determiner.
     */
    public function test_process_xml_to_data_errors() {
        $converter = new xml\membership();

        $node = $this->get_node_for_xml('<membership></membership>');

        try {
            $membership = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Membership has no members', $ex->getMessage());
        }

        $node = $this->get_node_for_xml('<membership><member></member></membership>');

        try {
            $membership = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Membership group has no source or id', $ex->getMessage());
        }

        $node = $this->get_node_for_xml('<membership><sourcedid><source></source><id></id></sourcedid>'.
                                        '<member></member></membership>');

        $membership = $converter->process_xml_to_data($node);

        $this->assertInternalType('array', $membership);
        $this->assertEmpty($membership);
    }
}
