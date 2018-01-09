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
 * The base status class.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\status;

defined('MOODLE_INTERNAL') || die();

class base {
    /** @var string A user displayable message */
    protected $description = false;

     /** @var bool If there was success */
    protected $success = true;

    public function set_success(bool $success) {
        $this->success = $success;
    }

    public function get_success() {
        return $this->success;
    }

    public function set_description($message) {
        $this->usermessage = $message;
    }

    public function get_description() {
        return $this->usermessage;
    }

}
