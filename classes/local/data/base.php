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

use enrol_lmb\logging;
use enrol_lmb\local\moodle;

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
    protected $dbkeys = array('id', 'additional', 'timemodified', 'messagetime');

    /** @var array An array of default property->value pairs */
    protected $defaults = array();

    /** @var array An array of keys that should not be blanked out on update if missing */
    protected $donotempty = array();

    /** @var array|null An array of keys that should be blanked out on update if missing. Used instead of donotempty if exists. */
    protected $emptyonmissing = null;

    /** @var object Object that contains additional data about the object. This will be JSON encoded. */
    protected $additionaldata;

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array();

    protected $existing = false;

    /**
     * The table name of this object.
     */
    const TABLE = 'base';

    /**
     * The class of the Moodle converter for this data object.
     */
    const MOODLE_CLASS = false;

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->record = new \stdClass();
        $this->additionaldata = new \stdClass();
        $this->donotempty[] = 'id';
        $this->donotempty[] = 'messagetime';
    }

    /**
     * Log a unique line to id this object.
     */
    abstract public function log_id();

    /**
     * Gets (by reference) the passed property.
     *
     * @param string $name Name of property to get
     * @return mixed The property
     */
    public function &__get($name) {
        // First check the DB keys, then additional.
        if (in_array($name, $this->dbkeys)) {
            if ($name == 'additional') {
                // Allows easier interaction with outside scripts of DB modification than serialize.
                $this->record->$name = json_encode($this->additionaldata, JSON_UNESCAPED_UNICODE);
//                $this->record->$name = $this->export_additional();
                return $this->record->$name;
            }
            if (!isset($this->record->$name) && isset($this->defaults[$name])) {
                return $this->defaults[$name];
            }
            return $this->record->$name;
        }
        if (!isset($this->additionaldata->$name) && isset($this->defaults[$name])) {
            return $this->defaults[$name];
        }
        return $this->additionaldata->$name;

    }

    protected function export_additional() {
        $additionaldefaults = array_diff($this->defaults, $this->dbkeys);
        $additional = clone $this->additionaldata;

        foreach ($additionaldefaults as $key) {
            if (!isset($additional->$key)) {
                $additional->$key = $this->__get('key');
            }
        }

        $json = json_encode($additional, JSON_UNESCAPED_UNICODE);

        return $json;
    }

    /**
     * Set a property, either in the db object, ot the additional data object
     *
     * @param string $name Name of property to set
     * @param string $value The value
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
     * Set a property, either in the db object, ot the additional data object, but skipping the handler.
     *
     * @param string $name Name of property to set
     * @param string $value The value
     */
    public function direct_set($name, $value) {
        if (in_array($name, $this->dbkeys)) {
            $this->record->$name = $value;
            return;
        }
        $this->additionaldata->$name = $value;
    }

    /**
     * Unset the passed property.
     *
     * @param string $name Name of property to unset
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
     * @param string $name Name of property to set
     * @return bool True if the property is set
     */
    public function __isset($name) {
        if (isset($this->defaults[$name])) {
            return true;
        }
        if (in_array($name, $this->dbkeys)) {
            return isset($this->record->$name);
        }
        return isset($this->additionaldata->$name);
    }

    /**
     * Concerts an incoming value to 1 or 0 for database storage.
     *
     * @param string $name Name of property to convert
     * @param string $value The value
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

    /**
     * Concerts an incoming date string to a timestamp.
     *
     * @param string $name Name of property to convert
     * @param string $value The value
     * @return int The new property value
     */
    protected function handler_date($name, $value) {
        if (is_int($value) || ctype_digit($value)) {
            // If this is either an actual integer, or all the characters are ints, then don't convert.
            return (int)$value;
        }

        // TODO This really needs timezone work...
        // Need to convert to straight date in some cases...
        $time = strtotime($value.' UTC');

        if ($time === false) {
            logging::instance()->log_line("Could not convert time \"{$value}\".", logging::ERROR_WARN);
            return 0;
        }

        return $time;
    }

    public function load_from_record($record) {
        $this->record = $record;
        $this->existing = clone $record;
        $this->additionaldata = json_decode($record->additional);
    }

    public function save_to_db() {
        $this->update_if_needed();
    }

    /**
     * Updates or inserts the record if it has changed.
     */
    protected function update_if_needed() {
        global $DB;

        if (is_null($this->existing)) {
            $this->existing = $this->get_record();
        }

        $existing = $this->existing;
        try {
            if ($existing) {
                // If this is existing, set the id.
                $this->__set('id', $existing->id);
            } else {
                // Insert the record because it doesn't exist.
                $new = $this->convert_to_db_object();
                $id = $DB->insert_record(static::TABLE, $new);
                if (!$id) {
                    throw new \enrol_lmb\local\exception\message_exception('exception_insert_failure');
                }
                $this->__set('id', $id);
                $new->id = $id;
                $this->existing = $new;
                logging::instance()->log_line('Inserting into database');
                return;
            }

            // Get the current as a db object.
            $new = $this->convert_to_db_object();

            $needsupdate = false;
            $newmessagetime = false;
            foreach ($this->dbkeys as $key) {
                if ($key === 'timemodified') {
                    // We ignore the timemodified column.
                    continue;
                }

                if ($key === 'messagetime') {
                    if ($new->messagetime != $existing->messagetime) {
                        // If the values don't match, skip the rest.
                        $newmessagetime = true;
                        break;
                    }
                }

                if ($new->$key != $existing->$key) {
                    // If the values don't match, skip the rest.
                    $needsupdate = true;
                    break;
                }
            }

            if (!$needsupdate) {
                if ($newmessagetime) {
                    $DB->set_field(static::TABLE, 'messagetime', $new->messagetime, ['id' => $new->id]);
                    logging::instance()->log_line('Only messagetime updated');
                    return;
                } else {
                    // Nothing to update.
                    logging::instance()->log_line('No database update needed');
                    return;
                }
            }

            if ($DB->update_record(static::TABLE, $new)) {
                // Updated the record.
                logging::instance()->log_line('Updated database record');
            } else {
                // There was some error.
                throw new \enrol_lmb\local\exception\message_exception('exception_update_failure');
            }
        } catch (\dml_exception $ex) {
            // TODO - better error handling.
            throw new \enrol_lmb\local\exception\message_exception('exception_update_failure', '', null, $ex->getMessage());
        }
    }

    /**
     * Converts this data object into a database record.
     *
     * @return object The object converted to a DB object.
     */
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

    public function load_existing() {
        $this->existing = $this->get_record();

        if (empty($this->existing)) {
            return;
        }

        $this->load_from_record($this->existing);
    }

    /**
     * Get the existing record for this object and merge it as needed with the internal record.
     */
    public function merge_existing() {
        $this->existing = $this->get_record();

        if (empty($this->existing)) {
            return;
        }

        $existingobj = new static();
        $existingobj->load_from_record($this->existing);

        if (is_array($this->emptyonmissing)) {
            foreach ($this->dbkeys as $key) {
                if ($key === 'additional') {
                    continue;
                }
                if (!isset($this->record->$key) && $existingobj->__isset($key) && !in_array($key, $this->emptyonmissing)) {
                    $this->direct_set($key, $existingobj->$key);
                }
            }

            $keys = $existingobj->get_additional_keys();

            foreach ($keys as $key) {
                if (!isset($this->additionaldata->$key) && !in_array($key, $this->emptyonmissing)) {
                    $this->direct_set($key, $existingobj->$key);
                }
            }

        } else {
            foreach ($this->donotempty as $key) {
                // We have to do a special isset check, because we want to exclude default value returns.
                if (in_array($key, $this->dbkeys)) {
                    $isset = isset($this->record->$key);
                } else {
                    $isset = isset($this->additionaldata->$key);
                }
                if (!$isset && $existingobj->__isset($key)) {
                    $this->__set($key, $existingobj->__get($key));
                }
            }
        }
    }

    public function get_additional_keys() {
        return array_keys((array)$this->additionaldata);
    }

    /**
     * Retreive an exiting db record for this record.
     *
     * @return object|false The record or false if not found.
     */
    protected function get_record() {
        global $DB;

        $params = array('sdid' => $this->__get('sdid'));

        return $DB->get_record(static::TABLE, $params);
    }

    public function exists() {
        if ($this->get_record()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the moodle converter for this data object.
     *
     * @return false|moodle\base
     */
    public function get_moodle_converter() {
        return false;
    }

    /**
     * Do any cleanups after loading from XML.
     */
//     public function post_xml_load() {
//
//     }
}
