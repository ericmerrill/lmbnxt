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

class xml_member_group_testcase extends xml_helper {
    public function test_conversion() {
        global $CFG;
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/member_group.xml');

        $converter = new xml\membership();

        settings_helper::temp_set('xlstype', data\crosslist::GROUP_TYPE_MERGE);

        $crosslist = $converter->process_xml_to_data($node);

        $this->assertInstanceOf(data\crosslist::class, $crosslist);
        $this->assertEquals('Test SCT Banner', $crosslist->sdidsource);
        $this->assertEquals('XLSAA201740', $crosslist->sdid);
        $this->assertEquals(data\crosslist::GROUP_TYPE_META, $crosslist->type);

        $members = $crosslist->get_members();

        $this->assertInternalType('array', $members);
        $this->assertCount(2, $members);

        $this->assertTrue(isset($members['10001.201740']));
        $member = $members['10001.201740'];
        $this->assertInstanceOf(data\crosslist_member::class, $member);

        $this->assertEquals('Test SCT Banner', $member->sdidsource);
        $this->assertEquals('10001.201740', $member->sdid);
        $this->assertEquals(1, $member->status);
        $this->assertEquals(2, $member->membertype);

        $this->assertTrue(isset($members['10002.201740']));
        $member = $members['10002.201740'];
        $this->assertInstanceOf(data\crosslist_member::class, $member);

        $this->assertEquals('Test SCT Banner', $member->sdidsource);
        $this->assertEquals('10002.201740', $member->sdid);
        $this->assertEquals(1, $member->status);
        $this->assertEquals(2, $member->membertype);

        // Now make sure we get the right default type.
        settings_helper::temp_set('xlstype', data\crosslist::GROUP_TYPE_META);
        $node->TYPE->set_data('MeRgE');
        $crosslist = $converter->process_xml_to_data($node);
        $this->assertEquals(data\crosslist::GROUP_TYPE_MERGE, $crosslist->type);

        // For unknown, return the current default;
        $node->TYPE->set_data('Unknown');
        $crosslist = $converter->process_xml_to_data($node);
        $this->assertEquals(data\crosslist::GROUP_TYPE_META, $crosslist->type);

        settings_helper::temp_set('xlstype', data\crosslist::GROUP_TYPE_MERGE);
        $crosslist = $converter->process_xml_to_data($node);
        $this->assertEquals(data\crosslist::GROUP_TYPE_MERGE, $crosslist->type);

        // Now check that a missing node is correct.
        unset($node->TYPE);
        $crosslist = $converter->process_xml_to_data($node);
        $this->assertEquals(data\crosslist::GROUP_TYPE_MERGE, $crosslist->type);

        settings_helper::temp_set('xlstype', data\crosslist::GROUP_TYPE_META);
        $crosslist = $converter->process_xml_to_data($node);
        $this->assertEquals(data\crosslist::GROUP_TYPE_META, $crosslist->type);
    }
}
