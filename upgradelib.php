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
