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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/lib.php');
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class enrol_lmb_plugin_test extends xml_helper {

    public function test_get_instance() {
        global $DB;

        $this->resetAfterTest(true);

        $enrol = new enrol_lmb_plugin();

        $this->resetDebugging();
        $this->assertFalse($enrol->get_instance(false));
        $this->assertDebuggingCalled("Expected stdClass or int passed to enrol_lmb_plugin->get_instance().");

        $course = $this->getDataGenerator()->create_course();

        // Try to make it with an invalid course id.
        $this->assertFalse($enrol->get_instance($course->id + 10));

        // Now try to make it with a valid course id.
        $result = $enrol->get_instance($course->id);
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertNotEmpty($result->id);
        $this->assertEquals('lmb', $result->enrol);

        // Now try again with the object, and make sure we get the same result back.
        $result2 = $enrol->get_instance($course);
        $this->assertInstanceOf(stdClass::class, $result2);
        $this->assertNotEmpty($result2->id);
        $this->assertEquals($result->id, $result2->id);

        // And finally, we are going to make it again (a new one) with the object instead of id.
        $DB->delete_records('enrol', ['id' => $result->id]);
        $result2 = $enrol->get_instance($course);
        $this->assertInstanceOf(stdClass::class, $result2);
        $this->assertNotEmpty($result2->id);
        $this->assertEquals('lmb', $result2->enrol);
        $this->assertNotEquals($result->id, $result2->id);
    }

}
