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

defined('MOODLE_INTERNAL') || die();

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class xml {
    protected $data = null;
    protected $mappings = null;

    const TYPE = 'base';

    public static function get_paths() {
        debugging('Function \\enrol_lmb\\local\\types\\base::get_paths must be implemented by child classes.', DEBUG_DEVELOPER);
    }

    public function start_object() {
        $this->data = new \stdClass();
    }

    abstract public function process_data($data);

    public function end_object() {
    print "END";
        var_dump($this->data);
        $this->data = null;

    }

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
    protected function apply_mappings($tags, $mappings = null) {
        if (!is_array($tags)) {
            debugging('Function \\enrol_lmb\\local\\types\\base::apply_mappings received non-array.', DEBUG_DEVELOPER);
            return false;
        }

        if (is_null($mappings)) {
            $mappings = $this->mappings;
        }

        foreach ($tags as $name => $value) {
            if (!array_key_exists($name, $mappings)) {
                continue;
            }

            if (is_string($mappings[$name])) {
                $this->process_tag($value, $mappings[$name]);
            } else if (is_array($mappings[$name])) {
                if (array_key_exists('lmbinternal', $mappings[$name])) {
                    $this->process_tag($value, $mappings[$name]);
                } else {
                    $this->apply_mappings($value, $mappings[$name]);
                }
            }
        }

    }

    protected function process_tag($value, $mapping) {
        if (is_string($mapping)) {
            if (is_array($value)) {
                if (array_key_exists('cdata', $value)) {
                    $this->data->$mapping = $value['cdata'];
                }
            } else {
                $this->data->$mapping = $value;
            }
        } else if (is_array($mapping) && array_key_exists('lmbinternal', $mapping)) {

        }
    }

}
