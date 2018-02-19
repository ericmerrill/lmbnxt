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

class lis2_person_member_test extends xml_helper {
    public function test_person_member_teacher() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/parse/member_replace_teacher.xml');

        $converter = new lis2\membership();

        $member = $converter->process_xml_to_data($node);

        $this->assertInstanceOf(data\person_member::class, $member);

        $this->assertEmpty($member->membersdidsource);
        $this->assertEquals('1000001', $member->membersdid);
        $this->assertEquals('10001.201740', $member->groupsdid);

        $this->assertEquals('ILP', $member->referenceagent);
        $this->assertEquals('CM-editingteacher-CS10001.201740-1000001', $member->messagereference);

        $this->assertEquals('02', $member->roletype);
        $this->assertEquals(1, $member->status);

        $this->assertEquals('courseSection', $member->membershiptype);
        $this->assertEquals('editingteacher', $member->lis_roletype);
        $this->assertEquals('Active', $member->lis_status);

    }

    /**
     * Data provider for test_person_member_status.
     *
     * @return array
     */
    public function member_status_testcases() {
        return [
            'Active' => ['Active', 1],
            'Active (1)' => ['1', 1],
            'Active (true)' => ['true', 1],
            'Inactive' => ['Inactive', 0],
            'Inactive (0)' => ['0', 0],
            'Inactive (false)' => ['false', 0],
            'Unknown' => ['Unknown', false]
        ];
    }

    /**
     * Test parsing of member status.
     *
     * @dataProvider member_status_testcases
     * @param string $data The input status
     * @param int|false $expected The expected return value. False for exception.
     */
    public function test_person_member_status($data, $expected) {
        $converter = new lis2\person_member();

        $node = $this->get_node_for_xml('<replaceMembershipRequest><membershipRecord><membership><member><role><status>'.
                                        $data.
                                        '</status></role></member></membership></membershipRecord></replaceMembershipRequest>');
        $node->set_attribute('XMLNS', $converter::NAMESPACE_DEF);
        if ($expected === false) {
            // This means an exception is expected.
            try {
                $member = $converter->process_xml_to_data($node);
                $this->fail("Expected exception not thrown.");
            } catch (\Exception $ex) {
                $this->assertInstanceOf(exception\message_exception::class, $ex);
                $this->assertStringStartsWith('Membership status value "'.$data.'" unknown.', $ex->getMessage());
            }
        } else {
            $member = $converter->process_xml_to_data($node);
            $this->assertEquals($expected, $member->status);
            $this->assertEquals($data, $member->lis_status);
        }

    }

    /**
     * Data provider for test_person_member_roletype.
     *
     * @return array
     */
    public function member_roletype_testcases() {
        return [
            'editingteacher' => ['editingteacher', '02'],
            'student' => ['student', '01'],
            'Unknown' => ['Unknown', false]
        ];
    }

    /**
     * Test parsing of member role type.
     *
     * @dataProvider member_roletype_testcases
     * @param string $data The input status
     * @param int|false $expected The expected return value. False for exception.
     */
    public function test_person_member_roletype($data, $expected) {
        $converter = new lis2\person_member();

        $node = $this->get_node_for_xml('<replaceMembershipRequest><membershipRecord><membership><member><role><roleType>'.
                                        $data.
                                        '</roleType></role></member></membership></membershipRecord></replaceMembershipRequest>');
        $node->set_attribute('XMLNS', $converter::NAMESPACE_DEF);
        if ($expected === false) {
            // This means an exception is expected.
            try {
                $member = $converter->process_xml_to_data($node);
                $this->fail("Expected exception not thrown.");
            } catch (\Exception $ex) {
                $this->assertInstanceOf(exception\message_exception::class, $ex);
                $this->assertStringStartsWith('Membership role type value "'.$data.'" unknown.', $ex->getMessage());
            }
        } else {
            $member = $converter->process_xml_to_data($node);
            $this->assertEquals($expected, $member->roletype);
            $this->assertEquals($data, $member->lis_roletype);
        }

    }

    public function test_error_membership() {
        $node = $this->get_node_for_xml('<replaceMembershipRequest><membershipRecord><membership>'.
                                        '</membership></membershipRecord></replaceMembershipRequest>');

        $converter = new lis2\membership();

        try {
            $membership = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Membership type could not be found', $ex->getMessage());
        }

        $node = $this->get_node_for_xml('<replaceMembershipRequest><membershipRecord><membership><membershipIdType>Unknown'.
                                        '</membershipIdType></membership></membershipRecord></replaceMembershipRequest>');

        try {
            $group = $converter->process_xml_to_data($node);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(exception\message_exception::class, $ex);
            $this->assertStringStartsWith('Membership type could not be found', $ex->getMessage());
        }
    }

}
