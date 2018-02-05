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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

use enrol_lmb\controller;
use enrol_lmb\logging;

class response_lis2_test extends xml_helper {

    public function test_get_response_body() {
        global $CFG;

        $this->resetAfterTest(true);

        $log = new logging_helper();
        $log->set_logging_level(logging::ERROR_NOTICE);

        $xml = file_get_contents($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/data/member_replace_teacher.xml');

        $controller = new controller();
        $rawresponse = $controller->process_xml_message($xml, true);

        // Now lets load it into an object to work with.
        $parser = new \enrol_lmb\parser();
        $parser->add_type('imsx_syncResponseHeaderInfo');
        $parser->add_type('replaceMembershipResponse');
        $parser->process_string($rawresponse);
        $processor = $parser->get_processor();
        $header = $processor->get_previous_header_node();
        $response = $processor->get_previous_node();

        $this->assertEquals('IMSX_SYNCRESPONSEHEADERINFO', $header->get_name());

        $messageid = $header->IMSX_STATUSINFO->IMSX_MESSAGEREFIDENTIFIER->get_value();
        $this->assertEquals('meassageId', $messageid);

        $status = $header->IMSX_STATUSINFO->IMSX_CODEMAJOR->get_value();
        $this->assertEquals('success', $status);

        $sev = $header->IMSX_STATUSINFO->IMSX_SEVERITY->get_value();
        $this->assertEquals('status', $sev);

        $this->assertEquals('REPLACEMEMBERSHIPRESPONSE', $response->get_name());
    }

}
