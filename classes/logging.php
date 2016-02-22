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
 * Logging object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

/**
 * An object that handles logging for LMB.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logging {
    /** @var logging The singular instances of logging */
    protected static $instance;

    /** @var int Our nesting depth */
    protected $depth = 0;

    /** @var int Our nesting depth */
    protected $buffer = array();

    /** @var int The current error level */
    protected $errorlevel = self::ERROR_NONE;

    /** @var int The error level setting */
    // TODO make a setting for this!
    protected $outputerrorlevel = self::ERROR_NONE;

    /**
     * No current error.
     */
    const ERROR_NONE = 0;

    /**
     * Notice/debugging style notices.
     */
    const ERROR_NOTICE = 1;

    /**
     * Warning level errors.
     */
    const ERROR_WARN = 2;

    /**
     * A major error has occurred.
     */
    const ERROR_MAJOR = 3;

    /**
     * Factory method that returns the singular instance of logging.
     *
     * @return logging The logging object
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new logging();
        }

        return self::$instance;
    }

    /**
     * Protected constructor, please use the static instance method.
     */
    protected function __construct() {
        // Nothing to do here. Just here to prevent incorrect usage.
    }

    // Log a line with an optional error level.
    public function log_line($line, $error = self::ERROR_NONE) {
        $this->set_error_level($error);

        // If above threashold, output now, otherwise buffer.
        if ($this->errorlevel >= $this->outputerrorlevel) {
            $this->output_line($line);
        } else {
            $this->buffer_line($line);
        }
    }

    // Set the error level, and output buffere if we should.
    public function set_error_level($error) {
        if ($error > $this->errorlevel) {
             $this->errorlevel = $error;
        }

        // If error level above threashold, flush the buffer.
        if ($this->errorlevel >= $this->outputerrorlevel) {
            $this->flush_buffer();
        }
    }

    // Output the start of a message block then increase level.
    public function start_message($line) {
        $this->log_line($line);
        $this->depth++;
    }

    // Add a depth level.
    public function add_level() {
        $this->depth++;
    }

    // End a depth level.
    public function end_level() {
        $this->depth--;
    }

    // End a complete message and reset.
    public function end_message($error = self::ERROR_NONE) {
        $this->set_error_level($error);

        $this->depth = 0;
        $this->purge_buffer();
    }

    // Get the indenting prefix of the line.
    protected function get_line_prefix() {
        return str_repeat('  ', $this->depth);
    }

    // Output a line to the user.
    protected function output_line($line) {
        mtrace($this->get_line_prefix().$line);
    }


    // Add a line to the message buffer.
    protected function buffer_line($line) {
        $this->buffer[] = $this->get_line_prefix().$line;
    }

    // Output and clear the buffer.
    protected function flush_buffer() {
        foreach ($this->buffer as $line) {
            $this->output_line($line);
        }

        $this->purge_buffer();
    }

    // Clear the buffer.
    protected function purge_buffer() {
        $this->buffer = array();
    }
}
