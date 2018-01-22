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

class moodle_user_testcase extends xml_helper {

    public function test_get_username() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/replace_person.xml');
        $converter = new lis2\person();
        $person = $converter->process_xml_to_data($node);

        $moodleuser = new moodle\user();
        $moodleuser->set_data($person);

        settings_helper::set('usernamesource', settings::USER_NAME_EMAIL);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('testuser@example.com', $result);

        settings_helper::set('usernamesource', settings::USER_NAME_EMAILNAME);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('testuser', $result);

        settings_helper::set('usernamesource', settings::USER_NAME_LOGONID);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('logoniduserid', $result);

        settings_helper::set('usernamesource', settings::USER_NAME_SCTID);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals(strtolower($person->sctid), $result);

        settings_helper::set('usernamesource', settings::USER_NAME_EMAILID);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('emailuserid', $result);

        settings_helper::set('usernamesource', settings::USER_NAME_OTHER);
        // Setting usernamesource isn't set, so expect false.
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertFalse($result);

        settings_helper::set('otheruserid', 'Other ID');
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('otheruserid', $result);
    }

    public function test_create_new_user_object() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/replace_person.xml');
        $converter = new lis2\person();
        $person = $converter->process_xml_to_data($node);

        $moodleuser = new moodle\user();
        $moodleuser->set_data($person);

        $result = $this->run_protected_method($moodleuser, 'create_new_user_object');
        $this->assertInstanceOf(\stdClass::class, $result);

        // Right now this has no items, so adding this as a reminder if we add stuff.
        $this->assertCount(0, (array)$result);
    }

    /**
     * Data provider for test_convert_to_moodle.
     *
     * @return array
     */
    public function convert_to_moodle_testcases() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/replace_person.xml');
        $converter = new lis2\person();
        $person1 = $converter->process_xml_to_data($node);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/person.xml');
        $converter = new xml\person();
        $person2 = $converter->process_xml_to_data($node);

        $output = ['lis2' => [$person1],
                   'xml'  => [$person2]];

        return $output;
    }

    /**
     * Test that two identical courses are made from LIS1-XML and LIS2 content.
     *
     * @dataProvider convert_to_moodle_testcases
     * @param data\person $section The input section
     */
    public function test_convert_to_moodle($person) {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $moodleuser = new moodle\user();

        // Test with a bad data object.
        $baddata = new data\section();
        try {
            $moodleuser->convert_to_moodle($baddata);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertContains('Expected instance of data\person to be passed', $ex->getMessage());
        }

        // Now try with creating of new users disabled.
        settings_helper::set('createnewusers', 0);

        $moodleuser->convert_to_moodle($person);

        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertFalse($dbuser);

        settings_helper::set('createnewusers', 1);

        $moodleuser->convert_to_moodle($person);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertInstanceOf(\stdClass::class, $dbuser);

        $this->assertEquals('testuser', $dbuser->username);

        $this->assertEquals('testUser@eXample.com', $dbuser->email);


        // Some additional tests.
        // Check with an empty email.
//         unset($person->email);
//
//         $log = new logging_helper();
//         $log->set_logging_level(logging::ERROR_NOTICE);
//
//
//         // First check that we don't force email.
//         settings_helper::set('forceemail', 0);
//         $moodleuser->convert_to_moodle($person);
//         $this->assertContains("WARNING: User has no username with current settings. Keeping testuser", $log->test_get_flush_buffer());
//         $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
//         $this->assertEquals('testUser@eXample.com', $dbuser->email);
//
//         // Now check that we do force, and it is an empty string.
//
//
//         settings_helper::set('forceemail', 1);
//         $moodleuser->convert_to_moodle($person);
//         $this->assertContains("WARNING: User has no username with current settings. Keeping testuser", $log->test_get_flush_buffer());
//         $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
//         $this->assertEquals('', $dbuser->email);



//print "<pre>";var_export($DB->get_record('user', array('idnumber')));print "</pre>";

//         settings_helper::set('coursetitle', '[CRN]:[TERM]');
//         settings_helper::set('forcetitle', 0);
//         settings_helper::set('courseshorttitle', '[DEPT][NUM]');
//         settings_helper::set('forceshorttitle', 0);
//         settings_helper::set('computesections', 0);
//         settings_helper::set('forcecomputesections', 0);
//
//         data_test::set_value($section, 'begindate', 0);
//
//         $moodlecourse->convert_to_moodle($section);
//
//         $dbcourse = $DB->get_record('course', array('idnumber' => '10001.201740'), '*', MUST_EXIST);
//         $this->assertInstanceOf(\stdClass::class, $dbcourse);
//
//         $this->assertEquals('10001:201740', $dbcourse->fullname);
//         $this->assertEquals('ENG101', $dbcourse->shortname);
//
//         $this->assertEquals(0, $dbcourse->startdate);
//         $this->assertEquals(0, $dbcourse->enddate);
//
//         $sections = $DB->count_records('course_sections', array('course' => $dbcourse->id));
//         $sections -= 1; // Remove 1 to account for the general section.
//         $this->assertEquals(get_config('moodlecourse', 'numsections'), $sections);
//
//         // Now we are going to change some things in the DB, to make sure they don't get overwritten.
//         $dbcourse->fullname = 'Full name';
//         $dbcourse->shortname = 'Short name';
//         $DB->update_record('course', $dbcourse);
//
//         settings_helper::set('computesections', 1);
//
//         data_test::set_value($section, 'begindate', 1504224000);
//         data_test::set_value($section, 'enddate', 0);
//
//         // Now run an update make sure things don't force change.
//         $moodlecourse->convert_to_moodle($section);
//
//         $dbcourse = $DB->get_record('course', array('idnumber' => '10001.201740'), '*', MUST_EXIST);
//         $this->assertInstanceOf(\stdClass::class, $dbcourse);
//
//         $this->assertEquals('Full name', $dbcourse->fullname);
//         $this->assertEquals('Short name', $dbcourse->shortname);
//
//         // Dates currently always get overwritten.
//         $this->assertEquals(1504224000, $dbcourse->startdate);
//         $this->assertEquals(0, $dbcourse->enddate);
//
//         $sections = $DB->count_records('course_sections', array('course' => $dbcourse->id));
//         $sections -= 1; // Remove 1 to account for the general section.
//         $this->assertEquals(get_config('moodlecourse', 'numsections'), $sections);
//
//         // Now turn on forcing and try again.
//         settings_helper::set('forcetitle', 1);
//         settings_helper::set('forceshorttitle', 1);
//         settings_helper::set('forcecomputesections', 1);
//
//         data_test::set_value($section, 'enddate', 1514678400);
//
//         $moodlecourse->convert_to_moodle($section);
//
//         $dbcourse = $DB->get_record('course', array('idnumber' => '10001.201740'), '*', MUST_EXIST);
//         $this->assertInstanceOf(\stdClass::class, $dbcourse);
//
//         $this->assertEquals('10001:201740', $dbcourse->fullname);
//         $this->assertEquals('ENG101', $dbcourse->shortname);
//
//         $this->assertEquals(1504224000, $dbcourse->startdate);
//         $this->assertEquals(1514678400, $dbcourse->enddate);
//
//         $sections = $DB->count_records('course_sections', array('course' => $dbcourse->id));
//         $sections -= 1; // Remove 1 to account for the general section.
//         $this->assertEquals(18, $sections);
    }

    /**
     * Test fpr empty username and email address cases.
     *
     * @dataProvider convert_to_moodle_testcases
     * @param data\person $section The input section
     */
    public function test_empty_email_and_username($person) {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $moodleuser = new moodle\user();

        unset($person->email);

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NOTICE);

        settings_helper::set('createnewusers', 1);
        settings_helper::set('usernamesource', settings::USER_NAME_EMAILNAME);

        // First, try to make a user with no user.
        $moodleuser->convert_to_moodle($person);
        $error = "NOTICE: No username could be determined for user.";
        $this->assertContains($error, $log->test_get_flush_buffer());
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertFalse($dbuser);

        // Now no email address, but with username.
        settings_helper::set('usernamesource', settings::USER_NAME_LOGONID);
        $moodleuser->convert_to_moodle($person);
        $buffer = $log->test_get_flush_buffer();
        $this->assertNotContains('NOTICE', $buffer);
        $this->assertNotContains('WARNING', $buffer);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertInstanceOf(\stdClass::class, $dbuser);
        $this->assertEquals('logoniduserid', $dbuser->username);
        $this->assertEquals('', $dbuser->email);

        // Now with an email address, but don't force.
        $person->email = 'testUser@eXample.com';
        settings_helper::set('forceemail', 0);
        $moodleuser->convert_to_moodle($person);
        $buffer = $log->test_get_flush_buffer();
        $this->assertNotContains('NOTICE', $buffer);
        $this->assertNotContains('WARNING', $buffer);
        //$this->assertContains("WARNING: User has no username with current settings. Keeping testuser", $log->test_get_flush_buffer());
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals('logoniduserid', $dbuser->username);
        $this->assertEquals('', $dbuser->email);

        // Now check that we do force.
        settings_helper::set('forceemail', 1);
        $moodleuser->convert_to_moodle($person);
        $buffer = $log->test_get_flush_buffer();
        $this->assertNotContains('NOTICE', $buffer);
        $this->assertNotContains('WARNING', $buffer);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals('testUser@eXample.com', $dbuser->email);

        // Finally we want to check the error what happens when user has no username after creation.
        settings_helper::set('usernamesource', settings::USER_NAME_OTHER);
        $moodleuser->convert_to_moodle($person);
        $error = "WARNING: User has no username with current settings. Keeping logoniduserid.";
        $this->assertContains($error, $log->test_get_flush_buffer());
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals('logoniduserid', $dbuser->username);
        $this->assertEquals('testUser@eXample.com', $dbuser->email);

    }
}
