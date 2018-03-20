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
use enrol_lmb\local\processors\lis2;

class bulk_util_test extends xml_helper {

    public function test_get_terms_in_timeframe() {
        global $CFG;
        $this->resetAfterTest();

        $util = new bulk_util();

        $start = time();

        // First the term.
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/term_replace.xml');
        $converter = new lis2\group();

        $term = $converter->process_xml_to_data($node);
        $term->save_to_db();

        // Now a section.
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/section_replace.xml');
        $converter = new lis2\section();

        $section = $converter->process_xml_to_data($node);
        $section->save_to_db();

        // Then a crosslist membership.
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/section_assoc_replace.xml');
        $converter = new lis2\section_assoc();

        $xlsmember = $converter->process_xml_to_data($node);
        $xlsmember->save_to_db();

        // Now an enrolment.
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/member_replace_teacher.xml');
        $converter = new lis2\membership();

        $member = $converter->process_xml_to_data($node);
        $member->save_to_db();


        $results = $util->get_terms_in_timeframe($start);


        $this->assertTrue(isset($results['201740']));
        $this->assertTrue(isset($results['201740']['termupdate']));
        $this->assertEquals(1, $results['201740']['termupdate']);
        $this->assertTrue(isset($results['201740']['sectionupdate']));
        $this->assertEquals(1, $results['201740']['sectionupdate']);
        $this->assertTrue(isset($results['201740']['crossmemberupdate']));
        $this->assertEquals(1, $results['201740']['crossmemberupdate']);
        $this->assertTrue(isset($results['201740']['enrolupdates']));
        $this->assertEquals(1, $results['201740']['enrolupdates']);

        // TODO - This needs much better testing.

        //print "<pre>";var_export($results);print "</pre>\n";
    }


}
