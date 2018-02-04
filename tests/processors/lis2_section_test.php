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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\lis2;
use enrol_lmb\local\data;
use enrol_lmb\local\exception;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class lis2_section_test extends xml_helper {
    public function test_section() {
        global $CFG;
        $this->resetAfterTest(true);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/parse/section_replace.xml');

        // Clear a term cache.
        $this->set_protected_property(data\term::class, 'terms', []);

        $converter = new lis2\section();

        $section = $converter->process_xml_to_data($node);
        $this->assertInstanceOf(data\section::class, $section);

        $this->assertEquals('44654.201740', $section->sdid);
        $this->assertEquals('44654', $section->crn);
        $this->assertEquals('201740', $section->termsdid);
        $this->assertEquals('Banner', $section->sdidsource);

        $this->assertEquals('Fall Semester 2017 - Contemporary Fiction', $section->title);
        $this->assertEquals('ENG-3705-001', $section->rubric);
        $this->assertEquals('3705', $section->coursenumber);
        $this->assertEquals('001', $section->sectionnumber);

        $this->assertEquals('English Dept', $section->deptname);
        $this->assertEquals('ENG', $section->deptsdid);
        $this->assertEquals('Active', $section->status);

        $this->assertEquals(1504224000, $section->begindate);
        $this->assertEquals(0, $section->beginrestrict);
        $this->assertEquals(1514678400, $section->enddate);
        $this->assertEquals(0, $section->endrestrict);

        $this->assertEquals('ENG.3705', $section->coursesdid);
        $this->assertEquals('Main Campus', $section->location);

        // Now we are going to load up the term, because that changes the title.
        $termnode = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/term_replace.xml');
        $termconverter = new lis2\group_term();
        $term = $termconverter->process_xml_to_data($termnode);
        $term->save_to_db();
        $this->set_protected_property(data\term::class, 'terms', []);

        $section = $converter->process_xml_to_data($node);
        $this->assertInstanceOf(data\section::class, $section);

        $this->assertEquals('Contemporary Fiction', $section->title);

        //print "<pre>";var_export($section);print "</pre>\n";
        // TODO.

    }

}
