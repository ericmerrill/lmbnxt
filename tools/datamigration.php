<?php
// This file is part of the Banner/LMB plugin for Moodle - http://moodle.org/
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
 * Data migration tool.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
require_once(dirname(dirname(__FILE__)).'/upgradelib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

admin_externalpage_setup('enrollmbtooldatamigration');

use core\progress\display;
use enrol_lmb\logging;

class enrol_lmb_data_migration_form extends moodleform {
    /**
     * Define this form - is called from parent constructor.
     */
    public function definition() {
        global $DB;

        $dbm = $DB->get_manager();

        $mform = $this->_form;
        $instance = $this->_customdata;


        $mform->addElement('checkbox', 'upgradeenrolments', '', get_string('migrateenrols', 'enrol_lmb'));

        $mform->addElement('checkbox', 'upgradecrosslists', '', get_string('migratecrosslists', 'enrol_lmb'));

        $mform->addElement('checkbox', 'upgradeterms', '', get_string('migrateterms', 'enrol_lmb'));

        //$mform->addElement('checkbox', 'upgradecourses', '', "Migrate old courses data");

        $mform->addElement('checkbox', 'deleteenrolments', '', get_string('deleteenrols', 'enrol_lmb'));

        $mform->addElement('checkbox', 'deletecrosslists', '', get_string('deletecrosslists', 'enrol_lmb'));

        $mform->addElement('checkbox', 'deleteterms', '', get_string('deleteterms', 'enrol_lmb'));

        $mform->addElement('checkbox', 'deletecourses', '', get_string('deletecourses', 'enrol_lmb'));

        $mform->addElement('checkbox', 'deletecats', '', get_string('deletecats', 'enrol_lmb'));

        $mform->addElement('checkbox', 'deletepeople', '', get_string('deletepeople', 'enrol_lmb'));

        $mform->addElement('checkbox', 'deletexml', '', get_string('deletexml', 'enrol_lmb'));

        //$mform->addElement('checkbox', 'deletecourses', '', "Delete old courses table");

        if ($dbm->table_exists('enrol_lmb_old_courses')) {

        }

        $mform->addElement('submit', 'process', 'Process');
    }

}


echo $OUTPUT->header();

@set_time_limit(0);

echo $OUTPUT->box_start();


$migrationform = new enrol_lmb_data_migration_form();
$data = $migrationform->get_data();


if ($data && isset($data->process)) {
    require_sesskey();

    $dbman = $DB->get_manager();

    logging::instance()->set_silence_std_out();

    // Migrate old enrolments.
    if (!empty($data->upgradeenrolments)) {
        echo html_writer::tag('h3', get_string('migratingenrols', 'enrol_lmb'));
        $pbar = new display();
        enrol_lmb_upgrade_migrate_old_enrols($pbar);
    }

    // Migrate old crosslists.
    if (!empty($data->upgradecrosslists)) {
        echo html_writer::tag('h3', get_string('migratingcrosslists', 'enrol_lmb'));
        $pbar = new display();
        enrol_lmb_upgrade_migrate_old_crosslists($pbar);
    }

    // Migrate old terms.
    if (!empty($data->upgradeterms)) {
        echo html_writer::tag('h3', get_string('migratingterms', 'enrol_lmb'));
        $pbar = new display();
        enrol_lmb_upgrade_migrate_old_terms($pbar);
    }

    // Delete the old enrolments table.
    if (!empty($data->deleteenrolments) && $dbman->table_exists('enrol_lmb_old_enrolments')) {
        echo html_writer::tag('h3', get_string('deletingenrols', 'enrol_lmb'));
        $table = new xmldb_table('enrol_lmb_old_enrolments');

        try {
            //$dbman->drop_table($table);
            $notification = new \core\output\notification(get_string('success'), \core\output\notification::NOTIFY_SUCCESS);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        } catch (Exception $e) {
            $notification = new \core\output\notification(get_string('error'), \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        }
    }

    // Delete the old crosslists table.
    if (!empty($data->deleteenrolments) && $dbman->table_exists('enrol_lmb_old_crosslists')) {
        echo html_writer::tag('h3', get_string('deletingcrosslists', 'enrol_lmb'));
        $table = new xmldb_table('enrol_lmb_old_crosslists');
        try {
            //$dbman->drop_table($table);
            $notification = new \core\output\notification(get_string('success'), \core\output\notification::NOTIFY_SUCCESS);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        } catch (Exception $e) {
            $notification = new \core\output\notification(get_string('error'), \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        }
    }

    // Delete the old terms table.
    if (!empty($data->deleteenrolments) && $dbman->table_exists('enrol_lmb_old_terms')) {
        echo html_writer::tag('h3', get_string('deletingterms', 'enrol_lmb'));
        $table = new xmldb_table('enrol_lmb_old_terms');
        try {
            //$dbman->drop_table($table);
            $notification = new \core\output\notification(get_string('success'), \core\output\notification::NOTIFY_SUCCESS);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        } catch (Exception $e) {
            $notification = new \core\output\notification(get_string('error'), \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        }
    }

    // Delete the old courses table.
    if (!empty($data->deleteenrolments) && $dbman->table_exists('enrol_lmb_old_courses')) {
        echo html_writer::tag('h3', get_string('deletingcourses', 'enrol_lmb'));
        $table = new xmldb_table('enrol_lmb_old_courses');
        try {
            //$dbman->drop_table($table);
            $notification = new \core\output\notification(get_string('success'), \core\output\notification::NOTIFY_SUCCESS);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        } catch (Exception $e) {
            $notification = new \core\output\notification(get_string('error'), \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        }
    }

    // Delete the old categories table.
    if (!empty($data->deleteenrolments) && $dbman->table_exists('enrol_lmb_old_categories')) {
        echo html_writer::tag('h3', get_string('deletingcats', 'enrol_lmb'));
        $table = new xmldb_table('enrol_lmb_old_categories');
        try {
            //$dbman->drop_table($table);
            $notification = new \core\output\notification(get_string('success'), \core\output\notification::NOTIFY_SUCCESS);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        } catch (Exception $e) {
            $notification = new \core\output\notification(get_string('error'), \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        }
    }

    // Delete the old categories table.
    if (!empty($data->deleteenrolments) && $dbman->table_exists('enrol_lmb_old_people')) {
        echo html_writer::tag('h3', get_string('deletingpeople', 'enrol_lmb'));
        $table = new xmldb_table('enrol_lmb_old_people');
        try {
            //$dbman->drop_table($table);
            $notification = new \core\output\notification(get_string('success'), \core\output\notification::NOTIFY_SUCCESS);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        } catch (Exception $e) {
            $notification = new \core\output\notification(get_string('error'), \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        }
    }

    // Delete the old raw xml table.
    if (!empty($data->deleteenrolments) && $dbman->table_exists('enrol_lmb_old_raw_xml')) {
        echo html_writer::tag('h3', get_string('deletingxml', 'enrol_lmb'));
        $table = new xmldb_table('enrol_lmb_old_raw_xml');
        try {
            //$dbman->drop_table($table);
            $notification = new \core\output\notification(get_string('success'), \core\output\notification::NOTIFY_SUCCESS);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        } catch (Exception $e) {
            $notification = new \core\output\notification(get_string('error'), \core\output\notification::NOTIFY_ERROR);
            $notification->set_show_closebutton(false);
            echo $OUTPUT->render($notification);
        }
    }

} else {
    $migrationform->display();
}


echo $OUTPUT->box_end();


echo $OUTPUT->footer();

exit;
