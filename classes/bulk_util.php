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
use enrol_lmb\settings;
use enrol_lmb\date_util;

/**
 * A tool for working with bulk jobs.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_util {

    public function get_terms_in_timeframe($start, $end = false, $limitsource = false) {
        global $DB;

        if (empty($end)) {
            $end = time();
        }

        $output = [];

        // First we get any terms that were updated directly.
        $params = ['start' => $start, 'end' => $end];
        $select = 'messagetime >= :start AND messagetime <= :end';

        if ($limitsource) {
            $select .= " AND sdidsource = :source";

            $params['source'] = $limitsource;
        }

        $terms = $DB->get_fieldset_select(term::TABLE, 'sdid', $select, $params);

        if ($terms) {
            foreach ($terms as $term) {
                $output[$term]['termupdate'] = 1;
            }
        }

        // Next we want any course sections that were updated.
        $sql = "SELECT termsdid, COUNT(id) AS cnt
                  FROM {".section::TABLE."}
                 WHERE messagetime >= :start AND messagetime <= :end";

        if ($limitsource) {
            $sql .= " AND sdidsource = :source";
        }

        $sql .= " GROUP BY termsdid";

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
                 WHERE cm.messagetime >= :start AND cm.messagetime <= :end";

        if ($limitsource) {
            $sql .= " AND cm.sdidsource = :source";
        }

        $sql .= " GROUP BY section.termsdid";

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
                   AND enrol.status = :status";

        if ($limitsource) {
            $sql .= " AND enrol.membersdidsource = :source";
        }

        $sql .= " GROUP BY section.termsdid";

        $params['status'] = 1;
        $terms = $DB->get_records_sql($sql, $params);

        if ($terms) {
            foreach ($terms as $term) {
                $output[$term->termsdid]['enrolupdates'] = $term->cnt;
            }
        }

        foreach ($output as $termid => &$term) {
            $term['totalactiveenrols'] = $this->get_term_enrols_active_count($termid, $limitsource);
            $term['estimatedbulkdrops'] = $this->get_term_enrols_to_drop_count($termid, $start, $limitsource);

            $estpercent = 0;
            if (isset($term['totalactiveenrols']) && $term['totalactiveenrols']) {
                $estpercent = round(($term['estimatedbulkdrops'] / $term['totalactiveenrols']) * 100, 2);
            }

            $term['estimatedbulkpercent'] = $estpercent;
        }

        return $output;
    }

    /**
     * Returns the count of currently active enrolments in the term.
     *
     * @param string $termsdid The term sdid to check
     * @return int
     */
    public function get_term_enrols_active_count($termsdid, $limitsource = false) {
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

        if ($limitsource) {
            $sql .= " AND enrol.membersdidsource = :source";

            $params['source'] = $limitsource;
        }

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get the count of records that would be dropped before
     *
     * @param string $termsdid The sdid of the term
     * @param int $time The time that marked the start of the bulk run
     * @return int
     */
    public function get_term_enrols_to_drop_count($termsdid, $time, $limitsource = false) {
        global $DB;

        $sql = "SELECT COUNT(enrol.id) AS cnt
                  FROM {".person_member::TABLE."} enrol
             LEFT JOIN {".section::TABLE."} section
                    ON enrol.groupsdid = section.sdid
                 WHERE (enrol.messagetime < :reftime
                       OR (enrol.messagetime IS NULL
                           AND
                           enrol.timemodified < :reftime2))
                   AND (section.termsdid = :term
                       OR (section.termsdid IS NULL -- This catches cases where we don't have the section yet.
                           AND
                           enrol.groupsdid LIKE :termfind))
                   AND enrol.status = :status";

        $params = ['term' => $termsdid, 'status' => 1, 'reftime' => $time, 'reftime2' => $time, 'termfind' => '%.'.$termsdid];

        if ($limitsource) {
            $sql .= " AND enrol.membersdidsource = :source";

            $params['source'] = $limitsource;
        }

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Drop enrollments that have not received an update.
     *
     * @param string $termsdid The sdid of the term
     * @param int $time The time that marked the start of the bulk run
     */
    public function drop_old_term_enrols($termsdid, $time, $limitsource = false) {
        global $DB;

        $log = logging::instance();
        $settings = settings::get_settings();

        // Check that we aren't exceeding the drop limit.
        $active = $this->get_term_enrols_active_count($termsdid, $limitsource);
        if (empty($active)) {
            $log->log_line('No active enrollments in the selection. Skipping term.', logging::ERROR_WARN);
            $log->end_message();
            return;
        }
        $drop = $this->get_term_enrols_to_drop_count($termsdid, $time, $limitsource);
        $percent = round(($drop / $active) * 100, 1);

        $log->log_line("Processing old drops for Term {$termsdid} using the reference time of ".userdate($time)." ({$time}).");
        $log->start_level();
        $log->log_line("Approximately {$drop} enrollments out of {$active} active ({$percent}%) found to drop.");

        if ($percent > $settings->get('dropprecentlimit')) {
            $log->log_line('Term drops exceeds limit in settings. Skipping term.', logging::ERROR_WARN);
            $log->end_message();
            return;
        }

        // We only get the record ids now so that we get the most up to date record when we actually use it.
        $sql = "SELECT enrol.id
                  FROM {".person_member::TABLE."} enrol
             LEFT JOIN {".section::TABLE."} section
                    ON enrol.groupsdid = section.sdid
                 WHERE (enrol.messagetime < :reftime
                       OR (enrol.messagetime IS NULL
                           AND
                           enrol.timemodified < :reftime2))
                   AND (section.termsdid = :term
                       OR (section.termsdid IS NULL -- This catches cases where we don't have the section yet.
                           AND
                           enrol.groupsdid LIKE :termfind))
                   AND enrol.status = :status";

        $params = ['term' => $termsdid, 'status' => 1, 'reftime' => $time, 'reftime2' => $time, 'termfind' => '%.'.$termsdid];

        if ($limitsource) {
            $sql .= " AND enrol.membersdidsource = :source";

            $params['source'] = $limitsource;
        }

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

        $log->end_message();
    }

    public function drop_old_crosslist_members($termsdid, $starttime, $limitsource = false) {

    }

    /**
     * Based on the supplied time and term, adjust term dates to add one day to the start and end days.
     *
     * This is done because of a bug in ILP that supplies incorrect course dates when used with bulk.
     *
     * @param string $termsdid The sdid of the term
     * @param int $starttime The time that marked the start of the bulk run
     * @param int $endtime If set, limits the end of the bulk run
     */
    public function adjust_term_section_dates($termsdid, $starttime, $endtime = false, $limitsource = false) {
        global $DB;

        // Next we want any course sections that were updated.
        $sql = "SELECT id FROM {".section::TABLE."}
                 WHERE messagetime >= :start
                   AND termsdid = :termsdid";

        $params = ['start' => $starttime, 'termsdid' => $termsdid];

        if ($endtime) {
            $sql .= " AND messagetime < :end";
            $params['end'] = $endtime;
        }

        if ($limitsource) {
            $sql .= " AND sdidsource = :source";

            $params['source'] = $limitsource;
        }

        $sectionids = $DB->get_fieldset_sql($sql, $params);

        if (empty($sectionids)) {
            return;
        }

        foreach ($sectionids as $sectionid) {
            $section = section::get_for_id($sectionid);

            if (empty($section)) {
                // Missing section.
                continue;
            }

            $section->log_id();

            if (isset($section->begindate_raw)) {
                $time = date_util::correct_ilp_timeframe_quirk($section->begindate_raw);

                // Add one day, because we are short by 1.
                $time += DAYSECS;

                $section->direct_set('begindate', $time);
            }

            if (isset($section->enddate_raw)) {
                $time = date_util::correct_ilp_timeframe_quirk($section->enddate_raw);

                // Add one day, because we are short by 1.
                $time += DAYSECS;

                $section->direct_set('enddate', $time);
            }

            $section->save_to_db();

            $converter = $section->get_moodle_converter();
            if ($converter) {
                $converter->convert_to_moodle($section);
            }
        }
    }
}
