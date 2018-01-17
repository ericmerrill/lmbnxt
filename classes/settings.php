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

    const CREATE_COURSE_VISIBLE = 0;
    const CREATE_COURSE_HIDDEN = 1;
    const CREATE_COURSE_CRON = 2;

    const COURSE_CATS_TERMS = 1;
    const COURSE_CATS_DEPTS = 2;
    const COURSE_CATS_TERM_DEPTS = 3;
    const COURSE_CATS_DEPTS_SHORT = 4;
    const COURSE_CATS_TERM_DEPTS_SHORT = 5;
    const COURSE_CATS_SELECTED = 6;

    protected static $settingobj = null;

    protected $settings = null;

    public static function get_settings() {
        if (empty(static::$settingobj)) {
            static::$settingobj = new static();
        }

        return static::$settingobj;
    }

    protected function __construct() {
        $this->settings = get_config('enrol_lmb');
    }

    public function get($key) {
        if (!isset($this->settings->$key)) {
            return null;
        }

        return $this->settings->$key;
    }
}
