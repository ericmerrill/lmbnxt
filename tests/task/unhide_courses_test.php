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
 * Tests for the upgrade lib.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\data;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/upgradelib.php');
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class upgradelib_test extends xml_helper {

    public function test_enrol_lmb_upgrade_promote_column() {
        global $DB;

        $this->resetAfterTest(true);

        $record1 = new stdClass();
        $record1->timemodified = time();
        $record1->givenname = 'First1';
        $record1->sdid = '1';
        $record1->id = $DB->insert_record(data\person::TABLE, $record1);

        $record2 = new stdClass();
        $record2->timemodified = time();
        $record2->sdid = '2';
        $record2->givenname = 'First2';
        $additional = new stdClass();
        $additional->primaryrole = 'Staff';
        $record2->additional = json_encode($additional, JSON_UNESCAPED_UNICODE);
        $record2->id = $DB->insert_record(data\person::TABLE, $record2);

        $record3 = new stdClass();
        $record3->timemodified = time();
        $record3->sdid = '3';
        $record3->givenname = 'First3';
        $additional = new stdClass();
        $additional->primaryrole = 'Bob!!';
        $additional->otherkey = 'Something';
        $record3->additional = json_encode($additional, JSON_UNESCAPED_UNICODE);
        $record3->id = $DB->insert_record(data\person::TABLE, $record3);

        // This should cause a dml_exception, which should be cleanly caught.
        $long = "abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefgh";
        $record4 = new stdClass();
        $record4->timemodified = time();
        $record4->sdid = '4';
        $record4->givenname = 'First4';
        $additional = new stdClass();
        $additional->primaryrole = $long;
        $record4->additional = json_encode($additional, JSON_UNESCAPED_UNICODE);
        $record4->id = $DB->insert_record(data\person::TABLE, $record4);

        enrol_lmb_upgrade_promote_column(data\person::TABLE, 'primaryrole');

        $record = $DB->get_record(data\person::TABLE, ['id' => $record1->id]);
        $this->assertEquals('First1', $record->givenname);
        $this->assertEquals('1', $record->sdid);
        $this->assertNull($record->primaryrole);

        $record = $DB->get_record(data\person::TABLE, ['id' => $record2->id]);
        $this->assertEquals('First2', $record->givenname);
        $this->assertEquals('2', $record->sdid);
        $this->assertEquals('Staff', $record->primaryrole);
        $additional = json_decode($record->additional);
        $this->assertCount(0, (array)$additional);

        $record = $DB->get_record(data\person::TABLE, ['id' => $record3->id]);
        $this->assertEquals('First3', $record->givenname);
        $this->assertEquals('3', $record->sdid);
        $this->assertEquals('Bob!!', $record->primaryrole);
        $additional = json_decode($record->additional);
        $this->assertCount(1, (array)$additional);
        $this->assertEquals('Something', $additional->otherkey);

        $record = $DB->get_record(data\person::TABLE, ['id' => $record4->id]);
        $this->assertEquals('First4', $record->givenname);
        $this->assertEquals('4', $record->sdid);
        $this->assertNull($record->primaryrole);
        $additional = json_decode($record->additional);
        $this->assertCount(1, (array)$additional);
        $this->assertEquals($long, $additional->primaryrole);
    }

}
