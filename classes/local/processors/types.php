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

namespace enrol_lmb\local\processors;

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
    /** @var array Associative array and message root node names (types) and the processor class */
    protected static $types = array('person' => '\\enrol_lmb\\local\\processors\\xml\\person',
                                    'group' => '\\enrol_lmb\\local\\processors\\xml\\group',
                                    'membership' => '\\enrol_lmb\\local\\processors\\xml\\membership',
                                    'replacemembershiprequest' => '\\enrol_lmb\\local\\processors\\lis2\\membership',
                                    'replacepersonrequest' => '\\enrol_lmb\\local\\processors\\lis2\\person',
                                    'replacecoursesectionrequest' => '\\enrol_lmb\\local\\processors\\lis2\\section',
                                    'replacegrouprequest' => '\\enrol_lmb\\local\\processors\\lis2\\group',
                                    'deletemembershiprequest' => '\\enrol_lmb\\local\\processors\\lis2\\person_member_delete',
                                    'replacesectionassociationrequest' => '\\enrol_lmb\\local\\processors\\lis2\\section_assoc',
                                    'imsx_syncrequestheaderinfo' => false);


    /** @var array Array of processor nodes */
    protected static $processors = array();

    /**
     * Returns an array of type names (root node names) available.
     *
     * @return array
     */
    public static function get_types() {
        return array_keys(self::$types);
    }

    /**
     * Returns the statically cached processor that goes with a type.
     *
     * @param string $type The type to get the processor for
     * @return xml\base
     */
    public static function get_type_processor($type) {
        $type = strtolower($type);
        if (!isset(self::$types[$type])) {
            return false;
        }

        $class = self::$types[$type];

        return self::get_processor_for_class($class);
    }

    /**
     * Returns the statically cached processor that goes with a class name.
     *
     * @param string $class The class path to get the processor for
     * @return xml\base
     */
    public static function get_processor_for_class($class) {
        if (empty($class)) {
            return false;
        }

        if (isset(self::$processors[$class])) {
            return self::$processors[$class];
        }

        $processor = new $class();
        self::$processors[$class] = $processor;

        return $processor;
    }
}
