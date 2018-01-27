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
            $this->assertContains('Expected instance of data\member_person to be passed', $ex->getMessage());
        }


//
//         // Now try with creating of new users disabled.
//         settings_helper::set('createnewusers', 0);
//         settings_helper::set('lowercaseemails', 0);
//
//         $moodleuser->convert_to_moodle($person);
//
//         $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
//         $this->assertFalse($dbuser);
//
//         // Now enabled.
//         settings_helper::set('createnewusers', 1);
//
//         // Now try some bad email settings.
//         settings_helper::set('createusersemaildomain', 'example.com');
//         settings_helper::set('ignoredomaincase', 0);
//         settings_helper::set('donterroremail', 0);
//         $moodleuser->convert_to_moodle($person);
//         $error = 'WARNING: User email not allowed by email domain settings.';
//         $this->assertContains($error, $log->test_get_flush_buffer());
//
//         // Test no error setting.
//         settings_helper::set('donterroremail', 1);
//         $moodleuser->convert_to_moodle($person);
//         $resulterror = $log->test_get_flush_buffer();
//         $error = "User email not allowed by email domain settings.\n";
//         $this->assertEquals($error, $resulterror);
//
//         // Now put the setting back.
//         settings_helper::set('createusersemaildomain', '');
//
//         // Test a normally created user.
//         $moodleuser->convert_to_moodle($person);
//         $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
//         $this->assertInstanceOf(\stdClass::class, $dbuser);
//
//         $this->assertEquals('testuser', $dbuser->username);
//
//         $this->assertEquals('testUser@eXample.com', $dbuser->email);
//
//         // Now test that it made the email lowercase.
//         settings_helper::set('forceemail', 1);
//         settings_helper::set('lowercaseemails', 1);
//
//         $moodleuser->convert_to_moodle($person);
//         $dbuser = $this->run_protected_method($moodleuser, 'find_existing_user');
//
//         $this->assertEquals('testuser@example.com', $dbuser->email);
    }

}
