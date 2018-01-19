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
use enrol_lmb\local\data;
use enrol_lmb\local\exception;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class xml_group_testcase extends xml_helper {
    public function test_term_group() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/term.xml');

        $converter = new xml\group();

        $term = $converter->process_xml_to_data($node);
        $this->assertInstanceOf(data\term::class, $term);
    }

    public function test_error_groups() {
        $node = $this->get_node_for_xml('<group><sourcedid><source>Test SCT Banner</source><id>201640</id></sourcedid></group>');

        $converter = new xml\group();

        try {
            $group = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Group type not found', $ex->getMessage());
        }

        $node = $this->get_node_for_xml('<group><grouptype><scheme>Luminis</scheme><typevalue level="1">Unknown</typevalue>'.
                                        '</grouptype></group>');

        try {
            $group = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Group type not found', $ex->getMessage());
        }
    }
}
