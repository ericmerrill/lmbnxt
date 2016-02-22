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
 * Data model object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\data;

defined('MOODLE_INTERNAL') || die();

/**
 * Object that represents the internal data structure.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    protected $record;

    protected $dbkeys = array();

    protected $additionalkeys = array();

    protected $additionaldata;

    protected $handlers = array();

    public function __construct() {
        $this->record = new \stdClass();
        $this->additionaldata = new \stdClass();
    }

    public function &__get($name) {
        if (in_array($name, $this->dbkeys)) {
            return $this->record->$name;
        } else if (in_array($name, $this->additionalkeys)) {
            return $this->additionaldata->$name;
        } else {
            debugging("Cannot get property $name.", DEBUG_DEVELOPER);
            return null;
        }
    }

    public function __set($name, $value) {
        if (in_array($name, $this->dbkeys)) {
            $this->record->$name = $value;
        } else if (in_array($name, $this->additionalkeys)) {
            $this->additionaldata->$name = $value;
        } else {
            debugging("Cannot set property $name.", DEBUG_DEVELOPER);
        }
    }

    public function __unset($name) {
        if (in_array($name, $this->dbkeys)) {
            unset($this->record->$name);
        } else if (in_array($name, $this->additionalkeys)) {
            unset($this->additionaldata->$name);
        } else {
            debugging("Cannot unset property $name.", DEBUG_DEVELOPER);
        }
    }

    public function __isset($name) {
        if (in_array($name, $this->dbkeys)) {
            return isset($this->record->$name);
        } else if (in_array($name, $this->additionalkeys)) {
            return isset($this->additionaldata->$name);
        } else {
            debugging("Cannot check isset of property $name.", DEBUG_DEVELOPER);
            return false;
        }
    }
}
