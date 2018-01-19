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
 * The base abstract response class.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\response;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\controller;
use enrol_lmb\message;

abstract class base {

    /** @var controller The controller object */
    protected $controller = null;

     /** @var message The message object */
    protected $message = null;

    abstract public function get_response_body();

    /**
     * Set a controller for this response to use.
     *
     * @param controller $controller The controller for this response
     */
    public function set_controller($controller) {
        $this->controller = $controller;
    }

    /**
     * Set a message for this response to use.
     *
     * @param message $message The message for this response
     */
    public function set_message(message $message) {
        $this->message = $message;
    }

}
