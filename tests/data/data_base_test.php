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
 * Tests for the data model.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class data_base_testcase extends xml_helper {
    public function test_unset() {
        $obj = new data_test();

        $obj->dbkey = 'DB Key';
        $obj->nondbkey = 'NonDB Key';

        $this->assertTrue(isset($obj->dbkey));
        $this->assertEquals('DB Key', $obj->dbkey);
        $this->assertTrue(isset($obj->nondbkey));
        $this->assertEquals('NonDB Key', $obj->nondbkey);

        unset($obj->dbkey);
        $this->assertFalse(isset($obj->dbkey));

        unset($obj->nondbkey);
        $this->assertFalse(isset($obj->nondbkey));
    }

    public function test_handler() {
        $obj = new data_test();

        $obj->single = 2;
        $obj->double = 2;

        $this->assertEquals(2, $obj->single);
        $this->assertEquals(4, $obj->double);
    }

    public function test_handler_boolean() {
        $obj = new data_test();

        $obj->boolean = true;
        $this->assertEquals(1, $obj->boolean);

        $obj->boolean = false;
        $this->assertEquals(0, $obj->boolean);

        $obj->boolean = "something";
        $this->assertEquals(1, $obj->boolean);

        $obj->boolean = "";
        $this->assertEquals(0, $obj->boolean);
    }

    public function test_handler_date() {
        $obj = new data_test();

        $obj->date = "2016-01-10";
        $this->assertEquals(1452384000, $obj->date);

        $log = new logging_helper();
        $log->set_logging_level(\enrol_lmb\logging::ERROR_NONE);

        $obj->date = "1552384000";
        $this->assertEquals(1552384000, $obj->date);

        $obj->date = 1552384000;
        $this->assertEquals(1552384000, $obj->date);

        $obj->date = "2016-45-10";
        $this->assertEquals(0, $obj->date);
        $this->assertRegExp("|WARNING: |", $log->test_get_flush_buffer());
    }

    public function test_get() {
        $obj = new data_test();

        $obj->nondbkey = "non db value";
        $this->assertRegExp("|nondbkey.*non db value|", $obj->additional);

        $this->assertEquals('Default value', $obj->defkey);

        $this->assertNull($obj->nondefkey);

        $this->assertEquals('Additional key default', $obj->addkey);
    }
}
