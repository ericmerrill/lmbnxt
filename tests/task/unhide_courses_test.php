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
use enrol_lmb\local\moodle;
use enrol_lmb\task\unhide_courses;

global $CFG;

require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class unhide_courses_test extends xml_helper {

    public function test_course_unhide() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        $task = new unhide_courses();

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/data/section.xml');
        $converter = new xml\group_section();
        $section = $converter->process_xml_to_data($node);

        $moodlecourse = new moodle\course();
        $moodlecourse->convert_to_moodle($section);

        $curtime = time();
        $starttoday = mktime(0, 0, 0, date('n', $curtime), date('j', $curtime), date('Y', $curtime));
//DAYSECS

        $task->execute();
    }

}
