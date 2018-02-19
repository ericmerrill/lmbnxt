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
 * Object that represents the internal data structure of a term object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class term extends base {
    /**
     * The table name of this object.
     */
    const TABLE = 'enrol_lmb_terms';

    /**
     * The class of the Moodle converter for this data object.
     */
    const MOODLE_CLASS = '\\enrol_lmb\\local\\moodle\\term';

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('id', 'sdidsource', 'sdid', 'referenceagent', 'messagereference', 'description', 'begindate',
                              'enddate', 'sortorder', 'additional', 'timemodified', 'messagetime');

    /** @var array An array of default property->value pairs */
    protected $defaults = array();

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array('beginrestrict' => 'handler_boolean',
                                'endrestrict' => 'handler_boolean',
                                'enrollaccept' => 'handler_boolean',
                                'enrollallowed' => 'handler_boolean',
                                'begindate' => 'handler_date',
                                'enddate' => 'handler_date');

    /** @var An array of preexisting term object to use. */
    protected static $terms = array();

    protected $donotempty = array('sdidsource', 'sdid', 'referenceagent', 'messagereference', 'sortorder');

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

        $id = $this->__get('sdid');
        $source = $this->__get('sdidsource');
        $source = (empty($source) ? "(empty)" : $source);
        $desc = $this->__get('description');
        if (empty($id)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_bad_term');
        } else {
            logging::instance()->log_line("Term \"{$desc}\", ID \"{$id}\" from \"{$source}\"".$extramsg);
        }
    }

    /**
     * Returns a cached object of the specified array.
     *
     * @param string $sdid The ID of the term.
     * @return false|term
     */
    public static function get_term($sdid) {
        global $DB;

        if (isset(self::$terms[$sdid])) {
            return self::$terms[$sdid];
        }

        $params = array('sdid' => $sdid);

        $record = $DB->get_record(static::TABLE, $params);

        if (empty($record)) {
            self::$terms[$sdid] = false;
            return false;
        }

        $term = new self();
        $term->load_from_record($record);

        self::$terms[$sdid] = $term;

        return $term;

        // TODO testing save caching?
        /*
        if (!isset(self::$terms[$sdid])) {
            $params = array('sdid' => $sdid);

            self::$terms[$sdid] = $DB->get_record(static::TABLE, $params);
        }

        return self::$terms[$sdid];
        */
    }

    public function get_moodle_converter() {
        return new moodle\category();
    }

}
