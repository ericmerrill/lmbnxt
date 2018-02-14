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
 * Works on types of messages.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\processors\xml;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\exception;
use enrol_lmb\local\response;
use enrol_lmb\local\moodle;
use enrol_lmb\message;
use enrol_lmb\local\status;

/**
 * Class for working with messages from XML.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var array|null Array of mappings loaded from JSON */
    protected $mappings = null;

    /** @var enrol_lmb\local\data\base The currently in progress data object */
    protected $dataobj = null;

    /** @var message The message object that this applies to */
    protected $message = null;

    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\base';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = false;

    const CATEGORY = false;

    /**
     * Processes the passed xml_node into a data object of the current type.
     *
     * @param xml_node $node The node to work on
     * @return enrol_lmb\local\data\base
     */
    public function process_xml_to_data($node) {
        $class = static::DATA_CLASS;
        $this->dataobj = new $class();

        $this->pre_mappings();

        // First we are going to use the simple static mappings.
        $this->apply_mappings($node);
        $this->dataobj->merge_existing();

        // Do any post mapping work that we might want to.
        $this->post_mappings();


        return $this->dataobj;
    }

    /**
     * Return a response object.
     *
     * @return response\base
     */
    public function get_response_object() {
        return new response\xml();
    }

    /**
     * Set the message object that this object can work with.
     *
     * @param message $message
     */
    public function set_message(message $message) {
        $this->message = $message;
    }

    /**
     * Loads the mapping JSON into the this object.
     */
    protected function load_mappings() {
        global $CFG;
        if (!static::MAPPING_PATH) {
            return;
        }
        $path = $CFG->dirroot.static::MAPPING_PATH;

        if (!file_exists($path)) {
            return;
        }
        $json = file_get_contents($path);
        $this->mappings = json_decode($json, true);
    }

    /**
     * Uses the mapping file to process the XML object into an internal data object.
     *
     * Recusive.
     *
     * @param xml_node $xml The XML node to process
     * @param array $mappings Current mapping object for recusion
     */
    protected function apply_mappings(\enrol_lmb\local\xml_node $xml, $mappings = null) {
        if (is_null($mappings)) {
            // Start with the base mapping.
            $mappings = $this->mappings;
        }

        // For through each mapping.
        foreach ($mappings as $name => $mapping) {
            if (!isset($xml->$name)) {
                continue;
            }

            if (is_string($mapping)) {
                // This means we just associate directly to a field.
                $this->process_node_array_field($xml->$name, $mapping);
            } else if (is_array($mapping)) {
                if (isset($mapping['lmbinternal'])) {
                    // Special array for processing a field.
                    $this->process_node_array_field($xml->$name, $mapping);
                } else {
                    // This means that we are moving into a sub-level recursively.
                    if (is_array($xml->$name)) {
                        // Special case where there are multple matching records under the same name.
                        $array = $xml->$name;
                        foreach ($array as $part) {
                            $this->apply_mappings($part, $mapping);
                        }
                    } else {
                        // Recursively move into the mapping and object.
                        $this->apply_mappings($xml->$name, $mapping);
                    }
                }
            }
        }
    }

    /**
     * Do any work that we might want to after loading all the data from mappings.
     */
    protected function post_mappings() {
        if (isset($this->dataobj)) {
            $this->dataobj->messagetime = time();
        }
        return;
    }

    /**
     * Do any work that we might want to before loading all the data from mappings.
     */
    protected function pre_mappings() {
        return;
    }

    /**
     * Process a terminal node of a mapping. Itterates if passed an array.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_node_array_field($node, $mapping) {
        if (is_array($node)) {
            foreach ($node as $n) {
                $this->process_field($n, $mapping);
            }
        } else {
            $this->process_field($node, $mapping);
        }
    }

    /**
     * Process a terminal node of a mapping.
     *
     * @param xml_node $node The XML node to process
     * @param array $mapping The mapping for the field
     */
    protected function process_field(\enrol_lmb\local\xml_node $node, $mapping) {
        if (is_string($mapping)) {
            // Simple string means that this is a strait mapping.
            if ($node->has_data()) {
                $this->dataobj->$mapping = $node->get_value();
            }
        } else if (is_array($mapping) && isset($mapping['lmbinternal'])) {
            // LMB internal mapping array.
            if (isset($mapping['function'])) {
                // If a function is set, just call it, passing the node and mapping.
                $func = $mapping['function'];
                $this->$func($node, $mapping);
                return;
            }

            if (isset($mapping['fields'])) {
                foreach ($mapping['fields'] as $field) {
                    $this->process_field($node, $field);
                }
                return;
            }

            $matched = true;
            if (isset($mapping['reqattrs'])) {
                // This means we require attributes to continue.
                $matched = true;
                // We must match all required attributes to include.
                foreach ($mapping['reqattrs'] as $attr) {
                    if ($node->get_attribute($attr['name']) != $attr['value']) {
                        $matched = false;
                        break;
                    }
                }
            }

            // If we matched, then set the value.
            if ($matched) {
                $key = $mapping['objname'];
                // If attr is set, then we match to an attribute value. Otherwise we match to the node value.
                if (isset($mapping['attr'])) {
                    $value = $node->get_attribute($mapping['attr']);
                } else {
                    $value = $node->get_value();
                }
                if (isset($mapping['type']) && $mapping['type'] == 'array') {
                    // Type:array means we are going to store values in an array.
                    if (!isset($this->dataobj->$key)) {
                        $this->dataobj->$key = array();
                    }
                    $this->dataobj->{$key}[] = $value;
                } else if (isset($mapping['type']) && $mapping['type'] == 'bool') {
                    // Technically, XML spec only allows values of true, false, 1, and 0, case sensitive.
                    if ($value === '1' || strcasecmp($value, 'true') === 0) {
                        $boolvalue = true;
                    } else if ($value === '0' || strcasecmp($value, 'false') === 0) {
                        $boolvalue = false;
                    } else {
                        throw new exception\message_exception('exception_xml_boolean', '', $value);
                    }

                    $this->dataobj->$key = $boolvalue;
                } else {
                    $this->dataobj->$key = $value;
                }
            }
        } else {
            debugging('Non-lmbinternal array passed to process_field', DEBUG_DEVELOPER);
        }
    }

    /**
     * Return a generic success message.
     *
     * @return status\base
     */
    public function get_success_status() {
        $status = new status\base();
        $status->set_success(true);

        return $status;
    }

    /**
     * Return a generic failure message.
     *
     * @return status\base
     */
    public function get_failure_status() {
        $status = new status\base();
        $status->set_success(false);

        return $status;
    }
}
