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

use enrol_lmb\local\xml_node;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class xml_node_testcase extends xml_helper {
    public function test_itterator() {
        // Test the node itterator.
        $xml = '<tests><n1>V1</n1><n2>V2</n2><n2>V3</n2><n3>V4</n3><n2>V5</n2></tests>';
        $node = $this->get_node_for_xml($xml);

        $i = 0;
        $expected = array(array('n' => 'N1', 'v' => 'V1'),
                          array('n' => 'N2', 'v' => 'V2'),
                          array('n' => 'N2', 'v' => 'V3'),
                          array('n' => 'N2', 'v' => 'V5'),
                          array('n' => 'N3', 'v' => 'V4'));
        foreach ($node as $key => $child) {
            $this->assertInstanceOf(xml_node::class, $child);
            $this->assertEquals($expected[$i]['v'], $child->get_value());
            $this->assertEquals($expected[$i]['n'], $key);
            $i++;
        }
        $i = 0;
        // Do it again to make sure reset worked.
        foreach ($node as $key => $child) {
            $this->assertInstanceOf(xml_node::class, $child);
            $this->assertEquals($expected[$i]['v'], $child->get_value());
            $this->assertEquals($expected[$i]['n'], $key);
            $i++;
        }
    }

    public function test_get_parent() {
        $xml = '<tests><n1><n2>V2</n2></n1></tests>';
        $parent = $this->get_node_for_xml($xml);

        $this->assertInstanceOf(xml_node::class, $parent);
        $child1 = $parent->N1;
        $this->assertInstanceOf(xml_node::class, $child1);
        $child2 = $child1->N2;
        $this->assertInstanceOf(xml_node::class, $child2);

        $this->assertEquals($child1, $child2->get_parent());
        $this->assertEquals($parent, $child1->get_parent());
        $this->assertNull($parent->get_parent());
    }

    public function test_magic_get() {
        $xml = '<tests><n1>V1</n1><n2>V2</n2><n2>V3</n2></tests>';
        $node = $this->get_node_for_xml($xml);

        $this->assertInstanceOf(xml_node::class, $node->N1);
        $this->assertEquals('V1', $node->N1->get_value());
        $this->assertInternalType('array', $node->N2);
        $this->assertEquals('V2', $node->N2[0]->get_value());
        $this->assertEquals('V3', $node->N2[1]->get_value());

        // Make sure checks for non-existant child work.
        $this->assertFalse(isset($node->N3));
        $this->assertNull($node->N3);
    }

    public function test_magic_unset() {
        $xml = '<tests><n1>V1</n1><n2>V2</n2></tests>';
        $node = $this->get_node_for_xml($xml);

        $this->assertInstanceOf(xml_node::class, $node->N1);
        $this->assertInstanceOf(xml_node::class, $node->N2);

        $this->assertTrue(isset($node->N2));
        unset($node->N2);
        $this->assertFalse(isset($node->N2));
        $this->assertNull($node->N2);

        // Make sure no errors when unsetting something that doesn't exist.
        unset($node->N3);
    }

    public function test_attributes() {
        $xml = '<tests><n1 a1="v1" a2="v2">V1</n1><n2>V2</n2></tests>';
        $node = $this->get_node_for_xml($xml);

        $this->assertEquals('v1', $node->N1->get_attribute('A1'));
        $this->assertEquals('v2', $node->N1->get_attribute('A2'));
        $this->assertNull($node->N1->get_attribute('A3'));

        $this->assertInternalType('array', $node->N1->get_attributes());
        $this->assertCount(2, $node->N1->get_attributes());
        $this->assertEquals('v1', $node->N1->get_attributes()['A1']);
        $this->assertEquals('v2', $node->N1->get_attributes()['A2']);

        $this->assertInternalType('array', $node->N2->get_attributes());
        $this->assertEmpty(0, $node->N2->get_attributes());
    }

    public function test_name() {
        $xml = '<tests><n1 a1="v1" a2="v2">V1</n1><n2>V2</n2><n2>V3</n2></tests>';
        $node = $this->get_node_for_xml($xml);

        $this->assertEquals('TESTS', $node->get_name());
        $this->assertEquals('N1', $node->N1->get_name());
        $this->assertEquals('N2', $node->N2[0]->get_name());
        $this->assertEquals('N2', $node->N2[1]->get_name());
    }

    public function test_has_data() {
        $xml = '<tests><n1 a1="v1" a2="v2"><c1>Something</c1></n1><n2><c1>Data</c1></n2><n3></n3></tests>';
        $node = $this->get_node_for_xml($xml);

        $this->assertTrue($node->N1->has_data());
        $this->assertTrue($node->N1->C1->has_data());
        $this->assertFalse($node->N2->has_data());
        $this->assertTrue($node->N2->C1->has_data());

        // Empty nodes contain a empty string value.
        $this->assertTrue($node->N3->has_data());
        $this->assertEquals('', $node->N3->get_value());
    }

    public function test_mixed_case() {
        $xml = '<tEsts><node a1="v1" A2="V2">1</node><nOde>2</nOde><NODE>Val3</NODE></tEsts>';
        $node = $this->get_node_for_xml($xml);

        $this->assertInstanceOf(xml_node::class, $node);

        // All tag and attribute names should be converted to lowercase.
        // Data should remain in source case.
        $this->assertTrue(isset($node->NODE));
        $this->assertFalse(isset($node->nOde));
        $this->assertFalse(isset($node->node));

        $this->assertInternalType('array', $node->NODE);
        $this->assertCount(3, $node->NODE);
        $this->assertEquals(1, $node->NODE[0]->get_value());
        $this->assertEquals(2, $node->NODE[1]->get_value());
        $this->assertEquals('Val3', $node->NODE[2]->get_value());

        $this->assertEquals('v1', $node->NODE[0]->get_attributes()['A1']);
        $this->assertEquals('V2', $node->NODE[0]->get_attributes()['A2']);

    }
}
