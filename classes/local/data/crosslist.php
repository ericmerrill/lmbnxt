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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\data;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\types;
use enrol_lmb\local\moodle;
use enrol_lmb\logging;
use enrol_lmb\settings;

/**
 * Object that represents the internal data structure of a crosslist object.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crosslist extends base {
    /**
     * The table name of this object.
     */
    const TABLE = 'enrol_lmb_crosslists';

    /**
     * The class of the Moodle converter for this data object.
     */
    const MOODLE_CLASS = '\\enrol_lmb\\local\\moodle\\crosslist';

    const GROUP_TYPE_META = 1;
    const GROUP_TYPE_MERGE = 2;

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('id', 'sdidsource', 'sdid', 'type', 'additional', 'timemodified', 'messagetime');

    /** @var array An array of default property->value pairs */
    protected $defaults = array();

    /** @var array An array of keys that should not be blanked out on update if missing */
    protected $donotempty = array('type', 'sdidsource', 'sdid');

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array('type' => 'handler_group_type');

    protected $members = [];

    public function __construct() {
        parent::__construct();
        // We want to set the default based on a setting.
        $this->defaults['type'] = settings::get_settings()->get('xlstype');
    }

    /**
     * Log a unique line to id this object.
     */
    public function log_id() {
        $id = $this->__get('sdid');
        $source = $this->__get('sdidsource');
        $source = (empty($source) ? "(empty)" : $source);

        if (empty($id)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_bad_crosslist_id');
        } else {
            logging::instance()->log_line("Crosslist group \"{$id}\" from \"{$source}\"");
            foreach ($this->members as $member) {
                $member->log_id();
            }
        }
    }

    public function get_existing_members() {
        global $DB;

        if (!$this->__isset('id')) {
            return [];
        }

        $records = $DB->get_records(crosslist_member::TABLE, ['crosslistid' => $this->__get('id')], 'id ASC');

        if (empty($records)) {
            return [];
        }

        $members = [];
        foreach ($records as $record) {
            $member = new crosslist_member();
            $member->load_from_record($record);
            $members[$member->sdid] = $member;
        }

        return $members;
    }

    /**
     * Add a member to this crosslist.
     *
     * @param crosslist_member $child
     */
    public function add_member(crosslist_member $child) {
        $this->members[$child->sdid] = $child;
    }

    public function set_members($members) {
        $this->members = $members;
    }

    /**
     * Return the array of members for this crosslist.
     *
     * @return crosslist_member[]
     */
    public function get_members() {
        return $this->members;
    }

    protected function update_if_needed() {
        parent::update_if_needed();

        foreach ($this->members as $member) {
            $member->crosslistid = $this->__get('id');
            $member->groupsdid = $this->__get('sdid');
            $member->update_if_needed();
        }
    }

    public function merge_existing() {
        parent::merge_existing();

        if (!$this->__isset('id')) {
            return;
        }

        foreach ($this->get_members() as $member) {
            $member->crosslistid = $this->__get('id');
            $member->merge_existing();
        }
    }

    /**
     * Retreive an exiting db record for this record.
     *
     * @return object|false The record or false if not found.
     */
    protected function get_record() {
        global $DB;

        $params = array('sdid' => $this->__get('sdid'));

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

    public function get_moodle_converter() {
        return new moodle\crosslist();
    }

}
