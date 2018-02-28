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
 * Tests for the moodle converter model.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\lis2;
use enrol_lmb\local\processors\xml;
use enrol_lmb\local\data;
use enrol_lmb\local\moodle;
use enrol_lmb\settings;
use enrol_lmb\logging;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class moodle_enrolment_testcase extends xml_helper {
    /**
     * Data provider for test_convert_to_moodle.
     *
     * @return array
     */
    public function convert_to_moodle_testcases() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/member_replace_teacher.xml');
        $converter = new lis2\person_member();
        $member1 = $converter->process_xml_to_data($node);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/person_member.xml');
        $converter = new xml\membership();
        $member2 = $converter->process_xml_to_data($node);

        $output = ['lis2' => [$member1],
                   'xml'  => [$member2[0]]];

        return $output;
    }

    /**
     * Test that two identical enrolments are made from LIS1-XML and LIS2 content.
     *
     * @dataProvider convert_to_moodle_testcases
     * @param data\person_member $section The input section
     */
    public function test_convert_to_moodle($member) {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $moodleenrol = new moodle\enrolment();

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NOTICE);

        // Test with a bad data object.
        $baddata = new data\person();
        try {
            $moodleenrol->convert_to_moodle($baddata);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertContains('Expected instance of data\person_member to be passed', $ex->getMessage());
        }

        // First no user or course.
        $moodleenrol->convert_to_moodle($member);
        $error = 'WARNING: Moodle user could not be found.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        $user = $this->getDataGenerator()->create_user(['idnumber' => '1000001']);

        // Now no course.
        $moodleenrol->convert_to_moodle($member);
        $error = 'WARNING: Moodle course could not be found.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        $course = $this->getDataGenerator()->create_course(['idnumber' => '10001.201740']);
        $context = context_course::instance($course->id);

        // Now everything is made. Lets test an unknown roletype.
        $roletype = $member->roletype;
        $member->roletype = '10';
        $moodleenrol->convert_to_moodle($member);
        $error = 'NOTICE: No role mapping found for 10.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        $member->roletype = $roletype;

        // Make sure there aren't any enrolments for the user.
        $this->assertFalse(is_enrolled($context, $user));
        $this->assertCount(0, get_user_roles($context, $user->id));

        // Now trying to enrol for real.
        $moodleenrol->convert_to_moodle($member);
        $error = 'Enrolling user';
        $this->assertContains($error, $log->test_get_flush_buffer());

        $this->assertTrue(is_enrolled($context, $user));
        $this->assertCount(1, get_user_roles($context, $user->id));

        // And now unenrol.
        $member->status = 0;

        $moodleenrol->convert_to_moodle($member);
        $error = 'Unenrolling user';
        $this->assertContains($error, $log->test_get_flush_buffer());

        $this->assertFalse(is_enrolled($context, $user));
        $this->assertCount(0, get_user_roles($context, $user->id));
    }

    public function test_get_moodle_role_id() {
        $moodleenrol = new moodle\enrolment();

        $enrol = new data\person_member();
        $moodleenrol->set_data($enrol);

        // Unset the setting to make sure we get a default.
        settings_helper::temp_set('imsrolemap01', null);
        $enrol->roletype = '01';
        $result = $this->run_protected_method($moodleenrol, 'get_moodle_role_id');

        $roles = get_archetype_roles('student');
        $role = reset($roles);
        $roleid = $role->id;
        $this->assertEquals($roleid, $result);

        // Now set an ID.
        settings_helper::temp_set('imsrolemap01', $roleid+1);
        $result = $this->run_protected_method($moodleenrol, 'get_moodle_role_id');
        $this->assertEquals($roleid+1, $result);

        // Now an unknown roletype.
        $enrol->roletype = '10';
        $result = $this->run_protected_method($moodleenrol, 'get_moodle_role_id');
        $this->assertFalse($result);
    }

    public function test_get_default_role_id() {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertFalse(moodle\enrolment::get_default_role_id('10'));

        $roles = get_archetype_roles('editingteacher');
        $role = reset($roles);
        $roleid = $role->id;

        $result = moodle\enrolment::get_default_role_id('02');
        $this->assertEquals($roleid, $result);

        $roles = get_archetype_roles('student');
        $role = reset($roles);
        $roleid = $role->id;

        $result = moodle\enrolment::get_default_role_id('01');
        $this->assertEquals($roleid, $result);
        $result = moodle\enrolment::get_default_role_id('03');
        $this->assertEquals($roleid, $result);
        $result = moodle\enrolment::get_default_role_id('04');
        $this->assertEquals($roleid, $result);
        $result = moodle\enrolment::get_default_role_id('05');
        $this->assertEquals($roleid, $result);

        // Now for one that can't be found.
        $DB->delete_records('role');
        $this->assertFalse(moodle\enrolment::get_default_role_id('01'));
    }
}
