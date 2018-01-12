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

$string['exception_bad_course'] = 'Course object has no id or source';
$string['exception_bad_member_group'] = 'Membership group or destination has no source or id';
$string['exception_bad_member_person'] = 'Membership person or group has no source or id';
$string['exception_bad_person'] = 'Person object has no id or source';
$string['exception_bad_term'] = 'Term object has no id or source';
$string['exception_bad_section'] = 'Section object has no id or source';
$string['exception_insert_failure'] = 'Failure inserting in database';
$string['exception_grouptype_not_found'] = 'Group type not found';
$string['exception_lis_namespace'] = 'LIS message namespace incorrect';
$string['exception_member_roletype_unknown'] = 'Membership role type value "{$a}" unknown.';
$string['exception_member_status_unknown'] = 'Membership status value "{$a}" unknown.';
$string['exception_membershiptype_no_id'] = 'Membership group has no source or id';
$string['exception_membershiptype_no_member_type'] = 'Member has no idtype value';
$string['exception_membershiptype_no_members'] = 'Membership has no members';
$string['exception_membershiptype_not_found'] = 'Membership type could not be found';
$string['exception_update_failure'] = 'Failure updating in database';
$string['exception_xml_boolean'] = 'Could not convert data "{$a}" into boolean.';

// TODO.
$string['parseperson'] = 'TODO';
$string['parsepersonxml'] = 'TODO';
$string['parsepersonxml_help'] = 'TODO';
$string['createnewusers'] = 'TODO';
$string['createnewusers_help'] = 'TODO';
$string['createusersemaildomain'] = 'TODO';
$string['createusersemaildomain_help'] = 'TODO';
$string['ignoredomaincase'] = 'TODO';
$string['ignoredomaincase_help'] = 'TODO';
$string['donterroremail'] = 'TODO';
$string['donterroremail_help'] = 'TODO';


$string['parsecourse'] = 'TODO';
$string['parsecoursexml'] = 'TODO';
$string['parsecoursexml_help'] = 'TODO';
$string['coursetitle'] = 'Course full name';
$string['coursetitle_help'] = "This contains the template for creating the full course name.
<p>You can dictate how you would like the course full and short names formatted using the following flags. Any occurrence of these flags in the setting will
be replaced with the appropriate information about the course. Any text that is not apart of a flag will be left as is.</p>
<p><ul>
<li>[SOURCEDID] - Same as [CRN].[TERM]<br />
<li>[CRN] - The course/section number<br />
<li>[TERM] - The term code<br />
<li>[TERMNAME] - The full name of the term<br />
<li>[LONG] - The same as [DEPT]-[NUM]-[SECTION]<br />
<li>[FULL] - The full title of the course<br />
<li>[RUBRIC] - The same as [DEPT]-[NUM]<br />
<li>[DEPT] - The short department code<br />
<li>[NUM] - The department code for the course<br />
<li>[SECTION] - The section number of the course<br />
</ul></p>
<p>Example: The setting '[RUBRIC]-[CRN]-[FULL]' would look like 'ENG-341-12345-English History' for a course with that information.</p>";
$string['forcetitle'] = 'Force course name on update';
$string['forcetitle_help'] = "If this option is selected then whenever an update occurs to a course through LMB/Banner the name will be set as described in the 'Course full name' settings, even if the name has been manually changed. If is option is not set, then the name will only be set during initial course creation.";
$string['courseshorttitle'] = 'Course short name';
$string['courseshorttitle_help'] = 'This contains the template for creating the short course name. See above for available tags.';
$string['forceshorttitle'] = 'Force course short name on update';
$string['forceshorttitle_help'] = "If this option is selected then whenever an update occurs to a course through LMB/Banner the short name will be set as described in the 'Course short name' settings, even if the short name has been manually changed. If is option is not set, then the short name will only be set during initial course creation.";
$string['coursehidden'] = 'Create new courses:';
$string['coursehidden_help'] = "Specify the visibility of new courses. Options:
<ul>
<li>Visible: Courses will never be created hidden (ie always created visible)
<li>Based on date settings: Course will be created with it's visibility set based on the 'Unhide this many days before course start' setting. If the course start date has already past, or starts within the number of days specified, it will be visible. If it occurs further in the future, it will be created hidden.
<li>Hidden: Courses will always be created hidden
</ul>";
$string['coursehiddenhidden'] = 'Hidden';
$string['coursehiddencron'] = 'Based on date settings';
$string['coursehiddenvisible'] = 'Visible';
$string['cathidden'] = 'TODO';
$string['cathidden_help'] = 'TODO';
$string['cronunhidecourses'] = 'Automatically unhide courses';
$string['cronunhidecourses_help'] = 'TODO';
$string['cronunhidedays'] = 'Unhide this many days before course start';
$string['cronunhidedays_help'] = 'TODO';
$string['forcecat'] = 'TODO';
$string['forcecat_help'] = 'TODO';
$string['usemoodlecoursesettings'] = 'TODO';
$string['usemoodlecoursesettings_help'] = 'TODO';
$string['computesections'] = 'Compute number of sections';
$string['computesections_help'] = 'Compute the number of sections/topics to display, based on the number of weeks in a course.';
$string['forcecomputesections'] = 'Force computed sections on update';
$string['forcecomputesections_help'] = 'Force section count on update, but only more than existing, never removes sections.';
$string['categorytype'] = 'TODO';
$string['categorytype_help'] = 'TODO';
$string['catselect'] = 'TODO';
$string['catselect_help'] = 'TODO';
$string['termcat'] = 'TODO';
$string['deptcat'] = 'TODO';
$string['termdeptcat'] = 'TODO';
$string['deptcodecat'] = 'TODO';
$string['termdeptcodecat'] = 'TODO';
$string['selectedcat'] = 'TODOz-1';
