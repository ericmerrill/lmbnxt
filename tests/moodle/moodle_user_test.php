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

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/person_replace.xml');
        $converter = new lis2\person();
        $person = $converter->process_xml_to_data($node);

        $moodleuser = new moodle\user();
        $moodleuser->set_data($person);

        settings_helper::temp_set('sourcedidfallback', 0);

        settings_helper::temp_set('usernamesource', settings::USER_NAME_EMAIL);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('testuser@example.com', $result);

        settings_helper::temp_set('usernamesource', settings::USER_NAME_EMAILNAME);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('testuser', $result);

        settings_helper::temp_set('usernamesource', settings::USER_NAME_LOGONID);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('logoniduserid', $result);

        settings_helper::temp_set('usernamesource', settings::USER_NAME_SCTID);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals(strtolower($person->sctid), $result);

        settings_helper::temp_set('usernamesource', settings::USER_NAME_EMAILID);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('emailuserid', $result);

        settings_helper::temp_set('usernamesource', settings::USER_NAME_OTHER);
        // Setting usernamesource isn't set, so expect false.
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertFalse($result);

        // With sourcedid fallback, we get that.
        settings_helper::temp_set('sourcedidfallback', 1);
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('1000001', $result);

        settings_helper::temp_set('otheruserid', 'Other ID');
        $result = $this->run_protected_method($moodleuser, 'get_username');
        $this->assertEquals('otheruserid', $result);
    }

    public function test_create_new_user_object() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/person_replace.xml');
        $converter = new lis2\person();
        $person = $converter->process_xml_to_data($node);

        $moodleuser = new moodle\user();
        $moodleuser->set_data($person);

        $result = $this->run_protected_method($moodleuser, 'create_new_user_object');
        $this->assertInstanceOf(\stdClass::class, $result);

        // Right now this has no items, so adding this as a reminder if we add stuff.
        $this->assertCount(3, (array)$result);

        $this->assertEquals($CFG->lang, $result->lang);
        $this->assertEquals(1, $result->confirmed);
        $this->assertEquals($CFG->mnet_localhost_id, $result->mnethostid);
    }

    /**
     * Data provider for test_convert_to_moodle and test_empty_email_and_username.
     *
     * @return array
     */
    public function convert_to_moodle_testcases() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/person_replace.xml');
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

        // Make a profile field to use.
        $this->set_protected_property(moodle\user::class, 'customfields', []);
        $fieldid = $DB->insert_record('user_info_field', ['shortname' => 'text1', 'name' => 'Text 1', 'categoryid' => 1,
                'datatype' => 'text']);

        $moodleuser = new moodle\user();

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NOTICE);

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
        settings_helper::temp_set('createnewusers', 0);
        settings_helper::temp_set('lowercaseemails', 0);

        $moodleuser->convert_to_moodle($person);

        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertFalse($dbuser);

        // Now enabled.
        settings_helper::temp_set('createnewusers', 1);

        // Now try some bad email settings.
        settings_helper::temp_set('createusersemaildomain', 'example.com');
        settings_helper::temp_set('ignoredomaincase', 0);
        settings_helper::temp_set('donterroremail', 0);
        $moodleuser->convert_to_moodle($person);
        $error = 'WARNING: User email not allowed by email domain settings.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        // Test no error setting.
        settings_helper::temp_set('donterroremail', 1);
        $moodleuser->convert_to_moodle($person);
        $resulterror = $log->test_get_flush_buffer();
        $error = "User email not allowed by email domain settings.\n";
        $this->assertEquals($error, $resulterror);

        // Now put the setting back.
        settings_helper::temp_set('createusersemaildomain', '');
        settings_helper::temp_set('nickname', settings::USER_NICK_DISABLED);

        // Test a normally created user.
        $moodleuser->convert_to_moodle($person);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertInstanceOf(\stdClass::class, $dbuser);

        $this->assertEquals('testuser', $dbuser->username);
        $this->assertEquals('testUser@eXample.com', $dbuser->email);
        $this->assertEquals('Test', $dbuser->firstname);
        $this->assertEquals('User', $dbuser->lastname);
        $this->assertEquals('', $dbuser->alternatename);
        $this->assertEquals('manual', $dbuser->auth);
        $this->assertFalse($DB->get_record('user_info_data', ['userid' => $dbuser->id, 'fieldid' => $fieldid]));

        // Lets clear some things so that we can test the forces.
        $clearuser = new stdClass();
        $clearuser->id = $dbuser->id;
        $clearuser->email = 'x@x.com';
        $clearuser->firstname = 'x';
        $clearuser->lastname = 'x';
        $clearuser->alternatename = 'x';
        $DB->update_record('user', $clearuser);

        settings_helper::temp_set('forceemail', 0);
        settings_helper::temp_set('forcefirstname', 0);
        settings_helper::temp_set('forcelastname', 0);
        settings_helper::temp_set('forcealtname', 0);

        // Check that they don't get reset.
        $moodleuser->convert_to_moodle($person);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');

        $this->assertEquals('x@x.com', $dbuser->email);
        $this->assertEquals('x', $dbuser->firstname);
        $this->assertEquals('x', $dbuser->lastname);
        $this->assertEquals('x', $dbuser->alternatename);

        // Now see that forces work.
        settings_helper::temp_set('forceemail', 1);
        settings_helper::temp_set('forcefirstname', 1);
        settings_helper::temp_set('forcelastname', 1);
        settings_helper::temp_set('forcealtname', 1);
        // Testing lower case email.
        settings_helper::temp_set('lowercaseemails', 1);
        // Testing alt nickname.
        settings_helper::temp_set('nickname', settings::USER_NICK_ALT);
        settings_helper::temp_set('customfield1mapping', 'text1');
        settings_helper::temp_set('customfield1source', settings::USER_NAME_EMAILNAME);

        $moodleuser->convert_to_moodle($person);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');

        $this->assertEquals('testuser@example.com', $dbuser->email);
        $this->assertEquals('Test', $dbuser->firstname);
        $this->assertEquals('User', $dbuser->lastname);
        $this->assertEquals('Nick', $dbuser->alternatename);

        $custom = $DB->get_record('user_info_data', ['userid' => $dbuser->id, 'fieldid' => $fieldid]);
        $this->assertInstanceOf(\stdClass::class, $custom);
        $this->assertEquals('testUser', $custom->data);

        // Now check the first name alt-name.
        settings_helper::temp_set('nickname', settings::USER_NICK_FIRST);
        $moodleuser->convert_to_moodle($person);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals('Nick', $dbuser->firstname);
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

        settings_helper::temp_set('createnewusers', 1);
        settings_helper::temp_set('sourcedidfallback', 0);
        settings_helper::temp_set('usernamesource', settings::USER_NAME_EMAILNAME);

        // First, try to make a user with no user.
        $moodleuser->convert_to_moodle($person);
        $error = "NOTICE: No username could be determined for user.";
        $this->assertContains($error, $log->test_get_flush_buffer());
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertFalse($dbuser);

        // See that it falls back to the sourcedid.
        settings_helper::temp_set('sourcedidfallback', 1);
        $moodleuser->convert_to_moodle($person);
        $buffer = $log->test_get_flush_buffer();
        $this->assertNotContains('NOTICE', $buffer);
        $this->assertNotContains('WARNING', $buffer);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertInstanceOf(\stdClass::class, $dbuser);
        $this->assertEquals($person->sdid, $dbuser->username);

        // Now no email address, but with username.
        settings_helper::temp_set('usernamesource', settings::USER_NAME_LOGONID);
        settings_helper::temp_set('sourcedidfallback', 0);
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
        settings_helper::temp_set('forceemail', 0);
        $moodleuser->convert_to_moodle($person);
        $buffer = $log->test_get_flush_buffer();
        $this->assertNotContains('NOTICE', $buffer);
        $this->assertNotContains('WARNING', $buffer);
        //$this->assertContains("WARNING: User has no username with current settings. Keeping testuser", $log->test_get_flush_buffer());
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals('logoniduserid', $dbuser->username);
        $this->assertEquals('', $dbuser->email);

        // Now check that we do force.
        settings_helper::temp_set('forceemail', 1);
        $moodleuser->convert_to_moodle($person);
        $buffer = $log->test_get_flush_buffer();
        $this->assertNotContains('NOTICE', $buffer);
        $this->assertNotContains('WARNING', $buffer);
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals('testUser@eXample.com', $dbuser->email);

        // Finally we want to check the error what happens when user has no username after creation.
        settings_helper::temp_set('usernamesource', settings::USER_NAME_OTHER);
        $moodleuser->convert_to_moodle($person);
        $error = "WARNING: User has no username with current settings. Keeping logoniduserid.";
        $this->assertContains($error, $log->test_get_flush_buffer());
        $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals('logoniduserid', $dbuser->username);
        $this->assertEquals('testUser@eXample.com', $dbuser->email);

    }

    public function test_check_email_domain() {
        $person = new data\person();
        $moodleuser = new moodle\user();
        $moodleuser->set_data($person);

        settings_helper::temp_set('createusersemaildomain', '');
        settings_helper::temp_set('ignoredomaincase', 0);

        $result = $this->run_protected_method($moodleuser, 'check_email_domain');
        $this->assertTrue($result);

        settings_helper::temp_set('createusersemaildomain', 'example.com');
        $result = $this->run_protected_method($moodleuser, 'check_email_domain');
        $this->assertFalse($result);

        $person->email = 'test@example.com@eXample.com';
        $result = $this->run_protected_method($moodleuser, 'check_email_domain');
        $this->assertFalse($result);

        $person->email = 'test@example.com';
        $result = $this->run_protected_method($moodleuser, 'check_email_domain');
        $this->assertTrue($result);

        $person->email = 'test@eXample.com';
        $result = $this->run_protected_method($moodleuser, 'check_email_domain');
        $this->assertFalse($result);

        settings_helper::temp_set('ignoredomaincase', 1);

        $result = $this->run_protected_method($moodleuser, 'check_email_domain');
        $this->assertTrue($result);
    }

    public function test_find_existing_user() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NOTICE);

        $moodleuser = new moodle\user();
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/person_replace.xml');
        $converter = new lis2\person();
        $person = $converter->process_xml_to_data($node);

        settings_helper::temp_set('usernamesource', settings::USER_NAME_EMAILNAME);

        $moodleuser->convert_to_moodle($person);

        // Get the user record first.
        $dbuser1 = $this->run_protected_method($moodleuser, 'find_existing_user');

        $this->assertFalse(empty($dbuser1->id));
        $this->assertEquals($dbuser1->idnumber, $person->sdid);
        $this->assertEquals('testuser', $dbuser1->username);

        // Now we are going to clear the idnumber of that record.
        $DB->set_field('user', 'idnumber', '', ['id' => $dbuser1->id]);
        settings_helper::temp_set('consolidateusernames', 0);

        // With consolidate off, we shouldn't be able to find it.
        $dbuser2 = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertFalse($dbuser2);

        // Now find it by username.
        settings_helper::temp_set('consolidateusernames', 1);
        $dbuser2 = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertEquals($dbuser1->id, $dbuser2->id);

        // Now we want to set the ID number to something else, so we get an error.
        $DB->set_field('user', 'idnumber', 'Something', ['id' => $dbuser1->id]);

        $dbuser2 = $this->run_protected_method($moodleuser, 'find_existing_user');
        $error = "NOTICE: Existing user with username testuser found, but has non-matching ID Number.";
        $this->assertContains($error, $log->test_get_flush_buffer());
        $this->assertFalse($dbuser2);

        // Now double check empty username.
        unset($person->email);
        $dbuser2 = $this->run_protected_method($moodleuser, 'find_existing_user');
        $this->assertFalse($dbuser2);
    }

    public function test_get_custom_profile_field() {
        global $DB;

        $this->resetAfterTest(true);
        // Make sure the static field has been cleared.
        $this->set_protected_property(moodle\user::class, 'customfields', []);

        $fieldid = $DB->insert_record('user_info_field', ['shortname' => 'text1', 'name' => 'Text 1', 'categoryid' => 1,
                'datatype' => 'text']);

        $moodleuser = new moodle\user();

        $startreads = $DB->perf_get_reads();

        $field = $this->run_protected_method($moodleuser, 'get_custom_profile_field', ['unknown']);
        $this->assertFalse($field);
        $this->assertEquals($startreads + 1, $DB->perf_get_reads());

        $field = $this->run_protected_method($moodleuser, 'get_custom_profile_field', ['text1']);
        $this->assertInstanceOf(\stdClass::class, $field);
        $this->assertEquals($fieldid, $field->id);
        $this->assertEquals('Text 1', $field->name);
        $this->assertEquals('text1', $field->shortname);
        $this->assertEquals($startreads + 2, $DB->perf_get_reads());

        // Now make sure that there aren't extra DB hits.
        $field = $this->run_protected_method($moodleuser, 'get_custom_profile_field', ['unknown']);
        $field = $this->run_protected_method($moodleuser, 'get_custom_profile_field', ['text1']);
        $this->assertEquals($startreads + 2, $DB->perf_get_reads());

        // Double check that clearing the static and then re-calling is working right.
        $this->set_protected_property(moodle\user::class, 'customfields', []);
        $field = $this->run_protected_method($moodleuser, 'get_custom_profile_field', ['unknown']);
        $field = $this->run_protected_method($moodleuser, 'get_custom_profile_field', ['text1']);
        $this->assertEquals($startreads + 4, $DB->perf_get_reads());
    }

    public function test_save_custom_profile_value() {
        global $DB;

        $this->resetAfterTest(true);

        $this->set_protected_property(moodle\user::class, 'customfields', []);
        $moodleuser = new moodle\user();

        $user = $this->getDataGenerator()->create_user();
        $fieldid = $DB->insert_record('user_info_field', ['shortname' => 'text1', 'name' => 'Text 1', 'categoryid' => 1,
                'datatype' => 'text']);

        // First, no userid is set, so it just fails.
        $result = $this->run_protected_method($moodleuser, 'save_custom_profile_value', ['text1', 'Value1']);
        $this->assertFalse($result);

        $this->set_protected_property($moodleuser, 'userid', $user->id);

        // Next, a field that doesn't exist.
        $result = $this->run_protected_method($moodleuser, 'save_custom_profile_value', ['unknown', 'Value1']);
        $this->assertFalse($result);

        // Now see if it works.
        $result = $this->run_protected_method($moodleuser, 'save_custom_profile_value', ['text1', 'Value1']);
        $this->assertTrue($result);

        $result = $DB->get_record('user_info_data', ['userid' => $user->id, 'fieldid' => $fieldid]);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals('Value1', $result->data);

        // Now try updating.
        $result = $this->run_protected_method($moodleuser, 'save_custom_profile_value', ['text1', 'Value2']);
        $this->assertTrue($result);
        $result = $DB->get_record('user_info_data', ['userid' => $user->id, 'fieldid' => $fieldid]);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals('Value2', $result->data);

        // And try setting it to an empty value.
        $result = $this->run_protected_method($moodleuser, 'save_custom_profile_value', ['text1', null]);
        $this->assertTrue($result);
        $result = $DB->get_record('user_info_data', ['userid' => $user->id, 'fieldid' => $fieldid]);
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals('', $result->data);
    }

    public function test_user_creation_enrol() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(['idnumber' => '10001.201740']);

        $startenrols = $DB->count_records('user_enrolments');

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NOTICE);

        $moodleenrol = new moodle\enrolment();
        $enrolnode = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/member_student.xml');
        $converter = new xml\membership();
        $members = $converter->process_xml_to_data($enrolnode);
        $member = reset($members);
        $member->save_to_db();

        // First no user.
        $moodleenrol->convert_to_moodle($member);
        $error = 'WARNING: Moodle user could not be found.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        // Make sure no new enrolments happened somehow.
        $this->assertEquals($startenrols, $DB->count_records('user_enrolments'));

        // Now lets add the user.
        $moodleuser = new moodle\user();
        $personnode = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/person.xml');
        $converter = new xml\person();
        $user = $converter->process_xml_to_data($personnode);
        $moodleuser->convert_to_moodle($user);

        $this->assertEquals($startenrols + 1, $DB->count_records('user_enrolments'));
    }
}
