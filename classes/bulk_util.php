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
              GROUP BY section.termsdid";

        $terms = $DB->get_records_sql($sql, $params);

        if ($terms) {
            foreach ($terms as $term) {
                $output[$term->termsdid]['enrolupdates'] = $term->cnt;
            }
        }

        return $output;
    }
}
