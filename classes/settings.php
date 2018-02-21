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

    const USER_NAME_EMAIL = 1;
    const USER_NAME_EMAILNAME = 2;
    const USER_NAME_LOGONID = 3;
    const USER_NAME_SCTID = 4;
    const USER_NAME_EMAILID = 5;
    const USER_NAME_OTHER = 6;

    const USER_NICK_DISABLED = 0;
    const USER_NICK_FIRST = 1;
    const USER_NICK_ALT = 2;

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

    /**
     * Get the value of a setting, null if not set.
     *
     * @param string $key The setting key
     * @return mixed
     */
    public function get($key) {
        if (!isset($this->settings->$key)) {
            return null;
        }

        return $this->settings->$key;
    }

    /**
     * Set the setting for a key value. Unset value on null
     *
     * @param string $key The key
     * @param mixed $value The value to set.
     */
    public function set($key, $value) {
        $this->settings->$key = $value;

        if (is_null($value)) {
            unset_config($key, 'enrol_lmb');
        } else {
            set_config($key, $value, 'enrol_lmb');
        }
    }
}
