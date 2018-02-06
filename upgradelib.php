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
 * A library to help with upgrades.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\settings;
use enrol_lmb\local\data\crosslist;

function enrol_lmb_upgrade_promote_column($table, $column) {
    global $DB;

    $total = $DB->count_records($table);
    $pbar = new progress_bar('enrollmb'.$table.$column, 500, true);

    $recordset = $DB->get_recordset($table, null, '', 'id, '.$column.', additional');
    $i = 0;
    foreach ($recordset as $record) {
        $i++;
        $pbar->update($i, $total, "Table {$table} and column {$column} - $i/$total.");
        if (empty($record->additional)) {
            continue;
        }
        $additional = json_decode($record->additional);

        if (isset($additional->$column)) {
            $record->$column = $additional->$column;
            unset($additional->$column);
            $record->additional = json_encode($additional, JSON_UNESCAPED_UNICODE);

            try {
                $DB->update_record($table, $record);
            } catch (dml_exception $ex) {
                // We just skip over DML exceptions. Could be wrong data type being upgraded.
                continue;
            }
        }

    }

    $recordset->close();
}

function enrol_lmb_upgrade_migrate_user_source_value($value) {
    $new = false;

    switch ($value) {
        case 'email':
            $new = settings::USER_NAME_EMAIL;
            break;
        case 'emailname':
            $new = settings::USER_NAME_EMAILNAME;
            break;
        case 'loginid':
            $new = settings::USER_NAME_LOGONID;
            break;
        case 'sctid':
            $new = settings::USER_NAME_SCTID;
            break;
        case 'emailid':
            $new = settings::USER_NAME_EMAILID;
            break;
        case 'other':
            $new = settings::USER_NAME_OTHER;
            break;
        default:
            // Assume that if the value is numeric, then it was already converted.
            if (is_numeric($value)) {
                $new = $value;
            }
            break;
    }

    return $new;
}

function enrol_lmb_upgrade_migrate_cat_type_value($value) {
    $new = false;

    switch ($value) {
        case 'term':
            $new = settings::COURSE_CATS_TERMS;
            break;
        case 'dept':
            $new = settings::COURSE_CATS_DEPTS;
            break;
        case 'deptcode':
            $new = settings::COURSE_CATS_DEPTS_SHORT;
            break;
        case 'termdept':
            $new = settings::COURSE_CATS_TERM_DEPTS;
            break;
        case 'termdeptcode':
            $new = settings::COURSE_CATS_TERM_DEPTS_SHORT;
            break;
        case 'other':
            $new = settings::COURSE_CATS_SELECTED;
            break;
        default:
            // Assume that if the value is numeric, then it was already converted.
            if (is_numeric($value)) {
                $new = $value;
            }
            break;
    }

    return $new;
}

function enrol_lmb_upgrade_migrate_crosslist_type_value($value) {
    $new = false;

    switch ($value) {
        case 'meta':
            $new = crosslist::GROUP_TYPE_META;
            break;
        case 'merge':
            $new = crosslist::GROUP_TYPE_MERGE;
            break;
        default:
            // Assume that if the value is numeric, then it was already converted.
            if (is_numeric($value)) {
                $new = $value;
            }
            break;
    }

    return $new;
}
