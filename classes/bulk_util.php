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
 * A tool for working with bulk jobs.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\data\term;
use enrol_lmb\local\data\section;
use enrol_lmb\local\data\crosslist_member;
use enrol_lmb\local\data\person_member;

/**
 * A tool for working with bulk jobs.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_util {

    public function get_terms_in_timeframe($start, $end = null) {
        global $DB;

        if (empty($end)) {
            $end = time();
        }

        $output = [];

        // First we get any terms that were updated directly.
        $params = ['start' => $start, 'end' => $end];
        $select = 'messagetime >= :start AND messagetime <= :end';

        $terms = $DB->get_fieldset_select(term::TABLE, 'sdid', $select, $params);

        if ($terms) {
            foreach ($terms as $term) {
                $output[$term]['termupdate'] = 1;
            }
        }

        // Next we want any course sections that were updated.
        $sql = "SELECT termsdid, COUNT(id) AS cnt
                  FROM {".section::TABLE."}
                 WHERE messagetime >= :start AND messagetime <= :end
              GROUP BY termsdid";

        $terms = $DB->get_records_sql($sql, $params);

        if ($terms) {
            foreach ($terms as $term) {
                $output[$term->termsdid]['sectionupdate'] = $term->cnt;
            }
        }

        // Next crosslist updates.
        $sql = "SELECT section.termsdid, COUNT(cm.id) AS cnt
                  FROM {".crosslist_member::TABLE."} cm
            INNER JOIN {".section::TABLE."} section
                    ON cm.sdid = section.sdid
                 WHERE cm.messagetime >= :start AND cm.messagetime <= :end
              GROUP BY section.termsdid";

        $terms = $DB->get_records_sql($sql, $params);

        if ($terms) {
            foreach ($terms as $term) {
                $output[$term->termsdid]['crossmemberupdate'] = $term->cnt;
            }
        }

        // Now enrollments.
        $sql = "SELECT section.termsdid, COUNT(enrol.id) AS cnt
                  FROM {".person_member::TABLE."} enrol
            INNER JOIN {".section::TABLE."} section
                    ON enrol.groupsdid = section.sdid
                 WHERE enrol.messagetime >= :start AND enrol.messagetime <= :end
                   AND enrol.status = :status
              GROUP BY section.termsdid";

        $params['status'] = 1;
        $terms = $DB->get_records_sql($sql, $params);

        if ($terms) {
            foreach ($terms as $term) {
                $output[$term->termsdid]['enrolupdates'] = $term->cnt;
            }
        }

        return $output;
    }

    /**
     * Returns the count of currently active enrolments in the term.
     *
     * @param string $termsdid The term sdid to check
     * @return int
     */
    public function get_term_enrols_active_count($termsdid) {
        global $DB;

        $sql = "SELECT COUNT(enrol.id) AS cnt
                  FROM {".person_member::TABLE."} enrol
             LEFT JOIN {".section::TABLE."} section
                    ON enrol.groupsdid = section.sdid
                 WHERE (section.termsdid = :term
                       OR (section.termsdid IS NULL -- This catches cases where we don't have the section yet.
                           AND
                           enrol.groupsdid LIKE :termfind))
                   AND enrol.status = :status";

        $params = ['term' => $termsdid, 'status' => 1, 'termfind' => '%.'.$termsdid];

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get the count of records that would be dropped before
     *
     * @param string $termsdid The sdid of the term
     * @param int $time The time that marked the start of the bulk run
     * @return int
     */
    public function get_term_enrols_to_drop_count($termsdid, $time) {
        global $DB;

        $sql = "SELECT COUNT(enrol.id) AS cnt
                  FROM {".person_member::TABLE."} enrol
             LEFT JOIN {".section::TABLE."} section
                    ON enrol.groupsdid = section.sdid
                 WHERE enrol.messagetime < :reftime
                   AND (section.termsdid = :term
                       OR (section.termsdid IS NULL -- This catches cases where we don't have the section yet.
                           AND
                           enrol.groupsdid LIKE :termfind))
                   AND enrol.status = :status";

        $params = ['term' => $termsdid, 'status' => 1, 'reftime' => $time, 'termfind' => '%.'.$termsdid];

        return $DB->count_records_sql($sql, $params);
    }

    public function drop_old_term_enrols($termsdid, $time) {
        global $DB;

        $log = logging::instance();

        // We only get the record ids now so that we get the most up to date record when we actually use it.
        $sql = "SELECT enrol.id
                  FROM {".person_member::TABLE."} enrol
             LEFT JOIN {".section::TABLE."} section
                    ON enrol.groupsdid = section.sdid
                 WHERE enrol.messagetime < :reftime
                   AND (section.termsdid = :term
                       OR (section.termsdid IS NULL -- This catches cases where we don't have the section yet.
                           AND
                           enrol.groupsdid LIKE :termfind))
                   AND enrol.status = :status";

        $params = ['term' => $termsdid, 'status' => 1, 'reftime' => $time, 'termfind' => '%.'.$termsdid];

        $ids = $DB->get_recordset_sql($sql, $params);

        foreach ($ids as $id => $notused) {
            // Load the most up to date record for this id.
            $enrol = person_member::get_for_id($id);

            if (empty($enrol)) {
                // This shouldn't happen, but if it does, it isn't much of a concern.
                debugging('Enrolment '.$id.' was missing when we tried to find it.');
            }

            // Start a log message with this enrols log line.
            $enrol->log_id();
            $log->start_level();

            // This could happen if it takes us a long time to process through the ids.
            if ($enrol->status != 1 || $enrol->messagetime >= $time) {
                $log->log_line('Message time or status changed. Skipping.', logging::ERROR_NOTICE);
                $log->end_message();
                continue;
            }

            $log->log_line('Changing to status 0');
            $enrol->status = 0;
            $enrol->bulkdropped = $time;

            $converter = $enrol->get_moodle_converter();
            if ($converter) {
                $converter->convert_to_moodle($enrol);
            }

            $enrol->save_to_db();

            // Make sure to end the message to reset logging.
            $log->end_message();
        }

        $ids->close();
    }
}
