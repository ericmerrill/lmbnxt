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
    const TABLE = 'enrol_lmb_person';

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('sdidsource', 'sdid', 'sctid', 'logonid', 'emailid', 'fullname', 'nickname',
                              'familyname', 'givenname', 'email', 'rolestudent', 'rolestaff', 'rolefaculty',
                              'rolealumni', 'roleprospectivestudent', 'additional');

    /** @var array Array of allowed additional keys */
    /*protected $additionalkeys = array('phonevoice', 'phonemobile', 'middlename', 'gender', 'streetadr', 'city', 'region',
                                      'postalcode', 'country', 'customroles', 'userid', 'prefix', 'suffix', 'televoice',
                                      'telemobile', 'major', 'title', 'customrole', 'degree');*/

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array('rolestudent' => 'handler_boolean',
                                'rolestaff' => 'handler_boolean',
                                'rolefaculty' => 'handler_boolean',
                                'rolealumni' => 'handler_boolean',
                                'roleprospectivestudent' => 'handler_boolean');

    /**
     * Log a unique line to id this object.
     */
    public function log_id() {
        $id = $this->__get('sdid');
        $source = $this->__get('sdidsource');
        if (empty($id) || empty($source)) {
            //logging::instance()->log_line("Person object has no id or source", logging::ERROR_MAJOR);
            throw new \enrol_lmb\local\exception\message_exception('exception_bad_person');
        } else {
            logging::instance()->log_line("Person ID \"{$id}\" from \"{$source}\"");
        }
    }
}
