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
 * Tests for the message object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

use enrol_lmb\message;
use enrol_lmb\controller;
use enrol_lmb\local\processors;
use enrol_lmb\local\response;
use enrol_lmb\logging;
use enrol_lmb\local\status;

class message_test extends xml_helper {

    public static function setUpBeforeClass() {
        // This will create a logging tester and insert it into the factory instance.
        new logging_helper();
    }

    public function test_load_processor() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/person.xml');
        $message = new message(null, $node);

        $this->run_protected_method($message, 'load_processor');

        $this->assertAttributeInstanceOf(processors\xml\person::class, 'processor', $message);
        $this->assertAttributeInstanceOf(response\xml::class, 'response', $message);
    }

    public function test_process_to_data() {
        global $CFG, $DB;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records(\enrol_lmb\local\data\person::TABLE));

        $controller = new controller();
        $controller->set_option('nodb', true);
        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lmb/person.xml');
        $message = new message($controller, $node);

        $this->run_protected_method($message, 'process_to_data');

        // Make sure we still didn't save to DB.
        $this->assertEquals(0, $DB->count_records(\enrol_lmb\local\data\person::TABLE));

        // Now save to the DB.
        $controller->set_option('nodb', false);
        $this->run_protected_method($message, 'process_to_data');
        $this->assertEquals(1, $DB->count_records(\enrol_lmb\local\data\person::TABLE));

        // Reset a little.
        $DB->delete_records(\enrol_lmb\local\data\person::TABLE);
        $this->assertEquals(0, $DB->count_records(\enrol_lmb\local\data\person::TABLE));

        // Now try with a null controller.
        $message = new message(null, $node);
        $this->run_protected_method($message, 'process_to_data');
        $this->assertEquals(1, $DB->count_records(\enrol_lmb\local\data\person::TABLE));

        // Now we are going to inspect what the result was.
        $dataobjs = $this->get_protected_attribute($message, 'dataobjs');
        $this->assertCount(1, $dataobjs);
        $person = $dataobjs[0];

        $this->assertInstanceOf(\enrol_lmb\local\data\person::class, $person);

        // We expect this to throw a caught exception.
        $node = $this->get_node_for_xml('<replaceMembershipRequest></replaceMembershipRequest>');
        $message = new message(null, $node);

        $log = logging::instance();
        $this->run_protected_method($message, 'process_to_data');

        // See that we got the expected message.
        $this->assertContains("FATAL: Membership type could not be found\n", $log->test_get_flush_buffer());
    }

    public function test_get_response() {
        global $CFG;

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/member_replace_teacher.xml');
        $message = new message(null, $node);
        $this->run_protected_method($message, 'load_processor');

        $response = $message->get_response();

        $this->assertInstanceOf(response\lis2::class, $response);
    }

    public function test_get_status() {
        global $CFG;

        $message = new message(null, null);
        $this->assertNull($message->get_status());

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/member_replace_teacher.xml');
        $message = new message(null, $node);
        $this->run_protected_method($message, 'load_processor');
        $status = $message->get_status();
        $this->assertInstanceOf(status\lis2::class, $status);
        $this->assertTrue($status->get_success());
    }

    public function test_get_root_tag() {
        global $CFG;

        $message = new message(null, null);
        $this->assertFalse($message->get_root_tag());

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/member_replace_teacher.xml');
        $message = new message(null, $node);
        $this->assertEquals('REPLACEMEMBERSHIPREQUEST', $message->get_root_tag());
    }
}
