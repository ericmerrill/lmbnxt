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
 * The primary controller for file based imports.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();


/**
 * An object that provides settings for the plugin.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings {

    static protected $settingobj = null;

    protected $settings = null;

    static public function get_settings() {
        if (empty(static::$settingobj)) {
            static::$settingobj = new static();
        }

        return static::$settingobj;
    }

    public function __construct() {
        $this->settings = get_config('enrol_lmb');
    }

    public function get($key) {
        if (!isset($this->setting->$key)) {
            return null;
        }

        return $this->setting->$key;
    }
}
