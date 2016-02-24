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
use enrol_lmb\logging;

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

    protected $defaults = array();

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
            if ($name == 'additional') {
                $this->record->$name = serialize($this->additionaldata);
                return $this->record->$name;
            }
            if (isset($this->record->$name)) {
                return $this->record->$name;
            } else {
                if (isset($this->defaults[$name])) {
                    return $this->defaults[$name];
                } else {
                    return $this->record->$name;
                }
            }
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
            return;
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
            return;
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

    public function save_to_db() {
        $this->update_if_needed();
    }

    /**
     * Updates or inserts the record if it has changed.
     */
    protected function update_if_needed() {
        global $DB;

        $existing = $this->get_record();
        try {
            if ($existing) {
                // If this is existing, set the id.
                $this->__set('id', $existing->id);
            } else {
                // Insert the record because it doesn't exist
                $new = $this->convert_to_db_object();
                $id = $DB->insert_record(static::TABLE, $new);
                if (!$id) {
                    throw new \enrol_lmb\local\exception\message_exception('exception_insert_failure');
                }
                $this->__set('id', $id);
                logging::instance()->log_line('Inserting into database');
                return;
            }

            // Get the current as a db object.
            $new = $this->convert_to_db_object();

            $needsupdate = false;
            foreach ($this->dbkeys as $key) {
                if ($key === 'timemodified') {
                    // We ignore the timemodified column.
                    continue;
                }

                if ($new->$key != $existing->$key) {
                    // If the values don't match, skip the rest.
                    $needsupdate = true;
                    break;
                }
            }

            if (!$needsupdate) {
                // Nothing to update.
                logging::instance()->log_line('No database update needed');
                return;
            }

            if ($DB->update_record('enrol_lmb_person', $new)) {
                // Updated the record.
                logging::instance()->log_line('Updated database record');
            } else {
                // There was some error.
                throw new \enrol_lmb\local\exception\message_exception('exception_update_failure');
            }
        } catch (\dml_exception $ex) {
            throw new \enrol_lmb\local\exception\message_exception('exception_update_failure', '', null, $ex->getMessage());
        }
    }

    protected function convert_to_db_object() {
        $obj = new \stdClass();

        foreach ($this->dbkeys as $key) {
            if ($key == 'timemodified') {
                $obj->$key = time();
                continue;
            }
            $obj->$key = $this->__get($key);
        }

        return $obj;
    }

    abstract protected function get_record();
}
