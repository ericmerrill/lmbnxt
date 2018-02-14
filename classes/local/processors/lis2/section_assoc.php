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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\processors\lis2;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\types;
use enrol_lmb\local\data;

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_assoc extends base {
    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/cmsv1p0/wsdl11/sync/imscms_v1p0";

    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\crosslist';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/lis2/mappings/section_assoc.json';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    /**
     * Process the section nodes.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_section_node($node, $mapping) {
        // We need to add a member for each time this node is called.
        $member = new data\crosslist_member();
        $member->sdid = $node->get_value();

        if (isset($this->dataobj->sdidsource)) {
            $member->sdidsource = $this->dataobj->sdidsource;
        }
        // By definition, it is active by being here.
        $member->status = 1;

        $this->newmembers[$member->sdid] = $member;
        $this->dataobj->add_member($member);
    }

    /**
     * Do a little bit of extra processing on the SourceDID node.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @param array $mapping The mapping for the field
     */
    protected function process_sdid_node($node, $mapping) {
        $sdid = $node->get_value();

        // We are going to prepend XLS for various legacy reasons, but only if that value isn't
        // "Plugin Internal" and it doesn't already start with XLS.
        if (strcasecmp('Plugin Internal', $sdid) !== 0 && stripos($sdid, 'XLS') !== 0) {
            $sdid = 'XLS'.$sdid;
        }

        $this->dataobj->sdid = $sdid;
    }

    protected function post_mappings() {
        // LIS crosslists have the property that if a member is missing, it is considered dropped,
        // so we need to take care of that.

        $existing = $this->dataobj->get_existing_members();
        $newmembers = $this->dataobj->get_members();

        foreach ($newmembers as $member) {
            $member->messagetime = time();
        }

        foreach ($existing as $exists) {
            if (!isset($newmembers[$exists->sdid])) {
                $exists->status = 0;
                $newmembers[$exists->sdid] = $exists;
            }
        }

        $this->dataobj->set_members($newmembers);
    }
}
