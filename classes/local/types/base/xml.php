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
     * Uses the mapping file to process chunks of data.
     */
    protected function apply_mappings(\enrol_lmb\local\xml_node $xml, $mappings = null) {
        if (is_null($mappings)) {
            $mappings = $this->mappings;
        }

        foreach ($mappings as $name => $mapping) {
            if (!isset($xml->$name)) {
                continue;
            }

            if (is_string($mapping)) {
                $this->process_tag($xml->$name, $mapping);
            } else if (is_array($mapping)) {
                if (array_key_exists('lmbinternal', $mapping)) {
                    $this->process_tag($xml->$name, $mapping);
                } else {
                    if (is_array($xml->$name)) {
                        debugging("Assumed last mathching child for $name", DEBUG_DEVELOPER);
                        $array = $xml->$name;
                        $this->apply_mappings(end($array), $mapping);
                    } else {
                        $this->apply_mappings($xml->$name, $mapping);
                    }
                }
            }
        }
    }

    protected function process_tag($node, $mapping) {
        if (is_string($mapping)) {
            if (is_object($node)) {
                if ($node->has_data()) {
                    $this->dataobj->$mapping = $node->get_value();
                }
            } else if (is_array($node)) {
                debugging("Assumed last mathching child for $mapping", DEBUG_DEVELOPER);
                $this->process_tag(end($node), $mapping);
            }
        } else if (is_array($mapping) && array_key_exists('lmbinternal', $mapping)) {

        }
    }

}
