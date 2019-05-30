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
 * Internal message exception.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\exception;

defined('MOODLE_INTERNAL') || die();

/**
 * An exception that is fatal for a message, but the processing can continue.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_lock_exception extends \moodle_exception {
    /**
     * Constructor
     *
     * @param string $errorcode The name of the string to print.
     * @param string $link The url where the user will be prompted to continue.
     *                  If no url is provided the user will be directed to the site index page.
     * @param mixed $a Extra words and phrases that might be required in the error string.
     * @param string $debuginfo optional debugging information.
     */
    public function __construct($errorcode = 'exception_category_lock', $link='', $a=null, $debuginfo=null) {
        parent::__construct($errorcode, 'enrol_lmb', $link, $a, $debuginfo);
    }
}
