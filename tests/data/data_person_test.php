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

class data_person_testcase extends xml_helper {
    public function test_log_id() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/person.xml');
        $converter = new \enrol_lmb\local\processors\xml\person();
        $person = $converter->process_xml_to_data($node);

        $log = new logging_helper();
        $log->set_logging_level(\enrol_lmb\logging::ERROR_NONE);

        $person->log_id();

        $this->assertRegExp("|{$person->sdid}.*{$person->sdidsource}|", $log->test_get_flush_buffer());

        unset($person->sdid);

        try {
            $person->log_id();
            $this->fail('message_exception expected');
        } catch (\enrol_lmb\local\exception\message_exception $ex) {
            $this->assertInstanceOf('\\enrol_lmb\\local\\exception\\message_exception', $ex);
            $expected = get_string('exception_bad_person', 'enrol_lmb');
            $this->assertRegExp("|{$expected}|", $ex->getMessage());
        }
    }

    public function test_db_save() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/person.xml');
        $converter = new \enrol_lmb\local\processors\xml\person();
        $person = $converter->process_xml_to_data($node);

        $log = new logging_helper();
        $log->set_logging_level(\enrol_lmb\logging::ERROR_NONE);

        // First insert.
        $person->save_to_db();
        $this->assertRegExp("|Inserting into database|", $log->test_get_flush_buffer());

        // Try to save the same object again.
        $person->save_to_db();
        $this->assertRegExp("|No database update needed|", $log->test_get_flush_buffer());

        // Modify the person and try and insert again.
        $person->firstname = 'first';
        $person->save_to_db();
        $this->assertRegExp("|Updated database record|", $log->test_get_flush_buffer());

        // Now lets get it from the DB and check it.
        $params = array('sdid' => $person->sdid, 'sdidsource' => $person->sdidsource);
        $dbrecord = $DB->get_record(\enrol_lmb\local\data\person::TABLE, $params);
        $this->assertNotEmpty($dbrecord);

        foreach ($dbrecord as $key => $value) {
            if ($key == 'timemodified') {
                // Skip special case.
                continue;
            }
            $this->assertEquals($person->$key, $value, "Key {$key} did not match");
        }
    }
}
