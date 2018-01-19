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

use enrol_lmb\local\processors\types;
use enrol_lmb\local\processors\xml;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class xml_types_test extends xml_helper {
    public function test_get_types() {
        $types = types::get_types();

        $this->assertInternalType('array', $types);

        foreach ($types as $key => $type) {
            $this->assertInternalType('integer', $key);
            $this->assertInternalType('string', $type);
        }
    }

    public function test_get_type_processor() {
        // Check a not set type.
        $result = types::get_type_processor('unknown');
        $this->assertFalse($result);

        // Now a person processor.
        $personprocessor1 = types::get_type_processor('person');
        $this->assertInstanceOf(xml\person::class, $personprocessor1);

        // Now the same thing, but with all caps.
        $personprocessor2 = types::get_type_processor('PERSON');
        $this->assertInstanceOf(xml\person::class, $personprocessor2);

        // Should be the same object.
        $this->assertEquals($personprocessor1, $personprocessor2);
    }

    public function test_get_processor_for_class() {
        $personprocessor1 = types::get_processor_for_class(xml\person::class);
        $this->assertInstanceOf(xml\person::class, $personprocessor1);

        $groupprocessor = types::get_processor_for_class(xml\group::class);
        $this->assertInstanceOf(xml\group::class, $groupprocessor);

        // Now make sure we get the same one back.
        $personprocessor2 = types::get_processor_for_class(xml\person::class);
        $this->assertInstanceOf(xml\person::class, $personprocessor2);
        $this->assertEquals($personprocessor1, $personprocessor2);
    }
}
