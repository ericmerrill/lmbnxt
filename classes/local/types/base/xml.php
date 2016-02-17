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

namespace enrol_lmb\local\types\base;
use enrol_lmb\local\types;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for working with messages from XML.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class xml {
    protected $mappings = null;
    protected $dataobj = null;

    const TYPE = 'base';

    abstract public function process_xml_to_data($xmlobd);

    protected function load_mappings() {
        global $CFG;
        $path = $CFG->dirroot.'/enrol/lmb/classes/local/types/'.static::TYPE.'/mappings.json';

        if (!file_exists($path)) {
            return false;
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
                if (array_key_exists('lmbinternal', $mapping)) {
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
            if ($node->has_data()) {
                $this->dataobj->$mapping = $node->get_value();
            }
        } else if (is_array($mapping) && array_key_exists('lmbinternal', $mapping)) {
            $matched = true;
            if (isset($mapping['reqattrs'])) {
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
                $this->dataobj->{$mapping['objname']} = $node->get_value();
            }
        }
    }

}
