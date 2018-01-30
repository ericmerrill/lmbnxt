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

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class controller_test extends xml_helper {

    public function test_import_file() {
        global $CFG;
        $this->resetAfterTest();

        $controller = new \enrol_lmb\controller();

        // No file error.
        $this->resetDebugging();
        $controller->import_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/does_not_exist.xml');
        $this->assertDebuggingCalled();

        // Now a working file.
        $this->resetDebugging();
        $controller->import_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/basic_file.xml');
        $this->assertDebuggingNotCalled();

        // Now test config path.
        set_config('xmlpath', $CFG->dirroot.'/enrol/lmb/tests/fixtures/basic_file.xml', 'enrol_lmb');
        $this->resetDebugging();
        $controller->import_file();
        $this->assertDebuggingNotCalled();
    }

    public function test_process_xml_object() {
        global $CFG;
        $this->resetAfterTest();

        $controller = new \enrol_lmb\controller();

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/section_replace.xml');
//print "<pre>";print_r($node);print "</pre>";
        //$controller->process_xml_object($node);

    }

}
