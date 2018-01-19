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
 * Admin settings file.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\settings;

$settings = new admin_category('enrolsettingscat', get_string('pluginname', 'enrol_lmb'), $settings->hidden);
$settingslmb = new admin_settingpage('enrolsettingslmb', get_string('settings'), 'moodle/site:config');

if ($ADMIN->fulltree) {
    $settingslmb->add(new admin_setting_configfile('enrol_lmb/logpath', get_string('logpath', 'enrol_lmb'),
        get_string('logpath_help', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configfile('enrol_lmb/xmlpath', get_string('xmlpath', 'enrol_lmb'),
        get_string('xmlpath_help', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configdirectory('enrol_lmb/extractpath', get_string('extractpath', 'enrol_lmb'),
        get_string('extractpath_help', 'enrol_lmb'), ''));

    $loggingoptions = array(\enrol_lmb\logging::ERROR_NONE => get_string('error_all', 'enrol_lmb'),
                            \enrol_lmb\logging::ERROR_NOTICE => get_string('error_notice', 'enrol_lmb'),
                            \enrol_lmb\logging::ERROR_WARN => get_string('error_warn', 'enrol_lmb'),
                            \enrol_lmb\logging::ERROR_MAJOR => get_string('error_major', 'enrol_lmb'));

    $settingslmb->add(new admin_setting_configselect('enrol_lmb/logginglevel', get_string('logginglevel', 'enrol_lmb'),
            get_string('logginglevel_help', 'enrol_lmb'), \enrol_lmb\logging::ERROR_NOTICE, $loggingoptions));

    // Parse Person --------------------------------------------------------------------------------.
    $settingslmb->add(new admin_setting_heading('enrol_lmb_parseperson', get_string('parseperson', 'enrol_lmb'), ''));
    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/parsepersonxml', get_string('parsepersonxml', 'enrol_lmb'),
            get_string('parsepersonxml_help', 'enrol_lmb'), 1));
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/recordsctid', get_string('recordsctid', 'enrol_lmb'),
//             get_string('recordsctidhelp', 'enrol_lmb'), 0));
    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/createnewusers', get_string('createnewusers', 'enrol_lmb'),
            get_string('createnewusers_help', 'enrol_lmb'), 1));
    $settingslmb->add(new admin_setting_configtext('enrol_lmb/createusersemaildomain',
            get_string('createusersemaildomain', 'enrol_lmb'), get_string('createusersemaildomain_help', 'enrol_lmb'), ''));
    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/ignoredomaincase', get_string('ignoredomaincase', 'enrol_lmb'),
            get_string('ignoredomaincase_help', 'enrol_lmb'), 0));
    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/donterroremail', get_string('donterroremail', 'enrol_lmb'),
            get_string('donterroremail_help', 'enrol_lmb'), 1));
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/imsdeleteusers', get_string('deleteusers', 'enrol_lmb'),
//             get_string('deleteusershelp', 'enrol_lmb'), 0));

    // Parse Course --------------------------------------------------------------------------------.
    $settingslmb->add(new admin_setting_heading('enrol_lmb_parsecourse', get_string('parsecourse', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/parsecoursexml', get_string('parsecoursexml', 'enrol_lmb'),
            get_string('parsecoursexml_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/coursetitle', get_string('coursetitle', 'enrol_lmb'),
            get_string('coursetitle_help', 'enrol_lmb'), '[RUBRIC]-[CRN]-[FULL]'));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcetitle', get_string('forcetitle', 'enrol_lmb'),
            get_string('forcetitle_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/courseshorttitle', get_string('courseshorttitle', 'enrol_lmb'),
            get_string('courseshorttitle_help', 'enrol_lmb'), '[DEPT][NUM]-[CRN][TERM]'));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forceshorttitle', get_string('forceshorttitle', 'enrol_lmb'),
            get_string('forceshorttitle_help', 'enrol_lmb'), 1));

    unset($options);
    $options = array();
    $options[settings::CREATE_COURSE_VISIBLE] = get_string('coursehiddenhidden', 'enrol_lmb');
    $options[settings::CREATE_COURSE_CRON] = get_string('coursehiddencron', 'enrol_lmb');
    $options[settings::CREATE_COURSE_HIDDEN] = get_string('coursehiddenvisible', 'enrol_lmb');
    $settingslmb->add(new admin_setting_configselect('enrol_lmb/coursehidden', get_string('coursehidden', 'enrol_lmb'),
            get_string('coursehidden_help', 'enrol_lmb'), settings::CREATE_COURSE_VISIBLE, $options));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/cronunhidecourses', get_string('cronunhidecourses', 'enrol_lmb'),
            get_string('cronunhidecourses_help', 'enrol_lmb'), 0));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/cronunhidedays', get_string('cronunhidedays', 'enrol_lmb'),
            get_string('cronunhidedays_help', 'enrol_lmb'), '0'));

    unset($options);
    $options = array();
    $options[settings::COURSE_CATS_TERMS] = get_string('termcat', 'enrol_lmb');
    $options[settings::COURSE_CATS_DEPTS] = get_string('deptcat', 'enrol_lmb');
    $options[settings::COURSE_CATS_DEPTS_SHORT] = get_string('deptcodecat', 'enrol_lmb');
    $options[settings::COURSE_CATS_TERM_DEPTS] = get_string('termdeptcat', 'enrol_lmb');
    $options[settings::COURSE_CATS_TERM_DEPTS_SHORT] = get_string('termdeptcodecat', 'enrol_lmb');
    $options[settings::COURSE_CATS_SELECTED] = get_string('selectedcat', 'enrol_lmb');
    $settingslmb->add(new admin_setting_configselect('enrol_lmb/cattype', get_string('categorytype', 'enrol_lmb'),
            get_string('categorytype_help', 'enrol_lmb'), settings::COURSE_CATS_TERMS, $options));

    $displaylist = coursecat::make_categories_list();

    $settingslmb->add(new admin_setting_configselect('enrol_lmb/catselect', get_string('catselect', 'enrol_lmb'),
            get_string('catselect_help', 'enrol_lmb'), 1, $displaylist));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/cathidden', get_string('cathidden', 'enrol_lmb'),
            get_string('cathidden_help', 'enrol_lmb'), 0));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcecat', get_string('forcecat', 'enrol_lmb'),
            get_string('forcecat_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/usemoodlecoursesettings',
            get_string('usemoodlecoursesettings', 'enrol_lmb'), get_string('usemoodlecoursesettings_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/computesections', get_string('computesections', 'enrol_lmb'),
            get_string('computesections_help', 'enrol_lmb'), 0));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcecomputesections',
            get_string('forcecomputesections', 'enrol_lmb'), get_string('forcecomputesections_help', 'enrol_lmb'), 0));

}

$settings->add('enrolsettingscat', $settingslmb);
