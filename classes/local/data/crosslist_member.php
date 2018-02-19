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
 * Object that represents the internal data structure of a crosslist member course.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crosslist_member extends base {
    /**
     * The table name of this object.
     */
    const TABLE = 'enrol_lmb_crosslist_members';

    /** @var array Array of keys that go in the database object */
    protected $dbkeys = array('id', 'crosslistid', 'sdidsource', 'sdid', 'status', 'additional', 'timemodified', 'messagetime');

    /** @var array An array of default property->value pairs */
    protected $defaults = array('moodlestatus' => 0);

    /** @var array An array of keys that should not be blanked out on update if missing */
    protected $donotempty = array('sdidsource', 'sdid', 'moodlestatus');

    /** @var array An array of property->function pairs for converting incoming values */
    protected $handlers = array();

    protected $crosslist = false;

    /**
     * Log a unique line to id this object.
     */
    public function log_id() {
        $id = $this->__get('sdid');
        $source = $this->__get('sdidsource');
        $source = (empty($source) ? "(empty)" : $source);

        if (empty($id)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_bad_crosslist_member_id');
        } else {
            logging::instance()->log_line("Member \"{$id}\" from \"{$source}\"");
        }
    }

    /**
     * Retreive an exiting db record for this record.
     *
     * @return object|false The record or false if not found.
     */
    protected function get_record() {
        global $DB;

        if (!$this->__isset('crosslistid')) {
            return false;
        }

        $params = array('sdid' => $this->__get('sdid'),
                        'crosslistid' => $this->__get('crosslistid'));

        return $DB->get_record(static::TABLE, $params);
    }

    public function get_section() {
        $section = new section();
        $section->sdid = $this->__get('sdid');
        $section->sdidsource = $this->__get('sdidsource');
        $section->load_existing();

        if (isset($section->id)) {
            return $section;
        }

        return false;
    }

    public static function get_members_for_section_sdid($sdid, $status = 1, $type = crosslist::GROUP_TYPE_MERGE) {
        global $DB;

        $params = ['coursesdid' => $sdid, 'status' => $status, 'type' => $type];
        $sql = "SELECT cm.*
                  FROM {".static::TABLE."} cm
                  JOIN {".crosslist::TABLE."} cl
                    ON cm.crosslistid = cl.id
                 WHERE cm.sdid = :coursesdid
                   AND cm.status = :status
                   AND cl.type = :type";

        $records = $DB->get_records_sql($sql, $params);

        $results = [];
        if (empty($records)) {
            return $results;
        }

        foreach ($records as $record) {
            $member = new self();
            $member->load_from_record($record);
            $results[] = $member;
        }

        return $results;
    }

}
