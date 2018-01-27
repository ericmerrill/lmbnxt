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

class xml_member_person_testcase extends xml_helper {
    public function test_conversion() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/member_person.xml');

        $converter = new xml\membership();

        $members = $converter->process_xml_to_data($node);

        $this->assertInternalType('array', $members);
        $this->assertCount(3, $members);

        $member = $members[0];
        $this->assertInstanceOf(data\member_person::class, $member);

        $this->assertEquals('Test SCT Banner', $member->sdidsource);
        $this->assertEquals('1000002', $member->sdid);

        $this->assertEquals('02', $member->roletype);
        $this->assertEquals(1, $member->status);
        $this->assertEquals('Primary', $member->subrole);

        $this->assertFalse(isset($member->begindate));
        $this->assertFalse(isset($member->beginrestrict));
        $this->assertFalse(isset($member->enddate));
        $this->assertFalse(isset($member->endrestrict));

        $this->assertEquals('Test SCT Banner', $member->groupsdidsource);
        $this->assertEquals('10001.201740', $member->groupsdid);
        $this->assertEquals(1, $member->membertype);

        $member = $members[1];
        $this->assertInstanceOf(data\member_person::class, $member);
        $this->assertEquals('Test SCT Banner', $member->sdidsource);
        $this->assertEquals('1000001', $member->sdid);

        $this->assertEquals('01', $member->roletype);
        $this->assertEquals(1, $member->status);
        $this->assertFalse(isset($member->subrole));

        $this->assertEquals(1504051200, $member->begindate);
        $this->assertEquals(0, $member->beginrestrict);
        $this->assertEquals(1513468800, $member->enddate);
        $this->assertEquals(1, $member->endrestrict);

        $this->assertEquals('Test SCT Banner', $member->groupsdidsource);
        $this->assertEquals('10001.201740', $member->groupsdid);
        $this->assertEquals(1, $member->membertype);

        $this->assertEquals('Letter Grade', $member->midtermmode);
        $this->assertEquals('4-Point Grade', $member->finalmode);
        $this->assertEquals(1, $member->gradable);

        $member = $members[2];
        $this->assertInstanceOf(data\member_person::class, $member);
        $this->assertEquals('Test SCT Banner', $member->sdidsource);
        $this->assertEquals('1000003', $member->sdid);

        $this->assertEquals('02', $member->roletype);
        $this->assertEquals(0, $member->status);

        $this->assertEquals('Test SCT Banner', $member->groupsdidsource);
        $this->assertEquals('10001.201740', $member->groupsdid);
        $this->assertEquals(1, $member->membertype);

    }
}
