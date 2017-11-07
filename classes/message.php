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

use \enrol_lmb\local\exception;
use \enrol_lmb\local\processors;
use \enrol_lmb\local\xml_node;

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

    /** @var controller controller object */
    protected $controller = null;

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

        // Get a new response object for this message.
        $this->response = $this->processor->get_response_object();

    }

    /**
     * Process the xml node into data objects.
     */
    public function process_to_data() {
        $this->load_processor();

        // TODO - check setting to skip certain message types.

        try {
            // Convert the node to a data object.
            $objs = $this->processor->process_xml_to_data($this->xmlnode);

            if (!is_array($objs)) {
                // Convert single object to array for later.
                $objs = array($objs);
            }

            // Some nodes (like membership) may return many children.
            $nodb = false;
            if (!empty($this->controller) && !empty($this->controller->get_option('nodb'))) {
                $nodb = true;
            }
            foreach ($objs as $obj) {
                $obj->log_id();
                if (!$nodb) {
                    $obj->save_to_db();
                }
            }

            $this->dataobjs = $objs;
        } catch (exception\message_exception $e) {
            // There as a fatal exeption for this node.
            logging::instance()->log_line($e->getMessage(), logging::ERROR_MAJOR);
        }
    }

    public function process_to_moodle() {
        $converter = $this->processor->get_moodle_converter();
        foreach ($this->dataobjs as $dataobj) {
            $converter->convert_to_moodle($dataobj);
        }
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
