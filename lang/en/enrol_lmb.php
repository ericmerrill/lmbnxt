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
// Misc.
$string['ignore'] = 'Ignore';
$string['enrolcustomname'] = 'LMB NXT from {$a}';
$string['page_datamigration'] = 'Migrate old data';
$string['tools'] = 'Tools';

// Data Migration Tool.
$string['migratingenrols'] = 'Migrating enrolment data';
$string['migratingcrosslists'] = 'Migrating crosslists data';
$string['migratingterms'] = 'Migrating terms data';
$string['migrateenrols'] = 'Migrate enrolment data';
$string['migratecrosslists'] = 'Migrate crosslists data';
$string['migrateterms'] = 'Migrate terms data';

$string['deletingenrols'] = 'Deleting table enrol_lmb_old_enrolments';
$string['deletingcrosslists'] = 'Deleting table enrol_lmb_old_crosslists';
$string['deletingterms'] = 'Deleting table enrol_lmb_old_terms';
$string['deletingcourses'] = 'Deleting table enrol_lmb_old_courses';
$string['deletingcats'] = 'Deleting table enrol_lmb_old_categories';
$string['deletingpeople'] = 'Deleting table enrol_lmb_old_people';
$string['deletingxml'] = 'Deleting table enrol_lmb_old_raw_xml';
$string['deleteenrols'] = 'Delete table enrol_lmb_old_enrolments';
$string['deletecrosslists'] = 'Delete table enrol_lmb_old_crosslists';
$string['deleteterms'] = 'Delete table enrol_lmb_old_terms';
$string['deletecourses'] = 'Delete table enrol_lmb_old_courses';
$string['deletecats'] = 'Delete table enrol_lmb_old_categories';
$string['deletepeople'] = 'Delete table enrol_lmb_old_people';
$string['deletexml'] = 'Delete table enrol_lmb_old_raw_xml';

// Tasks.
$string['task_unhidecourses'] = 'Auto unhide courses';

// Settings.
$string['logginglevel'] = 'Error logging';
$string['logginglevel_help'] = 'Log messages at this level or higher will be logged. TODO.';
$string['error_all'] = 'All';
$string['error_notice'] = 'Notices';
$string['error_warn'] = 'Warnings';
$string['error_major'] = 'Major Errors';
$string['logpath'] = 'Log file location';
$string['logpath_help'] = 'This is the location you would like the log file to be saved to. This should be an absolute path on the server. The file specified should already exist, and needs to be writable by the webserver process.';
$string['logwsmessages'] = 'Log webservice messages';
$string['logwsmessages_help'] = 'TODO';
$string['extractpath'] = 'Import folder location';
$string['extractpath_help'] = 'The path (on the Moodle server) to the directory where a set of XML files will be located.';
$string['xmlpath'] = 'Import file location';
$string['xmlpath_help'] = 'The path (on the Moodle server) where the XML file that you would like to import resides.';

$string['exception_bad_course'] = 'Course object has no id or source';
$string['exception_bad_crosslist_id'] = 'Crosslist missing id';
$string['exception_bad_crosslist_member_id'] = 'Crosslist member missing id';
$string['exception_bad_person_member'] = 'Membership person or group has no id';
$string['exception_bad_person'] = 'Person object has no id';
$string['exception_bad_term'] = 'Term object has no id';
$string['exception_bad_section'] = 'Section object has no id';
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

// Person Settings.
$string['parseperson'] = 'Person Processing';
$string['parsepersonxml'] = 'TODO';
$string['parsepersonxml_help'] = 'TODO';
$string['createnewusers'] = 'Create new user accounts';
$string['createnewusers_help'] = 'This setting will allow the LMB module to create new Moodle users as directed by Banner/LMB.';
$string['createusersemaildomain'] = 'Restrict email domains';
$string['createusersemaildomain_help'] = 'If this setting has a value, only users who have an email address in the given domain will have an account generated for them. Regular expressions values may be used. Each line is internally wrapped by <code>|^</code> and <code>$|</code>';
$string['ignoredomaincase'] = 'Ignore email domain capitalization';
$string['ignoredomaincase_help'] = 'Set the domain comparison for the previous setting to case insensitive.';
$string['donterroremail'] = "Don't error for invalid email";
$string['donterroremail_help'] = 'If selected, a user skipped because of an invalid email address will not produce a warning.';
$string['lowercaseemails'] = 'Force lowercase emails';
$string['lowercaseemails_help'] = 'Converts received emails to lowercase for Moodle users.';
$string['usernamesource'] = 'Username source';
$string['usernamesource_help'] = "This determines what will be the username of created users
<ul>
<li>Full email address: The entire email address is used as the username.
<li>Email name (before @): Use the portion of the email address before the @.
<li>useridtype - Login ID: Use the value supplied in the userid tag marked 'Login ID'.
<li>useridtype - SCTID: Use the value supplied in the userid tag marked 'SCTID'.
<li>useridtype - Email ID: Use the value supplied in the userid tag marked 'Email ID'.
<li>useridtype - Other: Use the value supplied in the userid tag marked as indicated in the text box.
</ul>";
$string['fullemail'] = 'Full email address';
$string['emailname'] = 'Email before @';
$string['useridtypelogin'] = 'useridtype - Login ID';
$string['useridtypesctid'] = 'useridtype - SCTID';
$string['useridtypeemail'] = 'useridtype - Email ID';
$string['useridtypeother'] = 'useridtype - Other';
$string['otheruserid'] = 'Other User ID Source';
$string['otheruserid_help'] = '';
$string['sourcedidfallback'] = 'Username-SourcedID fallback';
$string['sourcedidfallback_help'] = 'Set the userid to the persons sourcedid if a username is not found. In general users will not know this number, so will not be able to login, but it will create the account as a placeholder until more complete data is received.';
$string['consolidateusernames'] = 'Consolidate existing usernames';
$string['consolidateusernames_help'] = 'TODO';
$string['customfield1mapping'] = 'Custom profile field';
$string['customfield1mapping_help'] = 'Shortname of the custom field to map to.';
$string['customfield1source'] = 'Custom profile field source';
$string['customfield1source_help'] = 'Source for the custom user profile field. Same options as Username source.';
$string['authmethod'] = 'Authentication method';
$string['authmethod_help'] = 'TODO';
$string['nickdisabled'] = 'None';
$string['firstname'] = 'First name';
$string['altname'] = 'Alternative name';
$string['nickname'] = 'Nickname use';
$string['nickname_help'] = 'TODO';
$string['forcealtname'] = 'Force alternate nickname';
$string['forcealtname_help'] = 'TODO';
$string['forcefirstname'] = 'Force first name';
$string['forcefirstname_help'] = 'TODO';
$string['forcelastname'] = 'Force last name';
$string['forcelastname_help'] = 'TODO';
$string['forceemail'] = 'Force email address';
$string['forceemail_help'] = 'TODO';

// Course Settings.
$string['parsecourse'] = 'Course Processing';
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
$string['cathidden'] = 'Create new categories as hidden';
$string['cathidden_help'] = '';
$string['cronunhidecourses'] = 'Automatically unhide courses';
$string['cronunhidecourses_help'] = 'TODO';
$string['cronunhidedays'] = 'Unhide this many days before course start';
$string['cronunhidedays_help'] = 'TODO';
$string['forcecat'] = 'Force category on update';
$string['forcecat_help'] = 'This option will cause the category to changed to the above setting whenever a LMB/Banner update occurs, even if it has been manually changed.';
$string['usemoodlecoursesettings'] = 'TODO';
$string['usemoodlecoursesettings_help'] = 'TODO';
$string['computesections'] = 'Compute number of sections';
$string['computesections_help'] = 'Compute the number of sections/topics to display, based on the number of weeks in a course.';
$string['forcecomputesections'] = 'Force computed sections on update';
$string['forcecomputesections_help'] = 'Force section count on update, but only more than existing, never removes sections.';
$string['categorytype'] = 'Course Categories';
$string['categorytype_help'] = 'This allows you select what categories you would like courses to be created in. Options:
<ul>
<li>Terms: This setting will cause courses to be placed in categories with the name of their term/semester.
<li>Departments: This setting will cause courses to be placed in categories with the name of their host department.
<li>Department Codes: Uses the department short code, instead of full name.
<li>Terms then Departments: This setting will cause courses to be placed in categories with the name of their host department, which is contained in a parent term named for the term/semester.
<li>Terms then Department Codes: Same as Terms then Departments, but uses the department short code instead of its full name.
<li>Selected: With this setting, select the existing category you would like courses to be placed in from the second drop down menu.
</ul>';
$string['catselect'] = 'Selected Category';
$string['catselect_help'] = '';
$string['termcat'] = 'Terms';
$string['deptcat'] = 'Departments - TODO';
$string['termdeptcat'] = 'Terms then Departments - TODO';
$string['deptcodecat'] = 'Department Codes - TODO';
$string['termdeptcodecat'] = 'Terms then Department Codes - TODO';
$string['selectedcat'] = 'Selected - TODO';

// Enrolment Settings.
$string['parseenrol'] = 'Enrolment Processing';
$string['parseenrolxml'] = 'TODO';
$string['parseenrolxml_help'] = 'TODO';
$string['imsrolename01'] = 'Learner (01)';
$string['imsrolename02'] = 'Instructor (02)';
$string['imsrolename03'] = 'Extra 1 (03)';
$string['imsrolename04'] = 'Extra 2 (04)';
$string['imsrolename05'] = 'Extra 3 (05)';
$string['restrictenroldates'] = 'TODO';
$string['restrictenroldates_help'] = 'TODO';

// XLS Settings.
$string['parsexls'] = 'Crosslist Processing';
$string['parsexlsxml'] = 'TODO';
$string['parsexlsxml_help'] = 'TODO';
$string['xlstitle'] = 'TODO';
$string['xlstitle_help'] = 'TODO';
$string['xlstitlerepeat'] = 'TODO';
$string['xlstitlerepeat_help'] = 'TODO';
$string['xlstitledivider'] = 'TODO';
$string['xlstitledivider_help'] = 'TODO';
$string['xlsshorttitle'] = 'TODO';
$string['xlsshorttitle_help'] = 'TODO';
$string['xlsshorttitlerepeat'] = 'TODO';
$string['xlsshorttitlerepeat_help'] = 'TODO';
$string['xlsshorttitledivider'] = 'TODO';
$string['xlsshorttitledivider_help'] = 'TODO';
$string['xlstype'] = 'Crosslisted course type';
$string['xlstype_help'] = 'This determines how crosslisted courses will be handled in Moodle. Options:
<ul>
<li>Merged course: This setting will cause the separate courses of the crosslist to be left empty, with no enrollments. All members will be enrolled directly into the crosslisted course.
<li>Meta course: This setting will cause members to be enrolled in the individual courses, while the crosslsted course is formed by making a meta-course containing all the individual courses.
</ul>';
$string['xlsmergegroups'] = 'TODO';
$string['xlsmergegroups_help'] = 'TODO';
$string['xlsmergecourse'] = 'TODOmerge';
$string['xlsmetacourse'] = 'TODOmeta';
