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
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\types;

/**
 * Controller class for importing files and folders.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    /** @var array An array of existing type processors for reuse */
//    protected $typeprocessors = array();

    /** @var array Options for this run */
    protected $options = array();

    /** @var local\xml_node Most recent header node seen */
    protected $currentheader = false;

    /** @var local\response\base Most response seen */
    protected $lastresponse = false;

    public function import_file($path = null) {
        if (!$path) {
            $path = get_config('enrol_lmb', 'xmlpath');
        }

        $parser = new parser();
        $parser->set_controller($this);
        $parser->process_file($path);
    }

    // Takes a data object from an input source and does things to it.
//     public function process_data(local\types\base\data $data) {
//
//     }

    /**
     * Process a web-service type call.
     */
    public function process_ws_call() {
        // Silence standard outputs from logging.
        logging::instance()->set_silence_std_out();

        header('Content-type: text/xml;charset="utf-8"');

        // TODO - Authentication, etc.
        $xml = file_get_contents('php://input');

        if (settings::get_settings()->get('logwsmessages')) {
            logging::instance()->log_line($xml);
        }

        if (empty($xml)) {
            // TODO - maybe a XML fatal response? Check spec.

            logging::instance()->log_line("No input received", logging::ERROR_MAJOR);
            header("HTTP/1.0 400 Bad Request");
            header("Status: 400 Bad Request");
        }

        $response = $this->process_xml_message($xml, true);

        if (!empty($response)) {
            echo $response;
        } else {
            // TODO - some response/headers/errors.
        }
    }

    /**
     * Process a chunk of XML, and optionally give an appropriate response.
     *
     * @param string $message
     * @param bool   $wsresponse If true, we want a webservice response in string form.
     * @return null|string
     */
    public function process_xml_message($message, $wsresponse = false) {
        $parser = new parser();
        $parser->set_controller($this);
        $parser->process_string($message);

        if ($wsresponse) {
            $response = false;

            if (!empty($this->lastresponse)) {
                $response = $this->lastresponse->get_response_body();
            }

            if (empty($response)) {
                // TODO - Error.
                //logging::instance()->log_line("No webservice response found.", logging::ERROR_MAJOR);
                return 'Temp';
            }

            return $response;

        }

        return;
    }

    /**
     * Set a controller option.
     *
     * Valid keys:
     *     nodb - If true, disabled saving to db.
     *
     * @param string $key The option key
     * @param string $value The option value
     */
    public function set_option($key, $value) {
        $this->options[$key] = $value;
    }

    /**
     * Return a controller option.
     *
     * Valid keys:
     *     nodb - If true, disabled saving to db.
     *
     * @param string $key The option key
     * @return mixed
     */
    public function get_option($key) {
        if (!isset($this->options[$key])) {
            return null;
        }

        return $this->options[$key];
    }

    /**
     * Get the node of the most recent header we have seen.
     *
     * @return local\xml_node
     */
    public function get_current_header() {
        return $this->currentheader;
    }

    /**
     * We specially treat header nodes.
     *
     * @param xml_node $xmlobj The XML node to work on.
     */
    public function process_header_node(local\xml_node $xmlobj) {
        // Just saving this for later use.
        $this->currentheader = $xmlobj;
    }

    /**
     * Takes a built XML node and processes it.
     *
     * @param xml_node $xmlobj The XML node to work on.
     */
    public function process_xml_object(local\xml_node $xmlobj) {

        $message = new message($this, $xmlobj);

        $message->process();

        $this->lastresponse = $message->get_response();


        // TODO get response.

//
//         // Get the processor (cached).
//         $xmlproc = types::get_type_processor($xmlobj->get_name());
//
//         try {
//             // Convert the node to a data object.
//             $objs = $xmlproc->process_xml_to_data($xmlobj);
//
//             if (!is_array($objs)) {
//                 // Convert single object to array for later.
//                 $objs = array($objs);
//             }
//
//             // Some nodes (like membership) may return many children.
//             foreach ($objs as $obj) {
//                 $obj->log_id();
//                 if (empty($this->options['nodb'])) {
//                     $obj->save_to_db();
//                 }
//             }
//         } catch (\enrol_lmb\local\exception\message_exception $e) {
//             // There as a fatal exeption for this node.
//             logging::instance()->log_line($e->getMessage(), logging::ERROR_MAJOR);
//         }

    }
}
