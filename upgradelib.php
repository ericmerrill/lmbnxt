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
use enrol_lmb\logging;
use enrol_lmb\local\data;
use enrol_lmb\local\moodle;

require_once($CFG->dirroot.'/enrol/lmb/lib.php');

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
            $new = data\crosslist::GROUP_TYPE_META;
            break;
        case 'merge':
            $new = data\crosslist::GROUP_TYPE_MERGE;
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

function enrol_lmb_upgrade_migrate_course_hidden_value($value) {
    $new = false;

    switch ($value) {
        case 'never':
            $new = settings::CREATE_COURSE_VISIBLE;
            break;
        case 'cron':
            $new = settings::CREATE_COURSE_CRON;
            break;
        case 'always':
            $new = settings::CREATE_COURSE_HIDDEN;
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

function enrol_lmb_upgrade_migrate_old_enrols($progress = false) {
    global $DB;

    if ($progress) {
        $count = $DB->count_records('enrol_lmb_old_enrolments');
        if ($count) {
            $progress->start_progress('', $count);
        } else {
            $progress->start_progress('');
        }
    }

    $records = $DB->get_recordset('enrol_lmb_old_enrolments');
    $count = 0;
    foreach ($records as $record) {
        $count++;
        if ($progress) {
            $progress->progress($count);
        }

        $member = new data\person_member();
        $member->membersdid = $record->personsourcedid;
        $member->groupsdid = $record->coursesourcedid;

        $role = str_pad($record->role, 2, '0', STR_PAD_LEFT);
        $member->roletype = $role;

        if ($member->exists()) {
            $message = "Skipping {$member->membersdid} in {$member->groupsdid}, role {$role} because it already exists.";
            logging::instance()->log_line($message);
            continue;
        }

        $member->term = $record->term;
        $member->status = $record->status;
        $member->membertype = 1;

        if (!empty($record->beginrestrict)) {
            $member->beginrestrict = $record->beginrestrict;
        }
        if (!empty($record->endrestrict)) {
            $member->endrestrict = $record->endrestrict;
        }

        if (!empty($record->beginrestricttime)) {
            $member->direct_set('begindate', $record->beginrestricttime);;
        }
        if (!empty($record->endrestricttime)) {
            $member->direct_set('enddate', $record->endrestricttime);;
        }

        if ($record->succeeded) {
            $member->moodlestatus = $member->status;
        } else {
            if ($member->status) {
                $member->moodlestatus = 0;
            } else {
                $member->moodlestatus = 1;
            }
        }

        if ($record->gradable) {
            $member->gradable = $record->gradable;
            $member->midtermgrademode = $record->midtermgrademode;
            $member->midtermsubmitted = $record->midtermsubmitted;
            $member->finalgrademode = $record->finalgrademode;
            $member->finalsubmitted = $record->finalsubmitted;
        }


        $member->migrated = 1;

        //print "<pre>";var_export($member);print "</pre>";
        $member->save_to_db();
    }

    if ($progress) {
        $progress->end_progress();
    }

    $records->close();
}

function enrol_lmb_upgrade_migrate_old_crosslists($progress = false) {
    global $DB;

    if ($progress) {
        $count = $DB->count_records('enrol_lmb_old_crosslists');
        if ($count) {
            $progress->start_progress('', $count);
        } else {
            $progress->start_progress('');
        }
    }

    $records = $DB->get_recordset('enrol_lmb_old_crosslists', null, 'crosslistsourcedid ASC');

    $previousxls = '';
    $crosslist = false;

    $count = 0;
    foreach ($records as $record) {
        $count++;
        if ($progress) {
            $progress->progress($count);
        }

        $crosslistid = $record->crosslistsourcedid;
        if ($crosslistid != $previousxls) {
            if ($crosslist) {
                $crosslist->save_to_db();
                enrol_lmb_upgrade_migrate_crosslist_enrols($crosslist);
                $crosslist = false;
            }

            if ($DB->record_exists(data\crosslist::TABLE, ['sdid' => $crosslistid])) {
                logging::instance()->log_line("Skipping {$crosslistid} because it already exists.");
                continue;
            }

            $previousxls = $crosslistid;

            $crosslist = new data\crosslist();
            $crosslist->sdid = $crosslistid;
            if (!empty($record->crosslistsourcedid)) {
                $crosslist->sdidsource = $record->crosssourcedidsource;
            }
            $crosslist->type = $record->type;
            $crosslist->migrated = 1;
        }

        $member = new data\crosslist_member();
        $member->sdid = $record->coursesourcedid;
        if (!empty($record->coursesourcedidsource)) {
            $member->sdidsource = $record->coursesourcedidsource;
        }
        $member->status = $record->status;
        if (!empty($record->crosslistgroupid)) {
            $member->groupid = $record->crosslistgroupid;
        }
        if (!empty($record->manual)) {
            $member->manual = $record->manual;
        }

        $member->migrated = 1;
        $member->membertype = 2;

        $crosslist->add_member($member);
    }

    if ($crosslist) {
        $crosslist->save_to_db();
        enrol_lmb_upgrade_migrate_crosslist_enrols($crosslist);
    }

    if ($progress) {
        $progress->end_progress();
    }

    $records->close();
}

function enrol_lmb_upgrade_migrate_crosslist_enrols($crosslist) {
    global $DB;

    // Only do merges.
    if ($crosslist->type != data\crosslist::GROUP_TYPE_MERGE) {
        return;
    }

    $course = $DB->get_record('course', ['idnumber' => $crosslist->sdid]);
    if (!$course) {
        logging::instance()->log_line("Skipping enrols for {$crosslist->sdid}, course not found.");
        return;
    }

    $members = $crosslist->get_members();
    $enrol = new enrol_lmb_plugin();

    $params = ['enrol' => 'lmb', 'courseid' => $course->id, 'customchar1' => null, 'customchar2' => null];
    $existing = $DB->get_record('enrol', $params);

    foreach ($members as $member) {
        if ($member->status) {
            $instance = $enrol->get_instance($course, $member->sdid);
            if (empty($existing)) {
                logging::instance()->log_line("Skipping enrols for {$crosslist->sdid}, original enrol not found.");
                continue;
            }
            if (empty($instance)) {
                logging::instance()->log_line("Skipping enrols for {$crosslist->sdid} {$member->sdid}, child instance not found.");
                continue;
            }

            // Need to update mdl_user_enrolments->enrolid and mdl_role_assignments->itemid (with component enrol_lmb).
            $sql = "UPDATE {user_enrolments}
                       SET enrolid = :newid
                     WHERE enrolid = :oldid
                       AND userid IN (SELECT u.id FROM {enrol_lmb_old_enrolments} enrol
                                        JOIN {user} u ON u.idnumber = enrol.personsourcedid
                                       WHERE coursesourcedid = :groupsdid AND status = 1)";

            $params = ['newid' => $instance->id, 'oldid' => $existing->id, 'groupsdid' => $member->sdid];

            $DB->execute($sql, $params);

            $sql = "UPDATE {role_assignments}
                       SET itemid = :newid
                     WHERE itemid = :oldid
                       AND component = :component
                       AND userid IN (SELECT u.id FROM {enrol_lmb_old_enrolments} enrol
                                        JOIN {user} u ON u.idnumber = enrol.personsourcedid
                                       WHERE coursesourcedid = :groupsdid AND status = 1)";

            $params = ['newid' => $instance->id,
                       'oldid' => $existing->id,
                       'groupsdid' => $member->sdid,
                       'component' => 'enrol_lmb'];

            $DB->execute($sql, $params);

            moodle\course_enrolments::reprocess_enrolments_for_section_sdid($member->sdid);
        }
    }

    if (empty($existing)) {
        return;
    }

    $count = $DB->count_records('user_enrolments', ['enrolid' => $existing->id]);

    if ($count) {
        logging::instance()->log_line("Records still left in user_enrolments for {$crosslist->sdid}, not deleting.");
        return;
    }

    $enrol->delete_instance($existing);

}

function enrol_lmb_upgrade_migrate_old_terms($progress = false) {
    global $DB;

    if ($progress) {
        $count = $DB->count_records('enrol_lmb_old_terms');
        if ($count) {
            $progress->start_progress('', $count);
        } else {
            $progress->start_progress('');
        }
    }

    $records = $DB->get_recordset('enrol_lmb_old_terms');

    $count = 0;
    foreach ($records as $record) {
        $count++;
        if ($progress) {
            $progress->progress($count);
        }

        $sdid = $record->sourcedid;
        if ($DB->record_exists(data\term::TABLE, ['sdid' => $sdid])) {
            logging::instance()->log_line("Skipping {$sdid} because it already exists.");
            continue;
        }

        $term = new data\term();
        $term->sdidsource = $record->sourcedidsource;
        $term->sdid = $sdid;
        $term->description = $record->title;
        $term->shortdescription = $record->title;
        $term->begindate = $record->starttime;
        $term->enddate = $record->endtime;
        $term->sortorder = $sdid;
        $term->beginrestrict = 0;
        $term->endrestrict = 0;
        $term->migrated = 1;

        $term->save_to_db();
    }

    if ($progress) {
        $progress->end_progress();
    }

    $records->close();
}
