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

class moodle_course_testcase extends xml_helper {

    public function test_create_course_title() {
        global $CFG;
        $this->resetAfterTest(true);

        // First a LIS 2 based node.
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/section_replace.xml');
        $converter = new lis2\section();
        $section = $converter->process_xml_to_data($node);

        $moodlecourse = new moodle\course();

        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[SOURCEDID]', $section]);
        $this->assertEquals('10001.201740', $result);
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[CRN]:[TERM]', $section]);
        $this->assertEquals('10001:201740', $result);
        // The term isn't in the DB, so we just expect the term code.
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[TERMNAME]', $section]);
        $this->assertEquals('201740', $result);
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[LONG]', $section]);
        $this->assertEquals('ENG-101-001', $result);
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[RUBRIC]:[DEPT]:[NUM]:[SECTION]', $section]);
        $this->assertEquals('ENG-101:ENG:101:001', $result);
        // This one will have the full semester name in the title, because we haven't loaded the term in the DB yet.
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[FULL]', $section]);
        $this->assertEquals('Fall Semester 2017 - Course Title', $result);

        // Load up the term into the DB.
        $termnode = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/term_replace.xml');
        $termconverter = new lis2\group_term();
        $term = $termconverter->process_xml_to_data($termnode);
        $term->save_to_db();

        // Clear a term cache, now that we loaded a new one.
        $this->set_protected_property(data\term::class, 'terms', []);

        // Now try the term name again.
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[TERMNAME]', $section]);
        $this->assertEquals('Fall Semester 2017', $result);

        // Now lets try the LIS 1.x based course XML.
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/section.xml');
        $converter = new xml\group();
        $section = $converter->process_xml_to_data($node);

        $moodlecourse->set_data($section);
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[SOURCEDID]', $section]);
        $this->assertEquals('10001.201740', $result);
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[CRN]:[TERM]', $section]);
        $this->assertEquals('10001:201740', $result);
        // The term isn't in the DB, so we just expect the term code.
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[TERMNAME]', $section]);
        $this->assertEquals('Fall Semester 2017', $result);
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[LONG]', $section]);
        $this->assertEquals('ENG-101-001', $result);
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[RUBRIC]:[DEPT]:[NUM]:[SECTION]', $section]);
        $this->assertEquals('ENG-101:ENG:101:001', $result);
        // This one will have the full semester name in the title, because we hadn't loaded it up yet.
        $result = $this->run_protected_method($moodlecourse, 'create_course_title', ['[FULL]', $section]);
        $this->assertEquals('Course Title', $result);
    }

    public function test_calculate_visibility() {
        // Set this course section to start 4 days in future.
        $time = time() + (4 * 24 * 3600);
        $starttime = mktime(0, 0, 0, date('n', $time), date('j', $time), date('Y', $time));
        $section = new data\section();

        $moodlecourse = new moodle\course();

        // Setting for 5 days.
        settings_helper::temp_set('cronunhidedays', 5);

        // Check always visible
        settings_helper::temp_set('coursehidden', settings::CREATE_COURSE_VISIBLE);
        $result = $this->run_protected_method($moodlecourse, 'calculate_visibility', [$starttime]);
        $this->assertEquals(1, $result);
        // Check always hidden.
        settings_helper::temp_set('coursehidden', settings::CREATE_COURSE_HIDDEN);
        $result = $this->run_protected_method($moodlecourse, 'calculate_visibility', [$starttime]);
        $this->assertEquals(0, $result);
        // Check based on cron setting (should be visible).
        settings_helper::temp_set('coursehidden', settings::CREATE_COURSE_CRON);
        $result = $this->run_protected_method($moodlecourse, 'calculate_visibility', [$starttime]);
        $this->assertEquals(1, $result);

        // Check an invalid setting.
        settings_helper::temp_set('coursehidden', 'invalid');
        $result = $this->run_protected_method($moodlecourse, 'calculate_visibility', [$starttime]);
        $this->assertEquals(1, $result);

        // Setting for 2 days.
        settings_helper::temp_set('cronunhidedays', 2);

        // Check always visible
        settings_helper::temp_set('coursehidden', settings::CREATE_COURSE_VISIBLE);
        $result = $this->run_protected_method($moodlecourse, 'calculate_visibility', [$starttime]);
        $this->assertEquals(1, $result);
        // Check always hidden.
        settings_helper::temp_set('coursehidden', settings::CREATE_COURSE_HIDDEN);
        $result = $this->run_protected_method($moodlecourse, 'calculate_visibility', [$starttime]);
        $this->assertEquals(0, $result);
        // Check based on cron setting (should be hidden).
        settings_helper::temp_set('coursehidden', settings::CREATE_COURSE_CRON);
        $result = $this->run_protected_method($moodlecourse, 'calculate_visibility', [$starttime]);
        $this->assertEquals(0, $result);

        settings_helper::reset();

        // TODO: Better testing right around midnight, and checking for the boundary day.
    }

    public function test_calculate_section_count() {
        $section = new data\section();

        $moodlecourse = new moodle\course();

        // We don't have both dates, so these will both be false.
        settings_helper::temp_set('computesections', 0);
        $begindate = mktime(0, 0, 0, 1, 1, 2018);
        $result = $this->run_protected_method($moodlecourse, 'calculate_section_count', [$begindate, null]);
        $this->assertFalse($result);

        settings_helper::temp_set('computesections', 1);
        $result = $this->run_protected_method($moodlecourse, 'calculate_section_count', [$begindate, null]);
        $this->assertFalse($result);

        // Now add the other date.
        $enddate = mktime(0, 0, 0, 1, 27, 2018);
        settings_helper::temp_set('computesections', 0);
        $result = $this->run_protected_method($moodlecourse, 'calculate_section_count', [$begindate, $enddate]);
        $this->assertFalse($result);

        settings_helper::temp_set('computesections', 1);
        $result = $this->run_protected_method($moodlecourse, 'calculate_section_count', [$begindate, $enddate]);
        $this->assertEquals(4, $result);

        // Now see what happens if that end is before the start.
        $enddate = mktime(0, 0, 0, 12, 31, 2017);
        $result = $this->run_protected_method($moodlecourse, 'calculate_section_count', [$begindate, $enddate]);
        $this->assertFalse($result);

        // And then more than max sections.
        $maxsections = get_config('moodlecourse', 'maxsections');
        $enddate = mktime(0, 0, 0, 1, 1, 2018) + (($maxsections + 1) * 7 * 86400);
        $result = $this->run_protected_method($moodlecourse, 'calculate_section_count', [$begindate, $enddate]);
        $this->assertEquals($maxsections, $result);
    }

    /**
     * Data provider for test_convert_to_moodle.
     *
     * @return array
     */
    public function convert_to_moodle_testcases() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/section_replace.xml');
        $converter = new lis2\section();
        $section1 = $converter->process_xml_to_data($node);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/section.xml');
        $converter = new xml\group_section();
        $section2 = $converter->process_xml_to_data($node);

        $output = ['lis2' => [$section1],
                   'xml'  => [$section2]];

        return $output;
    }

    /**
     * Test that two identical courses are made from LIS1-XML and LIS2 content.
     *
     * @dataProvider convert_to_moodle_testcases
     * @param data\section $section The input section
     */
    public function test_convert_to_moodle($section) {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $moodlecourse = new moodle\course();

        settings_helper::temp_set('coursetitle', '[CRN]:[TERM]');
        settings_helper::temp_set('forcetitle', 0);
        settings_helper::temp_set('courseshorttitle', '[DEPT][NUM]');
        settings_helper::temp_set('forceshorttitle', 0);
        settings_helper::temp_set('computesections', 0);
        settings_helper::temp_set('forcecomputesections', 0);

        data_test::set_value($section, 'begindate', 0);

        // Test with a bad data object.
        $baddata = new data\person();
        try {
            $moodlecourse->convert_to_moodle($baddata);
            $this->fail("Expected exception not thrown.");
        } catch (\Exception $ex) {
            $this->assertInstanceOf(\coding_exception::class, $ex);
            $this->assertContains('Expected instance of data\section to be passed', $ex->getMessage());
        }

        $moodlecourse->convert_to_moodle($section);

        $dbcourse = $DB->get_record('course', array('idnumber' => '10001.201740'), '*', MUST_EXIST);
        $this->assertInstanceOf(\stdClass::class, $dbcourse);

        $this->assertEquals('10001:201740', $dbcourse->fullname);
        $this->assertEquals('ENG101', $dbcourse->shortname);

        $this->assertEquals(0, $dbcourse->startdate);
        $this->assertEquals(0, $dbcourse->enddate);

        $sections = $DB->count_records('course_sections', array('course' => $dbcourse->id));
        $sections -= 1; // Remove 1 to account for the general section.
        $this->assertEquals(get_config('moodlecourse', 'numsections'), $sections);

        // Now we are going to change some things in the DB, to make sure they don't get overwritten.
        $dbcourse->fullname = 'Full name';
        $dbcourse->shortname = 'Short name';
        $DB->update_record('course', $dbcourse);

        settings_helper::temp_set('computesections', 1);

        data_test::set_value($section, 'begindate', 1504224000);
        data_test::set_value($section, 'enddate', 0);

        // Now run an update make sure things don't force change.
        $moodlecourse->convert_to_moodle($section);

        $dbcourse = $DB->get_record('course', array('idnumber' => '10001.201740'), '*', MUST_EXIST);
        $this->assertInstanceOf(\stdClass::class, $dbcourse);

        $this->assertEquals('Full name', $dbcourse->fullname);
        $this->assertEquals('Short name', $dbcourse->shortname);

        // Dates currently always get overwritten.
        $this->assertEquals(1504224000, $dbcourse->startdate);
        $this->assertEquals(0, $dbcourse->enddate);

        $sections = $DB->count_records('course_sections', array('course' => $dbcourse->id));
        $sections -= 1; // Remove 1 to account for the general section.
        $this->assertEquals(get_config('moodlecourse', 'numsections'), $sections);

        // Now turn on forcing and try again.
        settings_helper::temp_set('forcetitle', 1);
        settings_helper::temp_set('forceshorttitle', 1);
        settings_helper::temp_set('forcecomputesections', 1);

        data_test::set_value($section, 'enddate', 1514678400);

        $moodlecourse->convert_to_moodle($section);

        $dbcourse = $DB->get_record('course', array('idnumber' => '10001.201740'), '*', MUST_EXIST);
        $this->assertInstanceOf(\stdClass::class, $dbcourse);

        $this->assertEquals('10001:201740', $dbcourse->fullname);
        $this->assertEquals('ENG101', $dbcourse->shortname);

        $this->assertEquals(1504224000, $dbcourse->startdate);
        $this->assertEquals(1514678400, $dbcourse->enddate);

        $sections = $DB->count_records('course_sections', array('course' => $dbcourse->id));
        $sections -= 1; // Remove 1 to account for the general section.
        $this->assertEquals(18, $sections);
    }

    public function test_course_creation_enrol() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_user(['idnumber' => '1000001']);

        $startenrols = $DB->count_records('user_enrolments');

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NOTICE);

        $moodleenrol = new moodle\enrolment();
        $enrolnode = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/member_student.xml');
        $converter = new xml\membership();
        $members = $converter->process_xml_to_data($enrolnode);
        $member = reset($members);
        $member->save_to_db();

        // First no course.
        $moodleenrol->convert_to_moodle($member);
        $error = 'WARNING: Moodle course could not be found.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        // Make sure no new enrolments happened somehow.
        $this->assertEquals($startenrols, $DB->count_records('user_enrolments'));

        // Now lets add the course.
        $moodlecourse = new moodle\course();
        $coursenode = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/section.xml');
        $converter = new xml\group();
        $course = $converter->process_xml_to_data($coursenode);
        $moodlecourse->convert_to_moodle($course);

        $this->assertEquals($startenrols + 1, $DB->count_records('user_enrolments'));
    }
}
