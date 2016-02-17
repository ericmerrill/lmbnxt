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
        $i = 0;
        // Do it again to make sure reset worked.
        foreach ($node as $key => $child) {
            $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $child);
            $this->assertEquals($expected[$i]['v'], $child->get_value());
            $this->assertEquals($expected[$i]['n'], $key);
            $i++;
        }
    }

    public function test_node_finished() {
        $node = new \enrol_lmb\local\xml_node('parent');

        // Just calling a non-existent child to make sure no error is thrown.
        $node->mark_node_finished(array('child', 'subchild'));
    }

    public function test_get_parent() {
        $xml = '<person><n1><n2>V2</n2></n1></person>';
        $parent = $this->get_node_for_xml($xml);

        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $parent);
        $child1 = $parent->n1;
        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $child1);
        $child2 = $child1->n2;
        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $child2);

        $this->assertEquals($child1, $child2->get_parent());
        $this->assertEquals($parent, $child1->get_parent());
        $this->assertNull($parent->get_parent());
    }

    public function test_magic_get() {
        $xml = '<person><n1>V1</n1><n2>V2</n2><n2>V3</n2></person>';
        $node = $this->get_node_for_xml($xml);

        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $node->n1);
        $this->assertEquals('V1', $node->n1->get_value());
        $this->assertInternalType('array', $node->n2);
        $this->assertEquals('V2', $node->n2[0]->get_value());
        $this->assertEquals('V3', $node->n2[1]->get_value());

        // Make sure checks for non-existant child work.
        $this->assertFalse(isset($node->n3));
        $this->assertNull($node->n3);
    }

    public function test_magic_unset() {
        $xml = '<person><n1>V1</n1><n2>V2</n2></person>';
        $node = $this->get_node_for_xml($xml);

        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $node->n1);
        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $node->n2);

        $this->assertTrue(isset($node->n2));
        unset($node->n2);
        $this->assertFalse(isset($node->n2));
        $this->assertNull($node->n2);

        // Make sure no errors when unsetting something that doesn't exist.
        unset($node->n3);
    }

    public function test_attributes() {
        $xml = '<person><n1 a1="v1" a2="v2">V1</n1><n2>V2</n2></person>';
        $node = $this->get_node_for_xml($xml);

        $this->assertEquals('v1', $node->n1->get_attribute('a1'));
        $this->assertEquals('v2', $node->n1->get_attribute('a2'));
        $this->assertNull($node->n1->get_attribute('a3'));

        $this->assertInternalType('array', $node->n1->get_attributes());
        $this->assertCount(2, $node->n1->get_attributes());
        $this->assertEquals('v1', $node->n1->get_attributes()['a1']);
        $this->assertEquals('v2', $node->n1->get_attributes()['a2']);

        $this->assertInternalType('array', $node->n2->get_attributes());
        $this->assertEmpty(0, $node->n2->get_attributes());
    }

    public function test_name() {
        $xml = '<person><n1 a1="v1" a2="v2">V1</n1><n2>V2</n2><n2>V3</n2></person>';
        $node = $this->get_node_for_xml($xml);

        $this->assertEquals('person', $node->get_name());
        $this->assertEquals('n1', $node->n1->get_name());
        $this->assertEquals('n2', $node->n2[0]->get_name());
        $this->assertEquals('n2', $node->n2[1]->get_name());
    }

    public function test_has_data() {
        $xml = '<person><n1 a1="v1" a2="v2"><c1>Something</c1></n1><n2><c1>Data</c1></n2><n3></n3></person>';
        $node = $this->get_node_for_xml($xml);

        $this->assertTrue($node->n1->has_data());
        $this->assertTrue($node->n1->c1->has_data());
        $this->assertFalse($node->n2->has_data());
        $this->assertTrue($node->n2->c1->has_data());

        // Empty nodes contain a empty string value.
        $this->assertTrue($node->n3->has_data());
        $this->assertEquals('', $node->n3->get_value());
    }

    public function test_mixed_case() {
        $xml = '<peRson><node a1="v1" A2="V2">1</node><nOde>2</nOde><NODE>Val3</NODE></peRson>';
        $node = $this->get_node_for_xml($xml);

        $this->assertInstanceOf('\\enrol_lmb\\local\\xml_node', $node);

        // All tag and attribute names should be converted to lowercase.
        // Data should remain in source case.
        $this->assertTrue(isset($node->node));
        $this->assertFalse(isset($node->nOde));
        $this->assertFalse(isset($node->NODE));

        $this->assertInternalType('array', $node->node);
        $this->assertCount(3, $node->node);
        $this->assertEquals(1, $node->node[0]->get_value());
        $this->assertEquals(2, $node->node[1]->get_value());
        $this->assertEquals('Val3', $node->node[2]->get_value());

        $this->assertEquals('v1', $node->node[0]->get_attributes()['a1']);
        $this->assertEquals('V2', $node->node[0]->get_attributes()['a2']);

    }
}
