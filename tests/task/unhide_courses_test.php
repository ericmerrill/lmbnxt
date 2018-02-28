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
 * Tests for the unhide courses test.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\data;
use enrol_lmb\settings;
use enrol_lmb\local\moodle;
use enrol_lmb\local\processors\xml;
use enrol_lmb\task\unhide_courses;

global $CFG;

require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class unhide_courses_test extends xml_helper {

    public function test_course_unhide() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $task = new unhide_courses();

        settings_helper::temp_set('cronunhidedays', 5);
        settings_helper::temp_set('cronunhidecourses', 1);
        settings_helper::temp_set('coursehidden', settings::CREATE_COURSE_HIDDEN);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/term.xml');
        $converter = new xml\group_term();
        $term = $converter->process_xml_to_data($node);
        $term->save_to_db();

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/section.xml');
        $converter = new xml\group_section();
        $section = $converter->process_xml_to_data($node);
        $section->save_to_db();

        $moodlecourse = new moodle\course();

        // Just make sure there is no error.
        $task->execute();

        // Now setup some time stuff.
        $curtime = time();
        $prevtime = $curtime + (4 * DAYSECS);
        $prevrun = mktime(23, 59, 59, date('n', $prevtime), date('j', $prevtime), date('Y', $prevtime));
        settings_helper::temp_set('prevunhideendtime', $prevrun);

        // First test at midnight on the day allowed (today+5 at 00:00:00).
        $reftime = $curtime + (5 * DAYSECS);
        $startmidnight = mktime(0, 0, 0, date('n', $reftime), date('j', $reftime), date('Y', $reftime));
        $section->begindate = $startmidnight;
        $section->enddate = $startmidnight + (100 * DAYSECS);

        $moodlecourse->convert_to_moodle($section);

        $course = $DB->get_record('course', ['idnumber' => $section->sdid]);

        $this->assertEquals('0', $course->visible);
        $this->assertEquals('0', $course->visibleold);

        $task->execute();

        $course = $DB->get_record('course', ['idnumber' => $section->sdid]);
        $this->assertEquals('1', $course->visible);
        $this->assertEquals('1', $course->visibleold);

        // Now test at the end of the day (Test today+5 at 23:59:59).
        settings_helper::temp_set('prevunhideendtime', $prevrun);
        $startmidnight = mktime(23, 59, 59, date('n', $reftime), date('j', $reftime), date('Y', $reftime));
        $section->begindate = $startmidnight;

        $moodlecourse->convert_to_moodle($section);
        $DB->set_field('course', 'visible', 0, ['idnumber' => $section->sdid]);

        $task->execute();
        $course = $DB->get_record('course', ['idnumber' => $section->sdid]);
        $this->assertEquals('1', $course->visible);

        // Now test just before (Test Today+4 at 23:59:59).
        settings_helper::temp_set('prevunhideendtime', $prevrun);
        $reftime = $curtime + (4 * DAYSECS);
        $startmidnight = mktime(23, 59, 59, date('n', $reftime), date('j', $reftime), date('Y', $reftime));
        $section->begindate = $startmidnight;

        $moodlecourse->convert_to_moodle($section);
        $DB->set_field('course', 'visible', 0, ['idnumber' => $section->sdid]);

        $task->execute();

        $course = $DB->get_record('course', ['idnumber' => $section->sdid]);
        $this->assertEquals('0', $course->visible);

        // And now just after (Test today+6 at 00:00:00).
        settings_helper::temp_set('prevunhideendtime', $prevrun);
        $reftime = $curtime + (6 * DAYSECS);
        $startmidnight = mktime(0, 0, 0, date('n', $reftime), date('j', $reftime), date('Y', $reftime));
        $section->begindate = $startmidnight;

        $moodlecourse->convert_to_moodle($section);
        $DB->set_field('course', 'visible', 0, ['idnumber' => $section->sdid]);

        $task->execute();

        $course = $DB->get_record('course', ['idnumber' => $section->sdid]);
        $this->assertEquals('0', $course->visible);

        // Check and reset prevunhideendtime each time.

        // Try to test around time change?


    }

}
