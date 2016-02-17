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
 * Tests for the XML node.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class xml_node_testcase extends xml_helper {
    public function test_itterator() {
        // Test the node itterator.
        $xml = '<person><n1>V1</n1><n2>V2</n2><n2>V3</n2><n3>V4</n3><n2>V5</n2></person>';
        $node = $this->get_node_for_xml($xml);

        $i = 0;
        $expected = array(array('n' => 'n1', 'v' => 'V1'),
                          array('n' => 'n2', 'v' => 'V2'),
                          array('n' => 'n2', 'v' => 'V3'),
                          array('n' => 'n2', 'v' => 'V5'),
                          array('n' => 'n3', 'v' => 'V4'));
        foreach ($node as $key => $child) {
            $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $child);
            $this->assertEquals($expected[$i]['v'], $child->get_value());
            $this->assertEquals($expected[$i]['n'], $key);
            $i++;
        }
    }

}
