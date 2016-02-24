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
 * Processor for the XML parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/backup/util/xml/parser/processors/simplified_parser_processor.class.php');

/**
 * Processes XML chunks from the parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class parse_processor extends \simplified_parser_processor {
    /** @var array A cache of arrays that were finished */
    protected $finishedpaths = array();

    /** @var xml_node The current node we are working on */
    protected $currentnode = null;

    /** @var xml_node The most recently completed node, basically for testing */
    protected $previousnode = null;

    /** @var controller A reference to the controller object */
    protected $controller;

    /**
     * Basic constructor.
     */
    public function __construct($controller) {
        parent::__construct();

        $this->controller = $controller;
    }

    /**
     * Register a path for grouping.
     *
     * @param string $type The type the path will be registered to
     * @param string $path The path to register
     */
    public function register_path($path) {
        // We register both the path that the path in /enterprise/, as it is a common wrapper.
        // We also need to use lmb as a master wrapper, in case there are multiple parts in the incoming.
        $this->add_path(strtoupper('/lmb'.$path));
        $this->add_path(strtoupper('/lmb/enterprise'.$path));
        $this->add_path(strtoupper($path));
        $this->add_path(strtoupper('/enterprise'.$path));
    }

    /**
     * Takes a chunk of parsed XML and processes it.
     *
     * @param array $data Data array from XML parser
     */
    public function process_chunk($data) {
        $path = $data['path'];

        // If this is a child path, expand it to the parent level.
        if ($parent = $this->selected_parent_exists($path)) {
            $this->expand_path($parent, $data);
            $path = $parent;
        }

        // Check to see if it is one of our grouped paths.
        if ($this->path_is_selected($path)) {
            if (is_null($this->currentnode)) {
                debugging("XML object not started", DEBUG_DEVELOPER);
                return;
            }
            // Add the data to the current node.
            $this->currentnode->build_from_array($data['tags']);

            // Send all the currently buffered paths as finished.
            foreach ($this->finishedpaths as $fpath) {
                $fpath = str_replace($path.'/', '', $fpath);
                $patharray = explode('/', $fpath);
                $this->currentnode->mark_node_finished($patharray);
            }
            $this->finishedpaths = array();
        }

    }

    /**
     * Receives notification of an upcoming path.
     *
     * @param string $path Path that is going to happen.
     */
    public function before_path($path) {
        if ($this->path_is_selected($path)) {
            // If we are starting a new group node, start a new collector.
            $this->currentnode = new local\xml_node();
            $parts = explode('/', $path);
            $this->currentnode->set_name(end($parts));
            logging::instance()->start_message("Processing {$this->currentnode->get_name()} message");
        }
    }

    protected function process_complete_node($node) {
        $this->previousnode = $node;

        // Dispatch a completed node.
        if ($this->controller) {
            $this->controller->process_xml_object($node);
        }
    }

    /**
     * Returns the last completed XML node.
     *
     * @return xml_node
     */
    public function get_previous_node() {
        return $this->previousnode;
    }

    /**
     * Notifications after paths have been processed.
     *
     * @param string $path Path that happened.
     */
    public function after_path($path) {
        parent::after_path($path);

        if ($this->path_is_selected($path)) {
            // This is where our current node is complete, and can be dispatched.
            $this->process_complete_node($this->currentnode);
            logging::instance()->end_message();
            $this->currentnode = null;
        } else if ($this->selected_parent_exists($path)) {
            // Save the path for marking as finished. This has to be done after the upcoming chunk is processed.
            $this->finishedpaths[] = $path;
        }
    }

    protected function notify_path_start($path) {
        // Nothing to do. Required for abstract.
    }

    protected function notify_path_end($path) {
        // Nothing to do. Required for abstract.
    }

    protected function dispatch_chunk($path) {
        // Nothing to do. Required for abstract.
    }


    /**
     * Normalizes the data object to the passes path level.
     *
     * @param string $grouped The normalize path
     * @param array $data The base object
     */
    protected function expand_path($grouped, &$data) {
        $path = $data['path'];

        // Strip the matching parts of the array.
        $hierarchyarr = explode('/', str_replace($grouped . '/', '', $path));
        $hierarchyarr = array_reverse($hierarchyarr, false);

        foreach ($hierarchyarr as $element) {
            $data['level']--;
            $data['tags'] = array($element => $data['tags']);
        }
        $data['path'] = $grouped;
    }
}
