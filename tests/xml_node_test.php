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
 * An activity to interface with WebEx.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class xml_node_testcase extends advanced_testcase {

    public function test_itterator() {
        // Test the parse itterator.
        $parser = new \enrol_lmb\parser();
        $parser->process_string('<person><n1>V1</n1><n2>V2</n2><n2>V3</n2><n3>V4</n3></person>');
        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();

        $i = 1;
        foreach ($node as $child) {
            $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $child);
            $this->assertEquals('V'.$i, $child->get_value());
            $i++;
        }
    }

}
