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

use enrol_lmb\local\processors\xml;
use enrol_lmb\local\exception;
use enrol_lmb\local\response;
use enrol_lmb\local\status;

/**
 * Class for working with messages from XML from the LIS spec.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base extends xml\base {
    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = false;

    /**
     * Processes the passed xml_node into a data object of the current type.
     *
     * @param xml_node $node The node to work on
     * @return enrol_lmb\local\data\base
     */
    public function process_xml_to_data($node) {
        if (!$this->check_lis_namespace($node)) {
             throw new exception\message_exception('exception_lis_namespace');
        }

        return parent::process_xml_to_data($node);
    }

    public function get_response_object() {
        $res = new response\lis2();

        $res->set_namespace(static::NAMESPACE_DEF);

        return $res;
    }

    /**
     * Confirm the name that the namespace definition is correct.
     *
     * @param xml_node|array $node The XML node to process, or array of nodes
     * @return bool
     */
    protected function check_lis_namespace($node) {
        if (empty(static::NAMESPACE_DEF)) {
            throw new \coding_exception("NAMESPACE_DEF must be defined");
        }

        $value = $node->get_attribute('XMLNS');

        if (empty($value)) {
            return false;
        }

        if (stristr($value, static::NAMESPACE_DEF) === false) {
            return false;
        }

        return true;
    }

    /**
     * Get a basic success status object.
     *
     * @return status\lis2
     */
    public function get_success_status() {
        return new status\lis2(true, 'Success', 'Status', 'fullsuccess');
    }

    /**
     * Get a basic failure status object.
     *
     * @return status\lis2
     */
    public function get_failure_status() {
        return new status\lis2(false, 'Failure');
    }
}
