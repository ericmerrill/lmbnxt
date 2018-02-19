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

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\settings;
use enrol_lmb\logging;

/**
 * Upgrade file.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_enrol_lmb_upgrade($oldversion=0) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/enrol/lmb/upgradelib.php');

    $dbman = $DB->get_manager();

    // We need to rename all the old tables.
    if ($oldversion < 2018013000) {
        $update = false;
        // Define table enrol_lmb_courses to be renamed to enrol_lmb_old_courses.
        $table = new xmldb_table('enrol_lmb_courses');

        // Launch rename table for enrol_lmb_courses.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_old_courses');
            $update = true;
        }

        // Define table enrol_lmb_people to be renamed to enrol_lmb_old_people.
        $table = new xmldb_table('enrol_lmb_people');

        // Launch rename table for enrol_lmb_people.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_old_people');
            $update = true;
        }

        // Define table enrol_lmb_enrolments to be renamed to enrol_lmb_old_enrolments.
        $table = new xmldb_table('enrol_lmb_enrolments');

        // Launch rename table for enrol_lmb_enrolments.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_old_enrolments');
            $update = true;
        }

        // Define table enrol_lmb_raw_xml to be renamed to enrol_lmb_old_raw_xml.
        $table = new xmldb_table('enrol_lmb_raw_xml');

        // Launch rename table for enrol_lmb_raw_xml.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_old_raw_xml');
            $update = true;
        }

        // Define table enrol_lmb_crosslists to be renamed to enrol_lmb_old_crosslists.
        $table = new xmldb_table('enrol_lmb_crosslists');

        // Launch rename table for enrol_lmb_crosslists.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_old_crosslists');
            $update = true;
        }

        // Define table enrol_lmb_terms to be renamed to enrol_lmb_old_terms.
        $table = new xmldb_table('enrol_lmb_terms');

        // Launch rename table for enrol_lmb_terms.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_old_terms');
            $update = true;
        }

        // Define table enrol_lmb_categories to be renamed to enrol_lmb_old_categories.
        $table = new xmldb_table('enrol_lmb_categories');

        // Launch rename table for enrol_lmb_categories.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_old_categories');
            $update = true;
        }

        if ($update) {
            // Save a flag so we know we need to upgrade.
            set_config('needslegacyupgrade', 1, 'enrol_lmb');
        }

        // Update some various settings.
        $config = get_config('enrol_lmb');
        if (isset($config->logtolocation)) {
            set_config('logpath', $config->logtolocation, 'enrol_lmb');
            unset_config('logtolocation', 'enrol_lmb');
        }

        if (isset($config->bannerxmllocation)) {
            set_config('xmlpath', $config->bannerxmllocation, 'enrol_lmb');
            unset_config('bannerxmllocation', 'enrol_lmb');
        }

        if (isset($config->bannerxmlfolder)) {
            set_config('extractpath', $config->bannerxmlfolder, 'enrol_lmb');
            unset_config('bannerxmlfolder', 'enrol_lmb');
        }

        if (isset($config->ignoreemailcase)) {
            set_config('lowercaseemails', $config->ignoreemailcase, 'enrol_lmb');
            unset_config('ignoreemailcase', 'enrol_lmb');
        }

        if (isset($config->ignoreusernamecase)) {
            unset_config('ignoreusernamecase', 'enrol_lmb');
        }

        if (isset($config->forcename)) {
            set_config('forcefirstname', $config->forcename, 'enrol_lmb');
            set_config('forcelastname', $config->forcename, 'enrol_lmb');
            unset_config('forcename', 'enrol_lmb');
        }

        if (isset($config->usernamesource)) {
            $new = enrol_lmb_upgrade_migrate_user_source_value($config->usernamesource);
            if ($new !== false) {
                set_config('usernamesource', $new, 'enrol_lmb');
            } else {
                unset_config('usernamesource', 'enrol_lmb');
            }
        }

        if (isset($config->customfield1source)) {
            $new = enrol_lmb_upgrade_migrate_user_source_value($config->customfield1source);
            if ($new !== false) {
                set_config('customfield1source', $new, 'enrol_lmb');
            } else {
                unset_config('customfield1source', 'enrol_lmb');
            }
        }

        if (isset($config->cattype)) {
            $new = enrol_lmb_upgrade_migrate_cat_type_value($config->cattype);
            if ($new !== false) {
                set_config('cattype', $new, 'enrol_lmb');
            } else {
                unset_config('cattype', 'enrol_lmb');
            }
        }

        if (isset($config->xlstype)) {
            $new = enrol_lmb_upgrade_migrate_crosslist_type_value($config->xlstype);
            if ($new !== false) {
                set_config('xlstype', $new, 'enrol_lmb');
            } else {
                unset_config('xlstype', 'enrol_lmb');
            }
        }

        if (isset($config->logerrors)) {
            if ($config->logerrors) {
                set_config('logginglevel', logging::ERROR_NOTICE, 'enrol_lmb');
            } else {
                set_config('logginglevel', logging::ERROR_NONE, 'enrol_lmb');
            }
            unset_config('logerrors', 'enrol_lmb');
        }

        if (isset($config->coursehidden)) {
            $new = enrol_lmb_upgrade_migrate_course_hidden_value($config->coursehidden);
            if ($new !== false) {
                set_config('coursehidden', $new, 'enrol_lmb');
            } else {
                unset_config('coursehidden', 'enrol_lmb');
            }
        }

        upgrade_plugin_savepoint(true, 2018013000, 'enrol', 'lmb');
    }

    if ($oldversion < 2018013100) {
        // Define table enrol_lmb to be created.
        $table = new xmldb_table('enrol_lmb');

        // Adding fields to table enrol_lmb.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);

        // Adding keys to table enrol_lmb.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for enrol_lmb.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_lmb_person to be created.
        $table = new xmldb_table('enrol_lmb_person');

        // Adding fields to table enrol_lmb_person.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sctid', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('logonid', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('emailid', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('nickname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('familyname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('givenname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('rolestudent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('rolestaff', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('rolefaculty', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('rolealumni', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('roleprospectivestudent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('primaryrole', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_person.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table enrol_lmb_person.
        $table->add_index('sdid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch create table for enrol_lmb_person.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_lmb_term to be created.
        $table = new xmldb_table('enrol_lmb_term');

        // Adding fields to table enrol_lmb_term.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('referenceagent', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('messagereference', XMLDB_TYPE_CHAR, '128', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('begindate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_term.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table enrol_lmb_term.
        $table->add_index('sdid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch create table for enrol_lmb_term.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_lmb_section to be created.
        $table = new xmldb_table('enrol_lmb_section');

        // Adding fields to table enrol_lmb_section.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('begindate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('deptname', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('termsdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('termsdid', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('coursesdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('coursesdid', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_section.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table enrol_lmb_section.
        $table->add_index('sdid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch create table for enrol_lmb_section.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_lmb_course to be created.
        $table = new xmldb_table('enrol_lmb_course');

        // Adding fields to table enrol_lmb_course.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('rubric', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('deptsdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('deptsdid', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('collegesdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('collegesdid', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_course.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table enrol_lmb_course.
        $table->add_index('sdid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch create table for enrol_lmb_course.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_lmb_member_person to be created.
        $table = new xmldb_table('enrol_lmb_member_person');

        // Adding fields to table enrol_lmb_member_person.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('membersdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('membersdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('referenceagent', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('messagereference', XMLDB_TYPE_CHAR, '128', null, null, null, null);
        $table->add_field('groupsdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('groupsdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roletype', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('begindate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_member_person.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table enrol_lmb_member_person.
        $table->add_index('messagereference-referenceagent', XMLDB_INDEX_NOTUNIQUE, array('messagereference', 'referenceagent'));
        $table->add_index('membersdid-groupsdid-roletype', XMLDB_INDEX_UNIQUE, array('membersdid', 'groupsdid', 'roletype'));

        // Conditionally launch create table for enrol_lmb_member_person.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_lmb_crosslist to be created.
        $table = new xmldb_table('enrol_lmb_crosslist');

        // Adding fields to table enrol_lmb_crosslist.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_crosslist.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table enrol_lmb_crosslist.
        $table->add_index('sdid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch create table for enrol_lmb_crosslist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table enrol_lmb_crosslist_member to be created.
        $table = new xmldb_table('enrol_lmb_crosslist_member');

        // Adding fields to table enrol_lmb_crosslist_member.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('crosslistid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_crosslist_member.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('crosslistid', XMLDB_KEY_FOREIGN, array('crosslistid'), 'enrol_lmb_crosslist', array('id'));

        // Adding indexes to table enrol_lmb_crosslist_member.
        $table->add_index('sdid-crosslistid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'crosslistid', 'sdidsource'));

        // Conditionally launch create table for enrol_lmb_crosslist_member.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2018013100, 'enrol', 'lmb');
    }

    if ($oldversion < 2018021400) {
        // Add messagetime columns

        // Define field messagetime to be added to enrol_lmb_person.
        $table = new xmldb_table('enrol_lmb_person');
        $field = new xmldb_field('messagetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field messagetime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagetime to be added to enrol_lmb_term.
        $table = new xmldb_table('enrol_lmb_term');
        $field = new xmldb_field('messagetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field messagetime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagetime to be added to enrol_lmb_section.
        $table = new xmldb_table('enrol_lmb_section');
        $field = new xmldb_field('messagetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field messagetime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagetime to be added to enrol_lmb_course.
        $table = new xmldb_table('enrol_lmb_course');
        $field = new xmldb_field('messagetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field messagetime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagetime to be added to enrol_lmb_member_person.
        $table = new xmldb_table('enrol_lmb_member_person');
        $field = new xmldb_field('messagetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field messagetime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagetime to be added to enrol_lmb_crosslist.
        $table = new xmldb_table('enrol_lmb_crosslist');
        $field = new xmldb_field('messagetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field messagetime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagetime to be added to enrol_lmb_crosslist_member.
        $table = new xmldb_table('enrol_lmb_crosslist_member');
        $field = new xmldb_field('messagetime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field messagetime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2018021400, 'enrol', 'lmb');
    }

    if ($oldversion < 2018021900) {
        // Define table enrol_lmb_person to be renamed to enrol_lmb_people.
        $table = new xmldb_table('enrol_lmb_person');

        // Launch rename table for enrol_lmb_person.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_people');
        }

        // Define table enrol_lmb_term to be renamed to enrol_lmb_terms.
        $table = new xmldb_table('enrol_lmb_term');

        // Launch rename table for enrol_lmb_term.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_terms');
        }

        // Define table enrol_lmb_section to be renamed to enrol_lmb_course_sections.
        $table = new xmldb_table('enrol_lmb_section');

        // Launch rename table for enrol_lmb_section.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_course_sections');
        }

        // Define table enrol_lmb_course to be renamed to enrol_lmb_courses.
        $table = new xmldb_table('enrol_lmb_course');

        // Launch rename table for enrol_lmb_course.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_courses');
        }

        // Define table enrol_lmb_member_person to be renamed to enrol_lmb_person_members.
        $table = new xmldb_table('enrol_lmb_member_person');

        // Launch rename table for enrol_lmb_member_person.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_person_members');
        }

        // Define table enrol_lmb_crosslist to be renamed to enrol_lmb_crosslists.
        $table = new xmldb_table('enrol_lmb_crosslist');

        // Launch rename table for enrol_lmb_crosslist.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_crosslists');
        }

        // Define table enrol_lmb_crosslist_member to be renamed to enrol_lmb_crosslist_members.
        $table = new xmldb_table('enrol_lmb_crosslist_member');

        // Launch rename table for enrol_lmb_crosslist_member.
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'enrol_lmb_crosslist_members');
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2018021900, 'enrol', 'lmb');
    }
}
