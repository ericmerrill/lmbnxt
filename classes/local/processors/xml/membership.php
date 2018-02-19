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

namespace enrol_lmb\local\processors\xml;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\data;

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
     * @param xml_node $node The node to work on
     * @return data\member_user[]|data\crosslist
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

        // We make a temporary crosslist object in case this is a crosslist message.
        $crosslist = new data\crosslist();
        $crosslist->sdid = $groupid;
        $crosslist->sdidsource = $groupsource;

        if (isset($node->TYPE)) {
            $crosslist->type = $node->TYPE->get_value();
        }

        if (is_array($node->MEMBER)) {
            $members = $node->MEMBER;
        } else {
            $members = array($node->MEMBER);
        }

        $results = array();

        $iscrosslist = false;
        foreach ($members as $member) {
            if (!isset($member->IDTYPE)) {
                // We don't throw an exception so that other members can process.
                // TODO log error.
                continue;
            }

            switch ($member->IDTYPE->get_value()) {
                case '1':
                    // A person member.
                    $enrol = new person_member();
                    $enrol->set_group_info($groupid, $groupsource);
                    $result = $enrol->process_xml_to_data($member);
                    $results[] = $result;
                    break;
                case '2':
                    // A group member (crosslist).
                    $groupmember = new member_group();
                    $result = $groupmember->process_xml_to_data($member);
                    $crosslist->add_member($result);
                    $iscrosslist = true;
                    break;
                default:
                    // We don't throw an exception so that other members can process.
                    // TODO unknown type error log.
                    break;
            }
        }

        if ($iscrosslist) {
            $crosslist->merge_existing();

            $this->dataobj = $crosslist;

            $this->post_mappings();

            return $crosslist;
        }

        $this->post_mappings();

        return $results;
    }

}
