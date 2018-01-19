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
 * Tests for the xml parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\xml;
use enrol_lmb\local\data;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class xml_group_term_testcase extends xml_helper {
    public function test_conversion() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/term.xml');

        $converter = new xml\group();

        $term = $converter->process_xml_to_data($node);
        $this->assertInstanceOf(data\term::class, $term);

        $this->assertEquals('Test SCT Banner', $term->sdidsource);
        $this->assertEquals('201640', $term->sdid);

        $this->assertEquals('1472515200', $term->begindate);
        $this->assertEquals('1481932800', $term->enddate);

        $this->assertEquals('0', $term->beginrestrict);
        $this->assertEquals('1', $term->endrestrict);

        $this->assertEquals('0', $term->enrollallowed);
        $this->assertEquals('1', $term->enrollaccept);

        $this->assertEquals('201640', $term->sortorder);

        $this->assertEquals('Fall 2016', $term->description);
    }
}
