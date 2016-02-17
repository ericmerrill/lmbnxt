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


class xml_parser_testcase extends advanced_testcase {

    public function test_parser() {
        $this->resetAfterTest(true);

        $parser = new \enrol_lmb\parser();
        $parser->process_string('<person><n1>vn1</n1><n2><nc>vnc</nc><nc>vnc</nc></n2></person>');
        $processor = $parser->get_processor();
        $node = $processor->get_previous_node();
        //print_r($node);
        $this->assertTrue(isset($node->n1));
        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $node->n1);
        $this->assertTrue($node->n1->has_data());
        $this->assertEquals('vn1', $node->n1->get_value());

        $this->assertTrue(isset($node->n2));
        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $node->n2);
        $this->assertFalse($node->n2->has_data());
        $this->assertNull($node->n2->get_value());

        $this->assertInternalType('array', $node->n2->nc);

        $this->assertFalse(isset($node->n3));




        //$this->assertEquals(, 'person');
    }

}
