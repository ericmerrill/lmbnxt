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
    /** @var object The database record object */
    protected $record;

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array();

    /** @var array Array of allowed additional keys */
    /*protected $additionalkeys = array();*/

    /** @var object Object that contains additional data about the object */
    protected $additionaldata;

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array();

    /**
     * The table name of this object.
     */
    const TABLE = 'base';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->record = new \stdClass();
        $this->additionaldata = new \stdClass();
    }

    /**
     * Log a unique line to id this object.
     */
    abstract public function log_id();

    /**
     * Gets (by reference) the passed property.
     *
     * $param string $name Name of property to get
     * @return mixed The property
     */
    public function &__get($name) {
        // First check the DB keys, then additional
        if (in_array($name, $this->dbkeys)) {
            return $this->record->$name;
        }
        return $this->additionaldata->$name;

    }

    /**
     * Set a property, either in the db object, ot the additional data object
     *
     * $param string $name Name of property to set
     * $param string $value The value
     */
    public function __set($name, $value) {
        if (array_key_exists($name, $this->handlers)) {
            $func = $this->handlers[$name];
            $v = $this->$func($name, $value);
        } else {
            $v = $value;
        }

        if (in_array($name, $this->dbkeys)) {
            $this->record->$name = $v;
        }
        $this->additionaldata->$name = $v;
    }

    /**
     * Unset the passed property.
     *
     * $param string $name Name of property to unset
     */
    public function __unset($name) {
        if (in_array($name, $this->dbkeys)) {
            unset($this->record->$name);
        }
        unset($this->additionaldata->$name);
    }

    /**
     * Check if a property is set.
     *
     * $param string $name Name of property to set
     * @return bool True if the property is set
     */
    public function __isset($name) {
        if (in_array($name, $this->dbkeys)) {
            return isset($this->record->$name);
        }
        return isset($this->additionaldata->$name);
    }

    /**
     * Concerts an incoming value to 1 or 0 for database storage.
     *
     * $param string $name Name of property to convert
     * $param string $value The value
     * @return int The new property value
     */
    protected function handler_boolean($name, $value) {
        if ((bool)$value) {
            $v = 1;
        } else {
            $v = 0;
        }

        return $v;
    }
}
