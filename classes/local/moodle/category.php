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
require_once($CFG->libdir.'/coursecatlib.php');

use enrol_lmb\logging;
use enrol_lmb\settings;
use enrol_lmb\local\data;

/**
 * Abstract object for converting a data object to Moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category extends base {
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
                return settings::get_settings()->get('catselect');
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

        // TODO caching.

        // First find by idnumber.
        if ($field = $DB->get_field('course_categories', 'id', array('idnumber' => $termsdid))) {
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

                    return $catrecord->id;
                }
            }
        }

        $cat = static::create_new_category($term->description, $term->sdid);

        if (empty($cat)) {
            return static::get_default_category_id();
        }

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
        $cat = new \stdClass();
        $cat->name = \core_text::substr($title, 0, 255);
        $cat->idnumber = $idnumber;

        if (settings::get_settings()->get('cathidden')) {
            $cat->visible = 0;
        } else {
            $cat->visible = 1;
        }

        try {
            $cat = \coursecat::create($cat);
        } catch (\moodle_exception $e) {
            $error = "Exception while trying to create category: ".$e->getMessage();
            logging::instance()->log_line($error, logging::ERROR_MAJOR);
        }

        return $cat->get_db_record();


    }

    /**
     * Gets the default category id to use.
     *
     * @return int
     */
    protected static function get_default_category_id() {
        global $DB;

        $cats = $DB->get_records('course_categories', null, 'id ASC', 'id', 0, 1);
        $cat = array_pop($cats);

        return $cat->id;
    }
}
