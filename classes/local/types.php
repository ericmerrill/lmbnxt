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
 * Works on types of messages.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class types {

    protected static $types = array('person');

    public static function register_processor_paths(\enrol_lmb\parse_processor $processor) {
        $types = array('person');
        foreach ($types as $type) {
            $class = '\\enrol_lmb\\local\\types\\'.$type.'\\xml';
            $paths = $class::get_paths();
            foreach ($paths as $path) {
                $processor->register_path($type, $path, true);
            }
        }



        /*$processor->register_path('person', '/person/sourcedid');
        $processor->register_path('person', '/person/sourcedid/source');
        $processor->register_path('person', '/person/sourcedid/id');
        $processor->register_path('person', '/person/userid');
        $processor->register_path('person', '/person/email');
        $processor->register_path('person', '/person/name');
        $processor->register_path('person', '/person/name/fn');
        $processor->register_path('person', '/person/name/n');
        $processor->register_path('person', '/person/name/n/family');
        $processor->register_path('person', '/person/name/n/given');
        $processor->register_path('person', '/person/name/n/partname');
        $processor->register_path('person', '/person/demographics/gender');
        $processor->register_path('person', '/person/institutionrole');
        $processor->register_path('person', '/person/extension/luminisperson');
        $processor->register_path('person', '/person/extension/luminisperson/academicmajor');
        $processor->register_path('person', '/person/extension/luminisperson/customrole');*/
    }

}