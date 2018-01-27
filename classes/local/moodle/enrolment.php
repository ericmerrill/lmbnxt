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
 * An object for converting data to moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\moodle;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\logging;
use enrol_lmb\settings;
use enrol_lmb\local\data;

require_once($CFG->dirroot.'/user/lib.php');

/**
 * Abstract object for converting a data object to Moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment extends base {
    /**
     * This function takes a data object and attempts to apply it to Moodle.
     *
     * @param data\base $data A data object to process.
     */
    public function convert_to_moodle(\enrol_lmb\local\data\base $data) {
        global $DB;

        if (!($data instanceof data\member_person)) {
            throw new \coding_exception('Expected instance of data\member_person to be passed.');
        }

        $this->data = $data;

        // Check that user exsits.

        // Check that course exists.



    }


}
