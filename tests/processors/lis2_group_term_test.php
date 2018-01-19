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
use enrol_lmb\local\data;
use enrol_lmb\local\exception;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class lis2_group_term_test extends xml_helper {
    public function test_term_group() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/parse/term_replace.xml');

        $converter = new lis2\group();

        $term = $converter->process_xml_to_data($node);
        $this->assertInstanceOf(data\term::class, $term);

        $this->assertEmpty($term->sdidsource);
        $this->assertEquals('201740', $term->sdid);

        $this->assertEquals('ILP', $term->referenceagent);
        $this->assertEquals('201740', $term->messagereference);

        $this->assertEquals('1504051200', $term->begindate);
        $this->assertEquals('1513468800', $term->enddate);

        // TODO restrict settings.
        $this->assertEquals('0', $term->beginrestrict);
        $this->assertEquals('0', $term->endrestrict);

        $this->assertEquals('201740', $term->sortorder);

        $this->assertEquals('Fall Semester 2017', $term->description);
        $this->assertEquals('Short201740', $term->shortdescription);

    }

    public function test_error_group() {
        $converter = new lis2\group();

        $node = $this->get_node_for_xml('<replacegrouprequest><grouprecord><group><grouptype>'.
                                        '</grouptype></group></grouprecord></replacegrouprequest>');

        try {
            $group = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Group type not found', $ex->getMessage());
        }

        $node = $this->get_node_for_xml('<replacegrouprequest><grouprecord><group><grouptype><typevalue><id>Unknown</id>'.
                                        '</typevalue></grouptype></group></grouprecord></replacegrouprequest>');

        try {
            $group = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Group type not found', $ex->getMessage());
        }
    }
}
