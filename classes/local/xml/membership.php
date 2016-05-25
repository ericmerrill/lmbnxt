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

namespace enrol_lmb\local\xml;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class membership extends base {
    /**
     * The data object path for this object.
     */
    const DATA_CLASS = false;

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = false;

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    /**
     * Processes the passed xml_node into a data object of the current type.
     *
     * @param xml_node $xmlobj The node to work on
     * @return enrol_lmb\local\data\person
     */
    public function process_xml_to_data($node) {
        if (!isset($node->MEMBER)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_membershiptype_no_members');
        }

        if (!isset($node->SOURCEDID->SOURCE) || !isset($node->SOURCEDID->ID)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_membershiptype_no_id');
        }

        $groupsource = $node->SOURCEDID->SOURCE->get_value();
        $groupid = $node->SOURCEDID->ID->get_value();

        if (is_array($node->MEMBER)) {
            $members = $node->MEMBER;
        } else {
            $members = array($node->MEMBER);
        }

        $results = array();

        foreach ($members as $member) {
            switch ($member->IDTYPE->get_value()) {
                case '1':
                    // A person member.
                    $enrol = new member_person();
                    $result = $enrol->process_xml_to_data($member);
                    $result->groupsdidsource = $groupsource;
                    $result->groupsdid = $groupid;
                    $results[] = $result;
                    break;
                case '2':
                    // A group member (crosslist).
                    $crosslist = new member_group();
                    $result = $crosslist->process_xml_to_data($member);
                    $result->groupsdidsource = $groupsource;
                    $result->groupsdid = $groupid;
                    $results[] = $result;
                    break;
                default:
                    // TODO unknown type error, not exception.
                    break;
            }
        }

        return $results;

        if (!isset($member->IDTYPE)) {
            throw new \enrol_lmb\local\exception\message_exception('exception_membershiptype_no_member_type');
        }



        throw new \enrol_lmb\local\exception\message_exception('exception_membershiptype_unknown_member_type');
    }

}
