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
 * Tests for the bulk utility.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

use enrol_lmb\bulk_util;
use enrol_lmb\local\data;
use enrol_lmb\local\processors\lis2;

class bulk_util_test extends xml_helper {

    public function test_get_terms_in_timeframe() {
        $this->resetAfterTest();
        $this->setup_bulk();

        $util = new bulk_util();
        $results = $util->get_terms_in_timeframe(1510000000, 1520000000);

        $this->assertTrue(isset($results['201730']));
        $this->assertTrue(isset($results['201730']['termupdate']));
        $this->assertEquals(1, $results['201730']['termupdate']);
        $this->assertTrue(isset($results['201730']['sectionupdate']));
        $this->assertEquals(1, $results['201730']['sectionupdate']);
        $this->assertTrue(isset($results['201730']['enrolupdates']));
        $this->assertEquals(12, $results['201730']['enrolupdates']);
        $this->assertTrue(isset($results['201730']['totalactiveenrols']));
        $this->assertEquals(28, $results['201730']['totalactiveenrols']);
        $this->assertTrue(isset($results['201730']['estimatedbulkdrops']));
        $this->assertEquals(9, $results['201730']['estimatedbulkdrops']);
        $this->assertTrue(isset($results['201730']['estimatedbulkpercent']));
        $this->assertEquals(32.14, $results['201730']['estimatedbulkpercent'], '', 0.1);

        $this->assertTrue(isset($results['201720']));
        $this->assertFalse(isset($results['201720']['termupdate']));
        $this->assertTrue(isset($results['201720']['sectionupdate']));
        $this->assertEquals(1, $results['201720']['sectionupdate']);
        $this->assertTrue(isset($results['201720']['enrolupdates']));
        $this->assertEquals(5, $results['201720']['enrolupdates']);
        $this->assertTrue(isset($results['201720']['totalactiveenrols']));
        $this->assertEquals(20, $results['201720']['totalactiveenrols']);
        $this->assertTrue(isset($results['201720']['estimatedbulkdrops']));
        $this->assertEquals(15, $results['201720']['estimatedbulkdrops']);
        $this->assertTrue(isset($results['201720']['estimatedbulkpercent']));
        $this->assertEquals(75, $results['201720']['estimatedbulkpercent'], '', 0.1);

        $this->assertFalse(isset($results['201710']));

        $results = $util->get_terms_in_timeframe(1510000000, 1520000000, 'Not the source');

        $this->assertEmpty($results);

        $results = $util->get_terms_in_timeframe(1510000000, 1520000000, '');
        $this->assertTrue(isset($results['201720']));
        $this->assertTrue(isset($results['201730']));
    }

    public function test_get_term_enrols_active_count() {
        $this->resetAfterTest();
        $this->setup_bulk();

        $util = new bulk_util();
        $results = $util->get_term_enrols_active_count('201700');
        $this->assertEquals(0, $results);

        $results = $util->get_term_enrols_active_count('201710');
        $this->assertEquals(15, $results);

        $results = $util->get_term_enrols_active_count('201720');
        $this->assertEquals(20, $results);

        $results = $util->get_term_enrols_active_count('201730');
        $this->assertEquals(28, $results);

        $results = $util->get_term_enrols_active_count('201730', 'Not the source');
        $this->assertEquals(0, $results);
    }

    public function test_get_term_enrols_to_drop_count() {
        $this->resetAfterTest();
        $this->setup_bulk();

        $util = new bulk_util();
        // Check a term that doesn't exist, this would get any if they existed.
        $results = $util->get_term_enrols_to_drop_count('201700', 1520000000);
        $this->assertEquals(0, $results);

        // Because we didn't receive any messages for this term, everything would be to drop.
        $results = $util->get_term_enrols_to_drop_count('201710', 1510000000);
        $this->assertEquals(15, $results);

        $results = $util->get_term_enrols_to_drop_count('201720', 1510000000);
        $this->assertEquals(15, $results);
        $results = $util->get_term_enrols_to_drop_count('201720', 1520000000);
        $this->assertEquals(20, $results);

        $results = $util->get_term_enrols_to_drop_count('201730', 1510000000);
        $this->assertEquals(9, $results);
        $results = $util->get_term_enrols_to_drop_count('201730', 1520000000);
        $this->assertEquals(28, $results);

        $results = $util->get_term_enrols_to_drop_count('201730', 1520000000, 'Not the source');
        $this->assertEquals(0, $results);
    }

    public function test_drop_old_term_enrols() {
        global $DB;

        $this->resetAfterTest();

        $log = new logging_helper();

        $this->setup_bulk(true, '201730');

        // First we want to confirm that some things are true in our tables and Moodle before correcting.
        // There should be 26 active enrols and 6 inactive.
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 1]);
        $this->assertEquals(28, $count);
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 0]);
        $this->assertEquals(6, $count);

        // Only 17 of the active enrols are actually in place because 9 are for a course that doesn't exist.
        $count = $DB->count_records('user_enrolments');
        $this->assertEquals(17, $count);

        $util = new bulk_util();

        // Test the percent limits.
        settings_helper::temp_set('dropprecentlimit', 10);
        $log->test_get_flush_buffer();

        $results = $util->drop_old_term_enrols('201730', 1510000000);

        $error = 'WARNING: Term drops exceeds limit in settings. Skipping term.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        // Make sure nothing has changed.
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 1]);
        $this->assertEquals(28, $count);
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 0]);
        $this->assertEquals(6, $count);
        $count = $DB->count_records('user_enrolments');
        $this->assertEquals(17, $count);

        // Now do it with a high limit.
        settings_helper::temp_set('dropprecentlimit', 90);

        // Try with the wrong source.
        $log->test_get_flush_buffer();
        $results = $util->drop_old_term_enrols('201730', 1510000000, 'Not the source');

        $error = 'No active enrollments in the selection. Skipping term.';
        $this->assertContains($error, $log->test_get_flush_buffer());

        // Make sure nothing has changed.
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 1]);
        $this->assertEquals(28, $count);
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 0]);
        $this->assertEquals(6, $count);
        $count = $DB->count_records('user_enrolments');
        $this->assertEquals(17, $count);

        $results = $util->drop_old_term_enrols('201730', 1510000000, '');

        // Now, we expect that 8 of the enrols to have been deactivated.
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 1]);
        $this->assertEquals(19, $count);
        $count = $DB->count_records_select(data\person_member::TABLE, 'groupsdid LIKE ? AND status = ?', ['%.201730', 0]);
        $this->assertEquals(15, $count);

        // But only 5 of those could actually be applied to Moodle.
        $count = $DB->count_records('user_enrolments');
        $this->assertEquals(12, $count);
    }

    public function test_adjust_term_section_dates() {
//        adjust_term_section_dates($termsdid, $stime, endtime)
        global $DB;

        $this->resetAfterTest();

        $this->setup_bulk(true, '201730');
        $util = new bulk_util();

        // Make sure no error happens with no courses.
        $results = $util->adjust_term_section_dates('201730', 1, 2);

        // First check the original dates are expected.
        $course = $DB->get_record('course', ['idnumber' => '10001.201730']);
        $this->assertEquals(1530288000, $course->startdate);
        $this->assertEquals(1540828800, $course->enddate);

        $course = $DB->get_record('course', ['idnumber' => '10002.201730']);
        $this->assertEquals(1530288000, $course->startdate);
        $this->assertEquals(0, $course->enddate);

        // Now a time that will just change the first course.
        $results = $util->adjust_term_section_dates('201730', 1510000000);

        $course = $DB->get_record('course', ['idnumber' => '10001.201730']);
        $this->assertEquals(1530374400, $course->startdate);
        $this->assertEquals(1540915200, $course->enddate);
        // Need to set them back.
        $course->startdate = 1530288000;
        $course->enddate = 1540828800;
        $DB->update_record('course', $course);

        $course = $DB->get_record('course', ['idnumber' => '10002.201730']);
        $this->assertEquals(1530288000, $course->startdate);
        $this->assertEquals(0, $course->enddate);

        // Now a start and end date that will only update the second course.
        $results = $util->adjust_term_section_dates('201730', 1500000000, 1500000001);

        $course = $DB->get_record('course', ['idnumber' => '10001.201730']);
        $this->assertEquals(1530288000, $course->startdate);
        $this->assertEquals(1540828800, $course->enddate);

        $course = $DB->get_record('course', ['idnumber' => '10002.201730']);
        $this->assertEquals(1530374400, $course->startdate);
        $this->assertEquals(0, $course->enddate);

        // Now dates that will update both courses.
        $results = $util->adjust_term_section_dates('201730', 1500000000);

        $course = $DB->get_record('course', ['idnumber' => '10001.201730']);
        $this->assertEquals(1530374400, $course->startdate);
        $this->assertEquals(1540915200, $course->enddate);

        $course = $DB->get_record('course', ['idnumber' => '10002.201730']);
        $this->assertEquals(1530374400, $course->startdate);
        $this->assertEquals(0, $course->enddate);
    }

    protected function setup_bulk($convert = false, $onlyterm = false) {
        global $DB;

        $oldtime = 1500000000;
        $newtime = 1510000000;

        $users = [];
        for ($i = 0; $i < 20; $i++) {
            $users[$i] = $this->create_lmb_person(null, $convert);
        }

        $terms = [];
        $sections = [];
        if (!$onlyterm || $onlyterm === '201710') {
            $term = $this->create_lmb_term(['sdid' => '201710', 'messagetime' => $oldtime], $convert);
            $terms['201710'] = $term;

            $section = $this->create_lmb_section(['termsdid' => '201710', 'messagetime' => $oldtime], $convert);
            $sections['201710'][$section->sdid] = $section;
            for ($i = 0; $i < 5; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime], $convert);
            }
            for ($i = 5; $i < 7; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime, 'status' => 0], $convert);
            }

            $section = $this->create_lmb_section(['termsdid' => '201710', 'messagetime' => $oldtime], $convert);
            $sections['201710'][$section->sdid] = $section;
            for ($i = 0; $i < 10; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime], $convert);
            }
        }

        if (!$onlyterm || $onlyterm === '201720') {
            $term = $this->create_lmb_term(['sdid' => '201720', 'messagetime' => $oldtime], $convert);
            $terms['201720'] = $term;
            $section = $this->create_lmb_section(['termsdid' => '201720', 'messagetime' => $oldtime], $convert);
            $sections['201720'][$section->sdid] = $section;
            for ($i = 0; $i < 5; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime], $convert);
            }
            for ($i = 5; $i < 10; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime], $convert);
            }
            for ($i = 10; $i < 12; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime, 'status' => 0], $convert);
            }

            $section = $this->create_lmb_section(['termsdid' => '201720', 'messagetime' => $newtime], $convert);
            $sections['201720'][$section->sdid] = $section;
            for ($i = 0; $i < 10; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime], $convert);
            }
        }

        if (!$onlyterm || $onlyterm === '201730') {
            $term = $this->create_lmb_term(['sdid' => '201730', 'messagetime' => $newtime], $convert);
            $terms['201730'] = $term;
            $params = ['sdid' => '10001.201730', 'termsdid' => '201730', 'messagetime' => $newtime,
                       'begindate' => '2018-06-30T00:00:00', 'enddate' => '2018-10-30T00:00:00'];
            $section = $this->create_lmb_section($params, $convert);
            $sections['201730'][$section->sdid] = $section;
            for ($i = 0; $i < 5; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime], $convert);
            }
            for ($i = 5; $i < 10; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime], $convert);
            }
            for ($i = 10; $i < 12; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime, 'status' => 0], $convert);
            }

            $params = ['sdid' => '10002.201730', 'termsdid' => '201730', 'messagetime' => $oldtime,
                       'begindate' => '2018-06-30T00:00:00'];
            $section = $this->create_lmb_section($params, $convert);
            $sections['201730'][$section->sdid] = $section;
            for ($i = 0; $i < 7; $i++) {
                $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime], $convert);
            }

            // Now a course section that doesn't exist.
            for ($i = 0; $i < 6; $i++) {
                $this->create_lmb_enrol('99999.201730', $users[$i], ['messagetime' => $newtime], $convert);
            }
            for ($i = 6; $i < 9; $i++) {
                $this->create_lmb_enrol('99999.201730', $users[$i], ['messagetime' => $oldtime], $convert);
            }
            for ($i = 9; $i < 13; $i++) {
                $this->create_lmb_enrol('99999.201730', $users[$i], ['messagetime' => $newtime, 'status' => 0], $convert);
            }

            // Need to update message time and time modified for these.
            $enrol = $this->create_lmb_enrol('99999.201730', $users[13], [], $convert);
            $DB->set_field($enrol::TABLE, 'messagetime', null, ['id' => $enrol->id]);
            $DB->set_field($enrol::TABLE, 'messagetime', $newtime, ['id' => $enrol->id]);

            $enrol = $this->create_lmb_enrol('99999.201730', $users[14], ['messagetime' => null], $convert);
            $DB->set_field($enrol::TABLE, 'messagetime', null, ['id' => $enrol->id]);
            $DB->set_field($enrol::TABLE, 'timemodified', $oldtime, ['id' => $enrol->id]);
        }
    }
}
