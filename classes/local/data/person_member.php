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

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\types;
use enrol_lmb\logging;
use enrol_lmb\local\moodle;

/**
 * Object that represents the internal data structure of a course object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person_member extends base {
    /**
     * The table name of this object.
     */
    const TABLE = 'enrol_lmb_person_members';

    /**
     * The class of the Moodle converter for this data object.
     */
    const MOODLE_CLASS = '\\enrol_lmb\\local\\moodle\\enrolment';

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('id', 'membersdidsource', 'membersdid', 'referenceagent', 'messagereference', 'roletype', 'status',
                              'groupsdidsource', 'groupsdid', 'begindate', 'enddate', 'additional', 'timemodified', 'messagetime');

    /** @var array An array of default property->value pairs */
    protected $defaults = array();

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array('beginrestrict' => 'handler_boolean',
                                'endrestrict' => 'handler_boolean',
                                'begindate' => 'handler_date',
                                'enddate' => 'handler_date',
                                'gradable' => 'handler_boolean');

    protected $donotempty = array('membersdidsource', 'membersdid', 'referenceagent', 'messagereference', 'roletype', 'status',
                                  'groupsdidsource', 'groupsdid', 'begindate', 'enddate', 'beginrestrict', 'endrestrict');

    protected $emptyonmissing = array();

    /**
     * Log a unique line to id this object.
     */
    public function log_id() {
        $msgref = $this->__get('messagereference');

        $extramsg = "";
        if (!empty($msgref)) {
            // This means we are a LIS message, Add a message ID.
            $extramsg = " (LIS \"{$msgref}\")";
        }

        $id = $this->__get('membersdid');
        $source = $this->__get('membersdidsource');
        $source = (empty($source) ? "(empty)" : $source);
        $gid = $this->__get('groupsdid');
        $gsource = $this->__get('groupsdidsource');
        $gsource = (empty($gsource) ? "(empty)" : $gsource);

        if (empty($id) || empty($gid)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_bad_person_member');
        } else {
            logging::instance()->log_line("Person \"{$id}\" from \"{$source}\" membership into \"{$gid}\" from \"{$gsource}\"".
                    $extramsg);
        }
    }

    /**
     * Retreive an exiting db record for this record.
     *
     * @return object|false The record or false if not found.
     */
    protected function get_record() {
        global $DB;

        if ($this->__isset('messagereference')) {
            $record = $DB->get_record(static::TABLE, ['messagereference' => $this->__get('messagereference')]);

            if ($record) {
                return $record;
            }
        }

        $params = ['membersdid' => $this->__get('membersdid'),
                   'groupsdid' => $this->__get('groupsdid'),
                   'roletype' => $this->__get('roletype')];

        return $DB->get_record(static::TABLE, $params);
    }

    /**
     * Get the moodle converter for this data object.
     *
     * @return false|moodle\base
     */
    public function get_moodle_converter() {
        return new moodle\enrolment();
    }

}
