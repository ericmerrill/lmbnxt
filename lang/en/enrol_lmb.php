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
 * The language file.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'LMB NXT';

// Settings.
$string['logginglevel'] = 'Error logging';
$string['logginglevel_help'] = 'Log messages at this level or higher will be logged. TODO.';
$string['error_all'] = 'All';
$string['error_notice'] = 'Notices';
$string['error_warn'] = 'Warnings';
$string['error_major'] = 'Major Errors';
$string['logpath'] = 'Log file location';
$string['logpath_help'] = 'This is the location you would like the log file to be saved to. This should be an absolute path on the server. The file specified should already exist, and needs to be writable by the webserver process.';
$string['extractpath'] = 'Import folder location';
$string['extractpath_help'] = 'The path (on the Moodle server) to the directory where a set of XML files will be located.';
$string['xmlpath'] = 'Import file location';
$string['xmlpath_help'] = 'The path (on the Moodle server) where the XML file that you would like to import resides.';

$string['exception_bad_person'] = 'Person object has no id or source';
$string['exception_bad_term'] = 'Term object has no id or source';
$string['exception_bad_section'] = 'Section object has no id or source';
$string['exception_insert_failure'] = 'Failure inserting in database';
$string['exception_grouptype_not_found'] = 'Group type not found';
$string['exception_update_failure'] = 'Failure updating in database';
