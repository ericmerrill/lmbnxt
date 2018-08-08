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
 * A tool for working with locks.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;

/**
 * A tool for working with locks.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lock_factory {
    /** @var core\lock\lock_factory An instance of the core lock factory */
    protected static $factory = null;

    /**
     * Get a lock for the provided resource
     *
     * @param string $resource - The identifier for the lock.
     * @param int $timeout - The number of seconds to wait for a lock before giving up.
     * @param int $maxlifetime - The number of seconds to wait before reclaiming a stale lock.
     * @return \core\lock\lock|boolean - An instance of \core\lock\lock if the lock was obtained, or false.
     */
    public static function get_lock($resource, $timeout = 10, $maxlifetime = 600) {
        if (is_null(self::$factory)) {
            self::$factory = lock_config::get_lock_factory('enrol_lmb');
        }

        // We shouldn't need the prefix, but core poorly defined the API.
        $lock = self::$factory->get_lock('enrol_lmb'.$resource, $timeout, $maxlifetime);

        return $lock;
    }

}
