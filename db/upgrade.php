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

/**
 * Upgrade file.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_enrol_lmb_upgrade($oldversion=0) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016022300) {

        // Define table enrol_lmb_person to be created.
        $table = new xmldb_table('enrol_lmb_person');

        // Adding fields to table enrol_lmb_person.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
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

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2016022300, 'enrol', 'lmb');
    }

    if ($oldversion < 2016022401) {

        // Define table enrol_lmb_term to be created.
        $table = new xmldb_table('enrol_lmb_term');

        // Adding fields to table enrol_lmb_term.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
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

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2016022401, 'enrol', 'lmb');
    }

    if ($oldversion < 2016052400) {

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

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2016052400, 'enrol', 'lmb');
    }

    if ($oldversion < 2016052500) {

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

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2016052500, 'enrol', 'lmb');
    }

    if ($oldversion < 2016052501) {

        // Define table enrol_lmb_member_person to be created.
        $table = new xmldb_table('enrol_lmb_member_person');

        // Adding fields to table enrol_lmb_member_person.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupsdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupsdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roletype', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('begindate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_member_person.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for enrol_lmb_member_person.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2016052501, 'enrol', 'lmb');
    }

    if ($oldversion < 2016052600) {

        // Define table enrol_lmb_member_group to be created.
        $table = new xmldb_table('enrol_lmb_member_group');

        // Adding fields to table enrol_lmb_member_group.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupsdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupsdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_member_group.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for enrol_lmb_member_group.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2016052600, 'enrol', 'lmb');
    }

    if ($oldversion < 2016052600) {

        // Define table enrol_lmb_member_group to be created.
        $table = new xmldb_table('enrol_lmb_member_group');

        // Adding fields to table enrol_lmb_member_group.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupsdidsource', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupsdid', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('additional', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table enrol_lmb_member_group.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for enrol_lmb_member_group.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2016052600, 'enrol', 'lmb');
    }

    if ($oldversion < 2017052500) {

        // Define field referenceagent to be added to enrol_lmb_member_person.
        $table = new xmldb_table('enrol_lmb_member_person');
        $field = new xmldb_field('referenceagent', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'sdid');

        // Conditionally launch add field referenceagent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagereference to be added to enrol_lmb_member_person.
        $field = new xmldb_field('messagereference', XMLDB_TYPE_CHAR, '128', null, null, null, null, 'referenceagent');

        // Conditionally launch add field messagereference.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index sdid-sdidsource (not unique) to be added to enrol_lmb_member_person.
        $index = new xmldb_index('sdid-sdidsource', XMLDB_INDEX_NOTUNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch add index sdid-sdidsource.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index messagereference-referenceagent (not unique) to be added to enrol_lmb_member_person.
        $index = new xmldb_index('messagereference-referenceagent',
                XMLDB_INDEX_NOTUNIQUE, array('messagereference', 'referenceagent'));

        // Conditionally launch add index messagereference-referenceagent.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2017052500, 'enrol', 'lmb');
    }

    if ($oldversion < 2017052501) {

        // Define index sdid-sdidsource (not unique) to be dropped form enrol_lmb_member_person.
        $table = new xmldb_table('enrol_lmb_member_person');
        $index = new xmldb_index('sdid-sdidsource', XMLDB_INDEX_NOTUNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch drop index sdid-sdidsource.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Changing nullability of field sdidsource on table enrol_lmb_member_person to null.
        $field = new xmldb_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null, 'id');

        // Launch change of nullability for field sdidsource.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field groupsdidsource on table enrol_lmb_member_person to null.
        $field = new xmldb_field('groupsdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null, 'messagereference');

        // Launch change of nullability for field groupsdidsource.
        $dbman->change_field_notnull($table, $field);

        // Define index sdid-groupsdid (not unique) to be added to enrol_lmb_member_person.
        $index = new xmldb_index('sdid-groupsdid', XMLDB_INDEX_NOTUNIQUE, array('sdid', 'groupsdid'));

        // Conditionally launch add index sdid-groupsdid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2017052501, 'enrol', 'lmb');
    }

    if ($oldversion < 2017052502) {

        // Define index sdid-sdidsource (not unique) to be dropped form enrol_lmb_term.
        $table = new xmldb_table('enrol_lmb_term');
        $index = new xmldb_index('sdid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch drop index sdid-sdidsource.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field referenceagent to be added to enrol_lmb_term.
        $field = new xmldb_field('referenceagent', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'sdid');

        // Conditionally launch add field referenceagent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field messagereference to be added to enrol_lmb_term.
        $field = new xmldb_field('messagereference', XMLDB_TYPE_CHAR, '128', null, null, null, null, 'referenceagent');

        // Conditionally launch add field messagereference.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing nullability of field sdidsource on table enrol_lmb_member_person to null.
        $field = new xmldb_field('sdidsource', XMLDB_TYPE_CHAR, '127', null, null, null, null, 'id');

        // Launch change of nullability for field sdidsource.
        $dbman->change_field_notnull($table, $field);

        // Define index sdid-sdidsource (not unique) to be added to enrol_lmb_term.
        $index = new xmldb_index('sdid-sdidsource', XMLDB_INDEX_UNIQUE, array('sdid', 'sdidsource'));

        // Conditionally launch add index sdid-sdidsource.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Lmb savepoint reached.
        upgrade_plugin_savepoint(true, 2017052502, 'enrol', 'lmb');
    }

}
