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
use enrol_lmb\settings;

/**
 * Object that represents the internal data structure of a course object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class member_group extends base {
    /**
     * The table name of this object.
     */
    const TABLE = 'enrol_lmb_member_group';

    /**
     * The class of the Moodle converter for this data object.
     */
    const MOODLE_CLASS = '\\enrol_lmb\\local\\moodle\\member_group';

    const GROUP_TYPE_META = 1;
    const GROUP_TYPE_MERGE = 2;

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('id', 'membersdidsource', 'membersdid', 'status', 'groupsdid', 'groupsdidsource', 'type',
                              'additional', 'timemodified');

    /** @var array An array of default property->value pairs */
    protected $defaults = array();

    /** @var array An array of keys that should not be blanked out on update if missing */
    protected $donotempty = array('type');

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array('type' => 'handler_group_type');

    public function __construct() {
        parent::__construct();
        // We want to set the default based on a setting.
        $this->defaults['type'] = settings::get_settings()->get('xlstype');
    }

    /**
     * Log a unique line to id this object.
     */
    public function log_id() {
        $id = $this->__get('membersdid');
        $source = $this->__get('membersdidsource');
        $gid = $this->__get('groupsdid');
        $gsource = $this->__get('groupsdidsource');
        // TODO.
        if (empty($id) || empty($source) || empty($gid) || empty($gsource)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_bad_member_group');
        } else {
            logging::instance()->log_line("Group \"{$id}\" from \"{$source}\" membership into \"{$gid}\" from \"{$gsource}\"");
        }
    }

    /**
     * Retreive an exiting db record for this record.
     *
     * @return object|false The record or false if not found.
     */
    protected function get_record() {
        global $DB;

        $params = array('membersdid' => $this->__get('membersdid'),
                        'groupsdid' => $this->__get('groupsdid'));

        return $DB->get_record(static::TABLE, $params);
    }

    /**
     * Handles the couple different ways that the type field could be represented.
     *
     * @param string $name Not used
     * @param string $value The value
     * @return int The new property value
     */
    protected function handler_group_type($name, $value) {
        if (is_numeric($value) && ($value == static::GROUP_TYPE_MERGE || $value == static::GROUP_TYPE_META)) {
            return (int)$value;
        }

        if (strcasecmp('merge', $value) === 0) {
            return static::GROUP_TYPE_MERGE;
        } else if (strcasecmp('meta', $value) === 0) {
            return static::GROUP_TYPE_META;
        }

        return settings::get_settings()->get('xlstype');
    }

}
