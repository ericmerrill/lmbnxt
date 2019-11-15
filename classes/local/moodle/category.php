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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\moodle;

defined('MOODLE_INTERNAL') || die();

global $CFG;
if (!class_exists('\\core_course_category')) {
    require_once($CFG->libdir.'/coursecatlib.php');
}

use enrol_lmb\logging;
use enrol_lmb\lock_factory;
use enrol_lmb\settings;
use enrol_lmb\local\data;
use enrol_lmb\local\exception;

/**
 * Abstract object for converting a data object to Moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category extends base {
    protected static $confirmedcatids = [];

    protected static $termcatids = [];

    public function convert_to_moodle(data\base $data) {
        // TODO - We need to updating any existing categories based on this term.
        // TODO - We need also should try to update department names and such...
    }

    /**
     * Gets the category ID for a passed course.
     *
     * @param data\section $course
     */
    public static function get_category_id(data\section $course) {
        switch (settings::get_settings()->get('cattype')) {
            case (settings::COURSE_CATS_TERMS):
                return static::get_term_category_id($course->termsdid);
                break;
            case (settings::COURSE_CATS_DEPTS):
                // TODO.
                break;
            case (settings::COURSE_CATS_DEPTS_SHORT):
                // TODO.
                break;
            case (settings::COURSE_CATS_TERM_DEPTS):
                // TODO.
                break;
            case (settings::COURSE_CATS_TERM_DEPTS_SHORT):
                // TODO.
                break;
            case (settings::COURSE_CATS_SELECTED):
                return static::get_effective_category_id(settings::get_settings()->get('catselect'));
                break;

        }

        return static::get_default_category_id();
    }

    /**
     * Get the term category id for a passed term sourcedid.
     *
     * @param string $termsdid
     * @return int
     */
    protected static function get_term_category_id($termsdid) {
        global $DB;

        if (isset(static::$termcatids[$termsdid])) {
            return static::$termcatids[$termsdid];
        }

        // First find by idnumber.
        if ($field = $DB->get_field('course_categories', 'id', array('idnumber' => $termsdid))) {
            static::$termcatids[$termsdid] = $field;
            return $field;
        }

        $term = data\term::get_term($termsdid);
        if (empty($term)) {
            logging::instance()->log_line("Term {$termsdid} not found. Using default category.", logging::ERROR_WARN);

            return static::get_default_category_id();
        }

        // Find an existing category without the idnumber set, but with the term name.
        if ($catrecords = $DB->get_records('course_categories', array('name' => $term->description))) {
            // Only do it if we find exactly 1 record, and it has no idnumber.
            if (count($catrecords) === 1) {
                $catrecord = array_pop($catrecords);
                if (empty($catrecord->idnumber)) {
                    // Save the idnumber of future use.
                    $DB->set_field('course_categories', 'idnumber', $termsdid, array('id' => $catrecord->id));
                    static::$termcatids[$termsdid] = $catrecord->id;
                    return $catrecord->id;
                }
            }
        }

        $cat = static::create_new_category($term->description, $term->sdid);

        if (empty($cat)) {
            $text = "Could not create category for term {$termsdid}. Using default category.";
            logging::instance()->log_line($text, logging::ERROR_WARN);
            return static::get_default_category_id();
        }

        static::$termcatids[$termsdid] = $cat->id;
        return $cat->id;
    }

    /**
     * Create a new category object based on the given information.
     *
     * @param string $title
     * @param string $idnumber
     * @return \stdClass
     */
    protected static function create_new_category($title, $idnumber) {
        global $DB;

        // We need a lock because sometimes creating a category can cause collisions.
        // Use use the course lock, because in some cases another create course process can see the created
        // category before it has been fully setup and had its context created, causing... problems.
        if (!$lock = lock_factory::get_category_create_lock()) {
            logging::instance()->log_line("Could not aquire lock for category creation.", logging::ERROR_WARN);

            throw new exception\category_lock_exception();
        }

        // Now make sure it wasn't created since we aquired the lock.
        if ($record = $DB->get_record('course_categories', ['idnumber' => $idnumber])) {
            $lock->release();

            return $record;
        }

        $cat = new \stdClass();
        $cat->name = \core_text::substr($title, 0, 255);
        $cat->idnumber = $idnumber;

        if (settings::get_settings()->get('cathidden')) {
            $cat->visible = 0;
        } else {
            $cat->visible = 1;
        }

        if ($catid = settings::get_settings()->get('catinselected')) {
            $cat->parent = static::get_effective_category_id($catid);
        }

        try {
            // For depreciation of coursecat in Moodle 3.6. Remove at a later date.
            if (class_exists('\\core_course_category')) {
                $cat = \core_course_category::create($cat);
            } else {
                $cat = \coursecat::create($cat);
            }
        } catch (\moodle_exception $e) {
            $error = "Exception while trying to create category: ".$e->getMessage();
            logging::instance()->log_line($error, logging::ERROR_MAJOR);
        }

        $lock->release();

        return $cat->get_db_record();


    }

    /**
     * Gets the default category id to use.
     *
     * @return int
     */
    public static function get_default_category_id() {
        global $DB;

        $catid = settings::get_settings()->get('unknowncat');

        if (isset(static::$confirmedcatids[$catid])) {
            return static::$confirmedcatids[$catid];
        }

        if ($DB->record_exists('course_categories', ['id' => $catid])) {
            static::$confirmedcatids[$catid] = $catid;
            return $catid;
        }

        $cats = $DB->get_records('course_categories', null, 'id ASC', 'id', 0, 1);
        $cat = array_pop($cats);

        // Store the mismatch id so we don't have to try again later.
        static::$confirmedcatids[$catid] = $cat->id;

        return $cat->id;
    }

    /**
     * Returns the true category id to use, in case it doesn't really exist.
     *
     * @param int $catid The category id to check
     * @return int
     */
    protected static function get_effective_category_id($catid) {
        global $DB;

        if (isset(static::$confirmedcatids[$catid])) {
            return static::$confirmedcatids[$catid];
        }

        if ($DB->record_exists('course_categories', ['id' => $catid])) {
            static::$confirmedcatids[$catid] = $catid;
            return $catid;
        }

        return static::get_default_category_id();
    }
}
