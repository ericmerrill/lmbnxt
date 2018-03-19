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
use enrol_lmb\local\moodle;
use enrol_lmb\local\data;
use enrol_lmb\logging;

$settings = new admin_category('enrolsettingscat', get_string('pluginname', 'enrol_lmb'), $settings->hidden);
$settingslmb = new admin_settingpage('enrolsettingslmb', get_string('settings'), 'moodle/site:config');

if ($ADMIN->fulltree) {
    $settingslmb->add(new admin_setting_configfile('enrol_lmb/logpath', get_string('logpath', 'enrol_lmb'),
        get_string('logpath_help', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configfile('enrol_lmb/xmlpath', get_string('xmlpath', 'enrol_lmb'),
        get_string('xmlpath_help', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configdirectory('enrol_lmb/extractpath', get_string('extractpath', 'enrol_lmb'),
        get_string('extractpath_help', 'enrol_lmb'), ''));

    $loggingoptions = array(logging::ERROR_NONE => get_string('error_all', 'enrol_lmb'),
                            logging::ERROR_NOTICE => get_string('error_notice', 'enrol_lmb'),
                            logging::ERROR_WARN => get_string('error_warn', 'enrol_lmb'),
                            logging::ERROR_MAJOR => get_string('error_major', 'enrol_lmb'));

    $settingslmb->add(new admin_setting_configselect('enrol_lmb/logginglevel', get_string('logginglevel', 'enrol_lmb'),
            get_string('logginglevel_help', 'enrol_lmb'), logging::ERROR_NOTICE, $loggingoptions));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/logwsmessages', get_string('logwsmessages', 'enrol_lmb'),
            get_string('logwsmessages_help', 'enrol_lmb'), 0));

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
            get_string('ignoredomaincase_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/donterroremail', get_string('donterroremail', 'enrol_lmb'),
            get_string('donterroremail_help', 'enrol_lmb'), 1));

//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/imsdeleteusers', get_string('deleteusers', 'enrol_lmb'),
//             get_string('deleteusershelp', 'enrol_lmb'), 0));
//

    unset($options);
    $options = array();
    $options[settings::USER_NAME_EMAIL] = get_string('fullemail', 'enrol_lmb');
    $options[settings::USER_NAME_EMAILNAME] = get_string('emailname', 'enrol_lmb');
    $options[settings::USER_NAME_LOGONID] = get_string('useridtypelogin', 'enrol_lmb');
    $options[settings::USER_NAME_SCTID] = get_string('useridtypesctid', 'enrol_lmb');
    $options[settings::USER_NAME_EMAILID] = get_string('useridtypeemail', 'enrol_lmb');
    $options[settings::USER_NAME_OTHER] = get_string('useridtypeother', 'enrol_lmb');
    $settingslmb->add(new admin_setting_configselect('enrol_lmb/usernamesource', get_string('usernamesource', 'enrol_lmb'),
            get_string('usernamesource_help', 'enrol_lmb'), settings::USER_NAME_EMAILNAME, $options));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/useridtypeother', get_string('otheruserid', 'enrol_lmb'),
            get_string('otheruserid_help', 'enrol_lmb'), ''));

    // TODO - Option to allow no email address.


    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/lowercaseemails', get_string('lowercaseemails', 'enrol_lmb'),
            get_string('lowercaseemails_help', 'enrol_lmb'), 0));


    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/sourcedidfallback', get_string('sourcedidfallback', 'enrol_lmb'),
            get_string('sourcedidfallback_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/consolidateusernames',
            get_string('consolidateusernames', 'enrol_lmb'), get_string('consolidateusernames_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/customfield1mapping', get_string('customfield1mapping', 'enrol_lmb'),
            get_string('customfield1mapping_help', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configselect('enrol_lmb/customfield1source', get_string('customfield1source', 'enrol_lmb'),
            get_string('customfield1source_help', 'enrol_lmb'), 'email', $options));

    unset($options);
    $modules = \core\plugininfo\auth::get_enabled_plugins();
    $options = array();
    foreach ($modules as $module => $path) {
        $options[$module] = get_string("pluginname", "auth_".$module);
    }
    $settingslmb->add(new admin_setting_configselect('enrol_lmb/auth', get_string('authmethod', 'enrol_lmb'),
            get_string('authmethod_help', 'enrol_lmb'), 'manual', $options));
//
//     unset($options);
//     $options = array();
//     $options['none'] = get_string('none', 'enrol_lmb');
//     $options['loginid'] = get_string('useridtypelogin', 'enrol_lmb');
//     $options['sctid'] = get_string('useridtypesctid', 'enrol_lmb');
//     $options['emailid'] = get_string('useridtypeemail', 'enrol_lmb');
//     $options['other'] = get_string('useridtypeother', 'enrol_lmb');
//     $settingslmb->add(new admin_setting_configselect('enrol_lmb/passwordnamesource', get_string('passwordsource', 'enrol_lmb'),
//             get_string('passwordsourcehelp', 'enrol_lmb'), 'none', $options));
//
//     $settingslmb->add(new admin_setting_configtext('enrol_lmb/passworduseridtypeother', get_string('otherpassword', 'enrol_lmb'),
//             get_string('otherpasswordhelp', 'enrol_lmb'), ''));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcepassword', get_string('forcepassword', 'enrol_lmb'),
//             get_string('forcepasswordhelp', 'enrol_lmb'), 1));

    $options = array();
    $options[settings::USER_NICK_DISABLED] = get_string('nickdisabled', 'enrol_lmb');
    $options[settings::USER_NICK_FIRST] = get_string('firstname', 'enrol_lmb');
    $options[settings::USER_NICK_ALT] = get_string('altname', 'enrol_lmb');
    $settingslmb->add(new admin_setting_configselect('enrol_lmb/nickname', get_string('nickname', 'enrol_lmb'),
            get_string('nickname_help', 'enrol_lmb'), settings::USER_NICK_DISABLED, $options));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcealtname', get_string('forcealtname', 'enrol_lmb'),
            get_string('forcealtname_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcefirstname', get_string('forcefirstname', 'enrol_lmb'),
            get_string('forcefirstname_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcelastname', get_string('forcelastname', 'enrol_lmb'),
            get_string('forcelastname_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forceemail', get_string('forceemail', 'enrol_lmb'),
            get_string('forceemail_help', 'enrol_lmb'), 1));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/includetelephone', get_string('includetele', 'enrol_lmb'),
//             get_string('includetelehelp', 'enrol_lmb'), 0));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcetelephone', get_string('forcetele', 'enrol_lmb'),
//             get_string('forcetelehelp', 'enrol_lmb'), 0));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/includeaddress', get_string('includeadr', 'enrol_lmb'),
//             get_string('includeadrhelp', 'enrol_lmb'), 0));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forceaddress', get_string('forceadr', 'enrol_lmb'),
//             get_string('forceadrhelp', 'enrol_lmb'), 0));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/includecity', get_string('includecity', 'enrol_lmb'),
//             get_string('includecityhelp', 'enrol_lmb'), 0));
//
//     unset($options);
//     $options = array();
//     $options['xml'] = get_string('locality', 'enrol_lmb');
//     $options['standardxml'] = get_string('usestandardcityxml', 'enrol_lmb');
//     $options['standard'] = get_string('usestandardcity', 'enrol_lmb');
//     $settingslmb->add(new admin_setting_configselect('enrol_lmb/defaultcity', get_string('defaultcity', 'enrol_lmb'),
//             get_string('defaultcityhelp', 'enrol_lmb'), 'xml', $options));
//
//     $settingslmb->add(new admin_setting_configtext('enrol_lmb/standardcity', get_string('standardcity', 'enrol_lmb'),
//             get_string('standardcityhelp', 'enrol_lmb'), ''));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/forcecity', get_string('forcecity', 'enrol_lmb'),
//             get_string('forcecityhelp', 'enrol_lmb'), 0));

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

    // Parse Enrollments ---------------------------------------------------------------------------.
    $settingslmb->add(new admin_setting_heading('enrol_lmb_parseenrol', get_string('parseenrol', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/parseenrolxml', get_string('parseenrolxml', 'enrol_lmb'),
            get_string('parseenrolxml_help', 'enrol_lmb'), 1));

    // During initial install we can't reliably get the assignable roles.
    if (!during_initial_install()) {
        $coursecontext = context_course::instance(SITEID);
        $assignableroles = get_assignable_roles($coursecontext);
        $assignableroles = [0 => get_string('ignore', 'enrol_lmb')] + $assignableroles;

        $imsroles = array(
            '01' => get_string('imsrolename01', 'enrol_lmb'),
            '02' => get_string('imsrolename02', 'enrol_lmb'),
            '03' => get_string('imsrolename03', 'enrol_lmb'),
            '04' => get_string('imsrolename04', 'enrol_lmb'),
            '05' => get_string('imsrolename05', 'enrol_lmb')
        );

        foreach ($imsroles as $imsrolenum => $imsrolename) {
            $default = moodle\enrolment::get_default_role_id($imsrolenum);
            if (empty($default)) {
                $default = 0;
            }

            $settingslmb->add(new admin_setting_configselect('enrol_lmb/imsrolemap'.$imsrolenum,
                    $imsrolename, '', $default, $assignableroles));
        }
    }

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/restrictenroldates',
            get_string('restrictenroldates', 'enrol_lmb'),
            get_string('restrictenroldates_help', 'enrol_lmb'), 0));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/unenrolmember', get_string('unenrolmember', 'enrol_lmb'),
//             get_string('unenrolmemberhelp', 'enrol_lmb'), 0));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/disableenrol', get_string('disableenrol', 'enrol_lmb'),
//             get_string('disableenrolhelp', 'enrol_lmb'), 0));
//
//     $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/recovergrades', get_string('recovergrades', 'enrol_lmb'),
//             get_string('recovergradeshelp', 'enrol_lmb'), 1));

    // Parse XLS -----------------------------------------------------------------------------------.
    $settingslmb->add(new admin_setting_heading('enrol_lmb_parsexls', get_string('parsexls', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/parsexlsxml', get_string('parsexlsxml', 'enrol_lmb'),
            get_string('parsexlsxml_help', 'enrol_lmb'), 1));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/xlstitle', get_string('xlstitle', 'enrol_lmb'),
            get_string('xlstitle_help', 'enrol_lmb'), '[XLSID] - [REPEAT]'));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/xlstitlerepeat', get_string('xlstitlerepeat', 'enrol_lmb'),
            get_string('xlstitlerepeat_help', 'enrol_lmb'), '[CRN]'));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/xlstitledivider', get_string('xlstitledivider', 'enrol_lmb'),
            get_string('xlstitledivider_help', 'enrol_lmb'), ' / '));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/xlsshorttitle', get_string('xlsshorttitle', 'enrol_lmb'),
            get_string('xlsshorttitle_help', 'enrol_lmb'), '[XLSID]'));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/xlsshorttitlerepeat', get_string('xlsshorttitlerepeat', 'enrol_lmb'),
            get_string('xlsshorttitlerepeat_help', 'enrol_lmb'), ''));

    $settingslmb->add(new admin_setting_configtext('enrol_lmb/xlsshorttitledivider',
            get_string('xlsshorttitledivider', 'enrol_lmb'), get_string('xlsshorttitledivider_help', 'enrol_lmb'), ''));

    unset($options);
    $options = [data\crosslist::GROUP_TYPE_MERGE => get_string('xlsmergecourse', 'enrol_lmb'),
                data\crosslist::GROUP_TYPE_META => get_string('xlsmetacourse', 'enrol_lmb')];
    $settingslmb->add(new admin_setting_configselect('enrol_lmb/xlstype', get_string('xlstype', 'enrol_lmb'),
            get_string('xlstype_help', 'enrol_lmb'), data\crosslist::GROUP_TYPE_MERGE, $options));

    $settingslmb->add(new admin_setting_configcheckbox('enrol_lmb/xlsmergegroups', get_string('xlsmergegroups', 'enrol_lmb'),
            get_string('xlsmergegroups_help', 'enrol_lmb'), 1));
}

$settings->add('enrolsettingscat', $settingslmb);

$settings->add('enrolsettingscat', new admin_category('enroltoolsscat',
        get_string('tools', 'enrol_lmb'), false));

//if (!empty(get_config('enrol_lmb', 'needslegacyupgrade'))) {
    $settings->add("enroltoolsscat", new admin_externalpage('enrollmbtooldatamigration',
            get_string('page_datamigration', 'enrol_lmb'),
            "$CFG->wwwroot/enrol/lmb/tools/datamigration.php", "moodle/site:config"));
//}
