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

use enrol_lmb\local\exception;
use enrol_lmb\local\processors\xml;
use enrol_lmb\local\data;
use enrol_lmb\logging;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class data_member_person_testcase extends xml_helper {
    public function test_log_id() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/member_person.xml');
        $converter = new xml\membership();
        $members = $converter->process_xml_to_data($node);

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NONE);

        foreach ($members as $member) {
            $member->log_id();

            $this->assertRegExp("|{$member->sdid}.*{$member->sdidsource}.*{$member->groupsdid}.*{$member->groupsdidsource}|",
                    $log->test_get_flush_buffer());

            unset($member->sdid);

            try {
                $member->log_id();
                $this->fail('message_exception expected');
            } catch (exception\message_exception $ex) {
                $this->assertInstanceOf(exception\message_exception::class, $ex);
                $expected = get_string('exception_bad_member_person', 'enrol_lmb');
                $this->assertRegExp("|{$expected}|", $ex->getMessage());
            }
        }
    }

    public function test_db_save() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/member_person.xml');
        $converter = new xml\membership();
        $members = $converter->process_xml_to_data($node);

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NONE);

        foreach ($members as $member) {
            // First insert.
            $member->save_to_db();
            $this->assertRegExp("|Inserting into database|", $log->test_get_flush_buffer());

            // Try to save the same object again.
            $member->save_to_db();
            $this->assertRegExp("|No database update needed|", $log->test_get_flush_buffer());

            // Modify the course and try and insert again.
            $member->status = (int)!$member->status;
            $member->save_to_db();
            $this->assertRegExp("|Updated database record|", $log->test_get_flush_buffer());

            // Now lets get it from the DB and check it.
            $params = array('sdid' => $member->sdid, 'sdidsource' => $member->sdidsource,
                    'groupsdid' => $member->groupsdid, 'groupsdidsource' => $member->groupsdidsource);
            $dbrecord = $DB->get_record(data\member_person::TABLE, $params);

            $this->assertNotEmpty($dbrecord);

            foreach ($dbrecord as $key => $value) {
                if ($key == 'timemodified') {
                    // Skip special case.
                    continue;
                }
                $this->assertEquals($member->$key, $value, "Key {$key} did not match");
            }
        }
    }
}
