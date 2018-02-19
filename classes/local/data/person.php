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
 * Data model object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\data;
use enrol_lmb\local\types;
use enrol_lmb\local\moodle;
use enrol_lmb\logging;

defined('MOODLE_INTERNAL') || die();

/**
 * Object that represents the internal data structure of a person object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person extends base {
    /**
     * The table name of this object.
     */
    const TABLE = 'enrol_lmb_people';

    /**
     * The class of the Moodle converter for this data object.
     */
    const MOODLE_CLASS = '\\enrol_lmb\\local\\moodle\\user';

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('id', 'sdidsource', 'sdid', 'sctid', 'logonid', 'emailid', 'fullname', 'nickname',
                              'familyname', 'givenname', 'email', 'rolestudent', 'rolestaff', 'rolefaculty',
                              'rolealumni', 'roleprospectivestudent', 'primaryrole', 'additional', 'timemodified', 'messagetime');

    /** @var array An array of default property->value pairs */
    protected $defaults = array('rolestudent' => 0,
                                'rolestaff' => 0,
                                'rolefaculty' => 0,
                                'rolealumni' => 0,
                                'roleprospectivestudent' => 0);

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array('rolestudent' => 'handler_boolean',
                                'rolestaff' => 'handler_boolean',
                                'rolefaculty' => 'handler_boolean',
                                'rolealumni' => 'handler_boolean',
                                'roleprospectivestudent' => 'handler_boolean');

    protected $donotempty = array('sdidsource', 'sdid');

    /**
     * Log a unique line to id this object.
     */
    public function log_id() {
        $id = $this->__get('sdid');
        $source = $this->__get('sdidsource');
        if (empty($id) || empty($source)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_bad_person');
        } else {
            logging::instance()->log_line("Person ID \"{$id}\" from \"{$source}\"");
        }
    }

    public function get_moodle_converter() {
        return new moodle\user();
    }
}
