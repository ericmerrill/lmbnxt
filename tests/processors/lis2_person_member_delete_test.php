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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\lis2;
use enrol_lmb\local\data;
use enrol_lmb\local\exception;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class lis2_person_member_delete_test extends xml_helper {
    public function test_person_member_teacher() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/parse/person_member_delete.xml');
        $converter = new lis2\person_member_delete();

        // First, there is no matching record in the DB, so we will see what it came up with.
        $member = $converter->process_xml_to_data($node);
        $this->assertFalse(isset($member->id));
        $this->assertInstanceOf(data\person_member::class, $member);
        $this->assertEquals('CM-editingteacher-CS10001.201740-1000001', $member->messagereference);
        $this->assertEquals('editingteacher', $member->lis_roletype);
        $this->assertEquals('10001.201740', $member->groupsdid);
        $this->assertEquals('1000001', $member->membersdid);
        $this->assertEquals(0, $member->status);
        $this->assertEquals('Inactive', $member->lis_status);

        // Make sure there is no error saving to the DB.
        $member->save_to_db();
        $this->assertTrue(isset($member->id));
        $memberid = $member->id;

        // Process again to make sure that we get the record back again, found by message ref.
        // Change the sdid, so we know it can't be found that way.
        $member->membersdid = '101';
        $member->save_to_db();

        $member = $converter->process_xml_to_data($node);
        $this->assertEquals($memberid, $member->id);

        // Now clear the messageref and set usersdid in the DB to make sure we find it without it, and it gets reset.
        unset($member->messagereference);
        $member->membersdid = '1000001';
        $member->save_to_db();

        $member = $converter->process_xml_to_data($node);
        $this->assertEquals($memberid, $member->id);

        // Now try invalid message reference.
        $node->SOURCEDID->set_data('SomethingBad');
        $member = $converter->process_xml_to_data($node);
        $this->assertFalse($member);

        // Now try with an missing message reference entirely.
        unset($node->SOURCEDID);
        $member = $converter->process_xml_to_data($node);
        $this->assertFalse($member);

    }


}
