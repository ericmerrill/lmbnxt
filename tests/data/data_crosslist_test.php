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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\exception;
use enrol_lmb\local\processors\xml;
use enrol_lmb\local\data\crosslist;
use enrol_lmb\local\data\crosslist_member;
use enrol_lmb\logging;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class data_crosslist_testcase extends xml_helper {
    public function test_log_id() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/member_group.xml');
        $converter = new xml\membership();
        $crosslist = $converter->process_xml_to_data($node);

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NONE);

        $crosslist->log_id();
        $regex = "|{$crosslist->sdid}.*{$crosslist->sdidsource}|";
        $result = $log->test_get_flush_buffer();
        $this->assertRegExp($regex, $result);

        $members = $crosslist->get_members();
        foreach ($members as $member) {
            $regex = "|{$member->sdid}.*{$member->sdidsource}|";
            $this->assertRegExp($regex, $result);
        }

        unset($crosslist->sdid);

        try {
            $crosslist->log_id();
            $this->fail('message_exception expected');
        } catch (exception\message_exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $expected = get_string('exception_bad_crosslist_id', 'enrol_lmb');
            $this->assertRegExp("|{$expected}|", $ex->getMessage());
        }

    }

    public function test_save_to_db() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/member_group.xml');
        $converter = new xml\membership();
        $crosslist = $converter->process_xml_to_data($node);

        $crosslist->save_to_db();

        $crossdb = $DB->get_record(crosslist::TABLE, ['id' => $crosslist->id]);
        $this->assertInstanceOf(\stdClass::class, $crossdb);
        $this->assertEquals('XLSAA201740', $crossdb->sdid);

        $members = $DB->get_records(crosslist_member::TABLE, ['crosslistid' => $crosslist->id]);
        $this->assertCount(2, $members);

        // Now lets do it again and make sure there is no error.
        $converter = new xml\membership();
        $crosslist = $converter->process_xml_to_data($node);

        $crosslist->save_to_db();

        $members = $DB->get_records(crosslist_member::TABLE, ['crosslistid' => $crosslist->id]);
        $this->assertCount(2, $members);
    }

    public function test_db_save() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/member_group.xml');
        $converter = new xml\membership();
        $crosslist = $converter->process_xml_to_data($node);

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NONE);


            // First insert.
            $crosslist->save_to_db();
            $output = $log->test_get_flush_buffer();
            $count = substr_count($output, 'Inserting into database');
            $this->assertEquals(3, $count);

            // Try to save the same object again.
            $crosslist->save_to_db();
            $output = $log->test_get_flush_buffer();
            $count = substr_count($output, 'No database update needed');
            $this->assertEquals(3, $count);

            // Now lets see if the message time needs updating.
            $DB->set_field(crosslist::TABLE, 'messagetime', time() - 10, ['id' => $crosslist->id]);
            $DB->set_field(crosslist_member::TABLE, 'messagetime', time() - 10, ['crosslistid' => $crosslist->id]);
            $crosslist = $converter->process_xml_to_data($node);
            $crosslist->save_to_db();
            $output = $log->test_get_flush_buffer();
//             print "<pre>";var_export($output);print "</pre>";
//             $count = substr_count($output, 'Only messagetime updated');
//             $this->assertEquals(3, $count);

            // Modify the course and try and insert again.
            $crosslist->status = 0;
            $crosslist->save_to_db();
            $output = $log->test_get_flush_buffer();
            // TODO - Work this out...
//             print "<pre>";var_export($output);print "</pre>";
//             $count = substr_count($output, 'Updated database record');
//             $this->assertEquals(1, $count);
//             $count = substr_count($output, 'No database update needed');
//             $this->assertEquals(2, $count);

            // Now lets get it from the DB and check it.
//             $params = array('membersdid' => $crosslist->membersdid, 'membersdidsource' => $crosslist->membersdidsource,
//                     'groupsdid' => $crosslist->groupsdid, 'groupsdidsource' => $crosslist->groupsdidsource);
//             $dbrecord = $DB->get_record(crosslist::TABLE, $params);
//
//             $this->assertNotEmpty($dbrecord);
//
//             foreach ($dbrecord as $key => $value) {
//                 if ($key == 'timemodified') {
//                     // Skip special case.
//                     continue;
//                 }
//                 $this->assertEquals($crosslist->$key, $value, "Key {$key} did not match");
//             }

    }

    public function test_handler_group_type() {
        $this->resetAfterTest(true);

        settings_helper::temp_set('xlstype', crosslist::GROUP_TYPE_MERGE);
        $crosslist = new crosslist();

        $result = $this->run_protected_method($crosslist, 'handler_group_type', array('', crosslist::GROUP_TYPE_META));
        $this->assertEquals(crosslist::GROUP_TYPE_META, $result);

        $result = $this->run_protected_method($crosslist, 'handler_group_type', array('', 'mEtA'));
        $this->assertEquals(crosslist::GROUP_TYPE_META, $result);

        $result = $this->run_protected_method($crosslist, 'handler_group_type', array('', 'Unknown'));
        $this->assertEquals(crosslist::GROUP_TYPE_MERGE, $result);

        settings_helper::temp_set('xlstype', crosslist::GROUP_TYPE_META);

        $result = $this->run_protected_method($crosslist, 'handler_group_type', array('', crosslist::GROUP_TYPE_MERGE));
        $this->assertEquals(crosslist::GROUP_TYPE_MERGE, $result);

        $result = $this->run_protected_method($crosslist, 'handler_group_type', array('', 'MeRgE'));
        $this->assertEquals(crosslist::GROUP_TYPE_MERGE, $result);

        $result = $this->run_protected_method($crosslist, 'handler_group_type', array('', 'Unknown'));
        $this->assertEquals(crosslist::GROUP_TYPE_META, $result);
    }

    public function test_default_overwrite() {
        global $DB;

        $this->resetAfterTest(true);
        settings_helper::temp_set('xlstype', crosslist::GROUP_TYPE_MERGE);

        // Start with the default value.
        $crosslist = new crosslist();
        $crosslist->sdid = 'XLSAB201740';
        $crosslist->sdidsource = 'Banner';
        $crosslist->save_to_db();

        $record = $DB->get_record(crosslist::TABLE, ['id' => $crosslist->id]);
        $this->assertEquals(crosslist::GROUP_TYPE_MERGE, (int)$record->type);

        // Now try to update that with a new value.
        $crosslist = new crosslist();
        $crosslist->sdid = 'XLSAB201740';
        $crosslist->sdidsource = 'Banner';
        $crosslist->type = 'meta';
        $crosslist->merge_existing();
        $crosslist->save_to_db();

        $record = $DB->get_record(crosslist::TABLE, ['id' => $crosslist->id]);
        $this->assertEquals(crosslist::GROUP_TYPE_META, (int)$record->type);

        // Now make sure it doesn't get overwritten by a blank new data.
        $crosslist = new crosslist();
        $crosslist->sdid = 'XLSAB201740';
        $crosslist->sdidsource = 'Banner';
        $crosslist->merge_existing();
        $crosslist->save_to_db();

        $record = $DB->get_record(crosslist::TABLE, ['id' => $crosslist->id]);
        $this->assertEquals(crosslist::GROUP_TYPE_META, (int)$record->type);
    }
}
