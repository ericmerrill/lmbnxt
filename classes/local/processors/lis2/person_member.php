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

namespace enrol_lmb\local\processors\lis2;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person_member extends base {
    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/mms2p0/wsdl11/sync/imsmms_v2p0";

    /**
     * Path to the mapping file.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/lis2/mappings/person_member.json';

    /**
     * Data class used by this type.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\person_member';

    // Note - The MembershipRequest spec only allows 1 member per message.

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    /**
     * Process the role type nodes.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_roletype_node($node, $mapping) {
        $role = $node->get_value();
        $this->dataobj->lis_roletype = $role;

        $roletype = self::get_roletype_for_name($role);
        if ($roletype !== false) {
            $this->dataobj->roletype = $roletype;
        } else {
            $this->dataobj->roletype = "00";
            throw new \enrol_lmb\local\exception\message_exception('exception_member_roletype_unknown', '', $role);
        }
    }

    /**
     * Process the list status nodes.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_status_node($node, $mapping) {
        // WSDL defines possible values as Active and Inactive, but giving a bit more leeway.
        $active = $node->get_value();
        $this->dataobj->lis_status = $active;

        if (strcasecmp("Active", $active) === 0 || $active === '1' || strcasecmp($active, 'true') === 0) {
            $this->dataobj->status = 1;
        } else if (strcasecmp("Inactive", $active) === 0 || $active === '0' || strcasecmp($active, 'false') === 0) {
            $this->dataobj->status = 0;
        } else {
            $this->dataobj->status = 0;
            throw new \enrol_lmb\local\exception\message_exception('exception_member_status_unknown', '', $active);
        }
    }

    /**
     * Converts an input role name into a role number.
     *
     * @param string $role The role name
     * @return string|false The role number (in string form) or false
     */
    public static function get_roletype_for_name($role) {
        // TODO - should be based on settings (This is a setting in ILP).
        // TODO - Offical vocab is in roletypevocabularyv1p0.xml.

        if (strcasecmp("editingteacher", $role) === 0) {
            return "02";
        } else if (strcasecmp("student", $role) === 0) {
            return "01";
        }

        return false;
    }
    // TODO - Student fields.
    // TODO - restrict fields.
}
