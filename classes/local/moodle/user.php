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
 * An object for converting data to moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\moodle;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\logging;
use enrol_lmb\settings;
use enrol_lmb\local\data;

require_once($CFG->dirroot.'/user/lib.php');

/**
 * Abstract object for converting a data object to Moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user extends base {
    protected static $customfields = [];

    protected $userid = false;

    /**
     * This function takes a data object and attempts to apply it to Moodle.
     *
     * @param data\base $data A data object to process.
     */
    public function convert_to_moodle(\enrol_lmb\local\data\base $data) {
        global $DB;

        if (!($data instanceof data\person)) {
            throw new \coding_exception('Expected instance of data\person to be passed.');
        }

        $settings = $this->settings;

        $this->data = $data;

        // Check if this user's email address is allowed.
        if (!$this->check_email_domain()) {
            // We allow different log levels based on a setting.
            $loglevel = logging::ERROR_WARN;
            if ((bool)$settings->get('donterroremail')) {
                $loglevel = logging::ERROR_NONE;
            }
            logging::instance()->log_line('User email not allowed by email domain settings.', $loglevel);
            return;
        }

        // See if we are going to be working with an existing or new user.
        $new = false;
        $user = $this->find_existing_user();
        if (empty($user)) {
            if (!(bool)$settings->get('createnewusers')) {
                // Don't create a new user if not enabled.
                logging::instance()->log_line('Not creating new users');
                return;
            }
            $new = true;
            $user = $this->create_new_user_object();
        } else {
            $userid = $user->id;
        }

        $username = $this->get_username();

        if (!empty($username)) {
            $user->username = $username;
        } else {
            if (empty($user->username)) {
                logging::instance()->log_line('No username could be determined for user. Cannot create.', logging::ERROR_NOTICE);
                return;
            } else {
                $error = 'User has no username with current settings. Keeping '.$user->username.'.';
                logging::instance()->log_line($error, logging::ERROR_WARN);
            }
        }

        // Set the user's auth plugin.
        // TODO - Option to force this.
        if ($new) {
            $auth = $settings->get('auth');
            if (!empty($auth)) {
                $user->auth = $auth;
            }
        }

        // In cases where either the user's ID number is changing, or they are new, we should check enrolments.
        $processenrols = false;
        if ($new || $user->idnumber != $this->data->sdid) {
            $user->idnumber = $this->data->sdid;
            $processenrols = true;
        }

        if ($new || $settings->get('forceemail')) {
            if (!empty($this->data->email)) {
                if ((bool)$settings->get('lowercaseemails')) {
                    $user->email = strtolower($this->data->email);
                } else {
                    $user->email = $this->data->email;
                }
            } else {
                $user->email = '';
            }
        }

        $nickname = false;
        if (isset($this->data->nickname)) {
            $nickname = $this->data->nickname;
        }

        if ($new || $settings->get('forcefirstname')) {
            if ($nickname && $settings->get('nickname') == settings::USER_NICK_FIRST) {
                $user->firstname = $nickname;
            } else {
                $user->firstname = $this->data->givenname;
            }
        }

        if ($new || $settings->get('forcealtname')) {
            if ($nickname && $settings->get('nickname') == settings::USER_NICK_ALT) {
                $user->alternatename = $nickname;
            }
        }

        if ($new || $settings->get('forcelastname')) {
            $user->lastname = $this->data->familyname;
        }




        // TODO - Need to make sure there won't be a username collision.



        try {
            if ($new) {
                logging::instance()->log_line('Creating new Moodle user');
                $this->userid = user_create_user($user, false, true);
            }  else {
                logging::instance()->log_line('Updating Moodle user');
                user_update_user($user, false, true);
            }
        } catch (\moodle_exception $e) {
            // TODO - catch exception and pass back up to message.
            $error = 'Fatal exception while inserting/updating user. '.$e->getMessage();
            logging::instance()->log_line($error, logging::ERROR_MAJOR);
            throw $e;
        }

        // This has to happen after saving, because the user record ID is needed first.
        $mapping = $settings->get('customfield1mapping');
        if (!empty($mapping)) {
            $value = $this->get_field_for_setting($settings->get('customfield1source'));
            $this->save_custom_profile_value($mapping, $value);
        }

        if ($processenrols) {
            course_enrolments::reprocess_enrolments_for_user_sdid($user->idnumber);
        }
    }

    /**
     * Checks if this is a allowed user based on createusersemaildomain and ignoredomaincase.
     *
     * @return bool True if the user is allowed, false if not.
     */
    protected function check_email_domain() {
        $domain = $this->settings->get('createusersemaildomain');

        // We allow this if the setting is empty.
        if (empty($domain)) {
            return true;
        }

        if (empty($this->data->email)) {
            return false;
        }

        // Extract the domain from the email address.
        $emaildomain = explode('@', $this->data->email);
        if (count($emaildomain) !== 2) {
            // Invalid email address.
            return false;
        }
        $emaildomain = $emaildomain[1];

        if ($this->settings->get('ignoredomaincase')) {
            $matchappend = 'i';
        } else {
            $matchappend = '';
        }

        if (!preg_match('/^'.$domain.'$/'.$matchappend, $emaildomain)) {
            // If the match failed, then we return false.
            return false;
        }

        return true;
    }

    /**
     * Find an existing user record for this instance.
     *
     * @return false|\stdClass User object or false if not found.
     */
    protected function find_existing_user() {
        global $DB;

        // First try to find based on the idnumber/sdid.
        $existing = self::get_user_for_sdid($this->data->sdid);

        if ($existing) {
            return $existing;
        }

        // If we get here, and colsolidate usernames isn't set, then we didn't find it.
        if (!$this->settings->get('consolidateusernames')) {
            return false;
        }

        // See if we can find a user with the same username, and now ID number.
        $username = $this->get_username();

        if (empty($username)) {
            return false;
        }

        $existing = $DB->get_record('user', array('username' => $username));

        if (!$existing) {
            return false;
        }

        if (!empty($existing->idnumber)) {
            $error = "Existing user with username {$username} found, but has non-matching ID Number.";
            logging::instance()->log_line($error, logging::ERROR_NOTICE);
            return false;
        }

        return $existing;
    }

    /**
     * Returns a user record for the passed sdid.
     *
     * @param string $sdid
     * @return false|\stdClass
     */
    public static function get_user_for_sdid($sdid) {
        global $DB;

        return $DB->get_record('user', array('idnumber' => $sdid));
    }

    /**
     * Create a new user object for this instance.
     *
     * @return \stdClass A basic new user object to work with.
     */
    protected function create_new_user_object() {
        global $CFG;

        $user = new \stdClass();

        if (isset($CFG->mnet_localhost_id)) {
            $user->mnethostid = $CFG->mnet_localhost_id;
        } else {
            $user->mnethostid = 1;
        }

        $user->confirmed = 1;

        // Add default site language.
        $user->lang = $CFG->lang;

        return $user;
    }

    /**
     * Find the proper username for this user.
     *
     * @return false|string The username, or false if can't be determined.
     */
    protected function get_username() {

        $username = $this->get_field_for_setting($this->settings->get('usernamesource'));

        if (empty($username) && $this->settings->get('sourcedidfallback')) {
            // Fallback to the sourcedid if we can't find a username.
            $username = (string)$this->data->sdid;
        }

        if (empty($username)) {
            return false;
        }

        // Moodle requires usernames to be lowercase.
        $username = strtolower($username);

        return $username;
    }

    /**
     * Calculates the field value to return based on a setting.
     *
     * @param int $setting
     * @return false|string
     */
    protected function get_field_for_setting($setting) {
        $result = false;
        switch ($setting) {
            case (settings::USER_NAME_EMAIL):
                if (isset($this->data->email)) {
                    $result = $this->data->email;
                }
                break;
            case (settings::USER_NAME_EMAILNAME):
                if (isset($this->data->email) && preg_match('{(.+?)@.*?}is', $this->data->email, $matches)) {
                    $result = trim($matches[1]);
                }
                break;
            case (settings::USER_NAME_LOGONID):
                if (isset($this->data->logonid)) {
                    $result = $this->data->logonid;
                }
                break;
            case (settings::USER_NAME_SCTID):
                if (isset($this->data->sctid)) {
                    $result = $this->data->sctid;
                }
                break;
            case (settings::USER_NAME_EMAILID):
                if (isset($this->data->emailid)) {
                    $result = $this->data->emailid;
                }
                break;
            case (settings::USER_NAME_OTHER):
                $otherid = $this->settings->get('otheruserid');
                if (!empty($otherid) && isset($this->data->userid[$otherid]->userid)) {
                    $result = $this->data->userid[$otherid]->userid;
                }
                break;
        }

        if (empty($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Save a custom profile field value.
     *
     * @param string $shortname The field shortname
     * @param string $value The value to save
     * @return true|false True on success
     */
    protected function save_custom_profile_value($shortname, $value) {
        global $DB;

        if (empty($this->userid)) {
            return false;
        }

        $field = $this->get_custom_profile_field($shortname);
        if (empty($field)) {
            return false;
        }

        $data = new \stdClass();
        $data->userid  = $this->userid;
        $data->fieldid = $field->id;
        if (!empty($value)) {
            $data->data = $value;
        } else {
            $data->data = '';
        }

        if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $data->userid, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
            $DB->update_record('user_info_data', $data);
        } else {
            $DB->insert_record('user_info_data', $data);
        }

        return true;
    }

    /**
     * Get a profile field record for the given shortname.
     *
     * @param string $shortname
     * @return false|\stdClass
     */
    protected function get_custom_profile_field($shortname) {
        global $DB;

        // See if it is in the cache already.
        if (!isset(self::$customfields[$shortname])) {
            self::$customfields[$shortname] = $DB->get_record('user_info_field', array('shortname' => $shortname));
        }

        return self::$customfields[$shortname];
    }
}
