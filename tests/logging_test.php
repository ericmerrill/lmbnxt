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

use enrol_lmb\logging;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class logging_testcase extends advanced_testcase {

    public static function setUpBeforeClass() {
        // This will create a logging tester and insert it into the factory instance.
        new logging_helper();
    }

    public function setUp() {
        $log = logging::instance();
        $log->test_get_flush_buffer();

        $log->set_logging_level(logging::ERROR_NONE);
    }

    public function test_instance() {
        // We need to get the testable instance and hold it for a minute.
        $testinstance = logging::instance();

        // Clear the factory instance.
        logging_helper::test_set_instance(null);

        // Use the factory to get an instance.
        $log = logging::instance();
        $this->assertInstanceOf(logging::class, $log);

        // Make sure the factory will return the same instance again.
        $this->assertSame($log, logging::instance());

        // Now put the testable back in place.
        logging_helper::test_set_instance($testinstance);
    }

    public function test_true_output() {
        // We need to get the testable instance and hold it for a minute.
        $testinstance = logging::instance();

        // Clear the factory instance.
        logging_helper::test_set_instance(null);

        // Use the factory to get an instance.
        $log = logging::instance();
        $this->assertInstanceOf(logging::class, $log);

        $log->set_logging_level(logging::ERROR_NONE);

        $expected = "Logged Line\n".
                    "Logged Line2\n".
                    "None 1\n".
                    "  None 1 sub\n";
        $this->expectOutputString($expected);
        $log->log_line("Logged Line");
        $log->log_line("Logged Line2");
        $log->start_message("None 1");
        $log->log_line("None 1 sub");
        $log->end_message();

        // Now put the testable back in place.
        logging_helper::test_set_instance($testinstance);
    }

    public function test_logging() {
        $log = logging::instance();

        $log->log_line("Logged Line");
        $log->log_line("Logged Line2");
        $this->assertEquals("Logged Line\nLogged Line2\n", $log->test_get_flush_buffer());
    }

    protected function log_all_messages($log) {
        $log->start_message("Notice 1");
        $log->log_line("Notice 1 sub", logging::ERROR_NOTICE);
        $log->end_message();

        $log->start_message("None 1");
        $log->log_line("None 1 sub");
        $log->end_message();

        $log->start_message("Notice 2");
        $log->log_line("Notice 2 sub", logging::ERROR_NOTICE);
        $log->end_message();

        $log->start_message("Warn 1");
        $log->log_line("Warn 1 sub", logging::ERROR_WARN);
        $log->end_message();

        $log->start_message("Major 1");
        $log->log_line("Major 1 sub", logging::ERROR_MAJOR);
        $log->end_message();
    }

    public function test_all() {
        $log = logging::instance();

        $log->set_logging_level(logging::ERROR_NONE);

        $this->log_all_messages($log);

        $expected = "Notice 1\n".
                    "  NOTICE: Notice 1 sub\n".
                    "None 1\n".
                    "  None 1 sub\n".
                    "Notice 2\n".
                    "  NOTICE: Notice 2 sub\n".
                    "Warn 1\n".
                    "  WARNING: Warn 1 sub\n".
                    "Major 1\n".
                    "  FATAL: Major 1 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());
    }

    public function test_notice() {
        $log = logging::instance();

        $log->set_logging_level(logging::ERROR_NOTICE);

        $this->log_all_messages($log);

        $expected = "Notice 1\n".
                    "  NOTICE: Notice 1 sub\n".
                    "Notice 2\n".
                    "  NOTICE: Notice 2 sub\n".
                    "Warn 1\n".
                    "  WARNING: Warn 1 sub\n".
                    "Major 1\n".
                    "  FATAL: Major 1 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());
    }

    public function test_warn() {
        $log = logging::instance();

        $log->set_logging_level(logging::ERROR_WARN);

        $this->log_all_messages($log);

        $expected = "Warn 1\n".
                    "  WARNING: Warn 1 sub\n".
                    "Major 1\n".
                    "  FATAL: Major 1 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());
    }

    public function test_major() {
        $log = logging::instance();

        $log->set_logging_level(logging::ERROR_MAJOR);

        $this->log_all_messages($log);

        $expected = "Major 1\n".
                    "  FATAL: Major 1 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());
    }

    public function test_depth() {
        $log = logging::instance();

        $log->start_message("Level 1");
        $log->log_line("Level 1 sub");
        $log->end_message();

        $expected = "Level 1\n".
                    "  Level 1 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());

        $log->start_level();
        $log->start_message("Level 2");
        $log->log_line("Level 2 sub");
        $log->end_message();

        $expected = "  Level 2\n".
                    "    Level 2 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());

        $log->start_level();
        $log->start_message("Level 3");
        $log->log_line("Level 3 sub");
        $log->end_message();

        $expected = "    Level 3\n".
                    "      Level 3 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());

        $log->end_level();
        $log->start_message("Level 2");
        $log->log_line("Level 2 sub");
        $log->end_message();

        $expected = "  Level 2\n".
                    "    Level 2 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());

        $log->end_level();
        $log->start_message("Level 1");
        $log->log_line("Level 1 sub");
        $log->end_message();

        $expected = "Level 1\n".
                    "  Level 1 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());

        // Check that we can't get into "negative" levels.
        $log->end_level();
        $log->start_message("Level 1");
        $log->log_line("Level 1 sub");
        $log->end_message();

        $expected = "Level 1\n".
                    "  Level 1 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());

        $log->start_level();
        $log->start_message("Level 2");
        $log->log_line("Level 2 sub");
        $log->end_message();

        $expected = "  Level 2\n".
                    "    Level 2 sub\n";
        $this->assertEquals($expected, $log->test_get_flush_buffer());

        $log->test_reset_level();
    }
}
