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
 * Tests for the logging class.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

use \enrol_lmb\logging;

class logging_testcase extends advanced_testcase {

    public function test_instance() {
        $log = logging::instance();
        $this->assertSame($log, logging::instance());

        //expectOutputString
        //https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.output
    }

    public function test_logging() {
        $log = logging::instance();

        $log->set_logging_level(\enrol_lmb\logging::ERROR_NONE);

        $this->expectOutputString("Logged Line\nLogged Line2\n");
        $log->log_line("Logged Line");
        $log->log_line("Logged Line2");
    }

    public function test_notice() {
        $log = logging::instance();

        $log->set_logging_level(\enrol_lmb\logging::ERROR_NOTICE);

        $expected = "Notice 1\n".
                    "  Notice 1 sub\n".
                    "Notice 2\n".
                    "  Notice 2 sub\n";

        $this->expectOutputString($expected);

        $log->start_message("Notice 1");
        $log->log_line("Notice 1 sub", \enrol_lmb\logging::ERROR_NOTICE);
        $log->end_message();

        $log->start_message("None 1");
        $log->log_line("None 1 sub");
        $log->end_message();

        $log->start_message("Notice 2");
        $log->log_line("Notice 2 sub", \enrol_lmb\logging::ERROR_NOTICE);
        $log->end_message();
    }

}
