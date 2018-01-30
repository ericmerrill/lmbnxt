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
 * The primary controller for file based imports.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\exception;
use enrol_lmb\local\processors;
use enrol_lmb\local\xml_node;
use enrol_lmb\local\status;

/**
 * An object that tracks the flow of a message though its life.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message {
    /** @var local\xml_node The XML node this message is based on */
    protected $xmlnode = null;

    /** @var local\data\base[] An array of data objects created from the XML */
    protected $dataobjs = array();

    /** @var local\response\base The response object for this particular message */
    protected $response = null;

    /** @var local\processors\xml\base The singular instances of logging */
    protected $processor = null;

    /** @var controller The controller object */
    protected $controller = null;

    /** @var status\base A status object */
    protected $status = null;

    /**
     * Build this object
     *
     * @param controller $controller The controller for this message
     * @param xml_node $node The node for this message
     */
    public function __construct(controller $controller = null, xml_node $node = null) {
        $this->controller = $controller;
        $this->xmlnode = $node;
    }

    /**
     * Load the processor for this message.
     */
    protected function load_processor() {
        // Get the processor (cached).
        $this->processor = processors\types::get_type_processor($this->xmlnode->get_name());
        $this->processor->set_message($this);

        // Get a new response object for this message.
        $this->response = $this->processor->get_response_object();
        $this->response->set_controller($this->controller);
        $this->response->set_message($this);

    }

    /**
     * Run the data processing on this message.
     */
    public function process() {
        $this->process_to_data();
        $this->process_to_moodle();
    }

    /**
     * Process the xml node into data objects.
     */
    protected function process_to_data() {
        $this->load_processor();

        // TODO - check setting to skip certain message types.

        try {
            // Convert the node to a data object.
            $objs = $this->processor->process_xml_to_data($this->xmlnode);

            if (!is_array($objs)) {
                // Convert single object to array for later.
                $objs = array($objs);
            }

            // Don't save to the database in some cases.
            $nodb = false;
            if (!empty($this->controller) && !empty($this->controller->get_option('nodb'))) {
                $nodb = true;
            }

            // Some nodes (like membership) may return many children.
            foreach ($objs as $obj) {
                if (empty($obj)) {
                    continue;
                }

                $obj->log_id();
                if (!$nodb) {
                    $obj->save_to_db();
                }
                $this->dataobjs[] = $obj;
            }
        } catch (\Exception $e) {
            // There as a fatal exeption for this node.
            $status = $this->processor->get_failure_status();
            $status->set_description('Exception while loading and saving input.');
            $this->set_status($status);
            logging::instance()->log_line($e->getMessage(), logging::ERROR_MAJOR);
            logging::instance()->log_line($e->getTraceAsString(), logging::ERROR_MAJOR);
        }
    }

    /**
     * Process this messages data objects into Moodle object.
     */
    protected function process_to_moodle() {
        try {
            foreach ($this->dataobjs as $dataobj) {
                $converter = $dataobj->get_moodle_converter();
                if (!empty($converter)) {
                    $converter->convert_to_moodle($dataobj);
                }
            }
        } catch (\Exception $e) {
            // There as a fatal exeption for this node.
            $status = $this->processor->get_failure_status();
            $status->set_description('Exception while converting data to Moodle objects.');
            $this->set_status($status);
            logging::instance()->log_line($e->getMessage(), logging::ERROR_MAJOR);
            logging::instance()->log_line($e->getTraceAsString(), logging::ERROR_MAJOR);
        }
    }

    /**
     * Add a status object to this message.
     *
     * @param status\base $status The status to use.
     */
    public function set_status(status\base $status) {
        $this->status = $status;
    }

    /**
     * Return the current status object for this message.
     *
     * @return status\base|null
     */
    public function get_status() {
        if (empty($this->status) && !empty($this->processor)) {
            return $this->processor->get_success_status();
        }

        return $this->status;
    }

    /**
     * Get the root tag for the message.
     *
     * @return string|false
     */
    public function get_root_tag() {
        if (empty($this->xmlnode)) {
            return false;
        }

        return $this->xmlnode->get_name();
    }

    /**
     * Return the message's response object.
     *
     * @return local\response\base
     */
    public function get_response() {
        return $this->response;
    }

}
