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

require_once($CFG->dirroot.'/backup/util/xml/parser/processors/progressive_parser_processor.class.php');

/**
 * Processes XML chunks from the parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class parse_processor extends \progressive_parser_processor {
    /** @var xml_node The current node we are working on */
    protected $currentnode = null;

    /** @var xml_node The root node we are building */
    protected $rootnode = null;

    /** @var bool|string The currently active node path */
    protected $currentrootpath = false;

    /** @var xml_node The most recently completed node, basically for testing */
    protected $previousnode = null;

    /** @var xml_node The most recently completed node, that was a header */
    protected $previousheadernode = array();

    /** @var controller A reference to the controller object */
    protected $controller;

    /** @var array The registered paths */
    protected $paths;

    /**
     * Basic constructor.
     */
    public function __construct($controller) {
        parent::__construct();

        $this->paths = array();

        $this->controller = $controller;
    }

    /**
     * Register a path for grouping.
     *
     * @param string $path The path to register
     */
    public function register_path($path) {
        // We register both the path that the path in /enterprise/, as it is a common wrapper.
        // We also need to use lmb as a master wrapper, in case there are multiple parts in the incoming.
        $this->add_path(strtoupper('/lmb'.$path));
        $this->add_path(strtoupper('/lmb/enterprise'.$path));
        $this->add_path(strtoupper($path));
        $this->add_path(strtoupper('/enterprise'.$path));
        $this->add_path(strtoupper('/SOAPENV:ENVELOPE/SOAPENV:BODY'.$path));
        $this->add_path(strtoupper('/SOAPENV:ENVELOPE/SOAPENV:HEADER'.$path));
        $this->add_path(strtoupper('/ENVELOPE/BODY'.$path));
        $this->add_path(strtoupper('/ENVELOPE/HEADER'.$path));
    }

    /**
     * Add the start of a new tag to the xml tree.
     *
     * @param string $tag The tag name
     * @param string $path The path to register
     * @param array $attributes Attributes that were associated with the start.
     */
    public function start_tag($tag, $path, $attributes = array()) {
        if ($this->currentrootpath !== false) {
            // This means we must be in a selected path. Make a new child.
            $child = new local\xml_node($tag);
            $child->set_attributes($attributes);
            $this->currentnode->add_child($child);
            $this->currentnode = $child;
            return;
        }

        if ($this->path_is_selected($path)) {
            // If we are starting a new group node, start a new root XML node.
            $this->rootnode = new local\xml_node($tag);
            $this->rootnode->set_attributes($attributes);
            $this->currentrootpath = $path;
            $this->currentnode = $this->rootnode;

            // If HEADER is in the path of a root node, then mark it as a header node.
            if (strpos($path, 'HEADER') !== false) {
                $this->rootnode->set_is_header(1);
            }

            logging::instance()->start_message("Processing {$this->currentnode->get_name()} message");
            return;
        }
    }

    /**
     * Complete the current node.
     *
     * @param string $tag The tag name
     * @param string $path The path to register
     * @param array $attributes Attributes that were associated with the start.
     */
    public function end_tag($tag, $path) {
        if ($this->currentrootpath === false) {
            return;
        }

        if ($path === $this->currentrootpath) {
            // Reached the end of the selected node.
            $this->process_complete_node($this->rootnode);
            logging::instance()->end_message();
            $this->currentnode = null;
            $this->rootnode = null;
            $this->currentrootpath = false;
            return;
        }

        // Move the current node pointer "up" a level.
        $this->currentnode = $this->currentnode->get_parent();
    }

    /**
     * Adds the passed data to the current node.
     *
     * @param string $data The data
     */
    public function add_data($data) {
        if (!is_null($this->currentnode)) {
            $trimmed = trim($data);
            if ($trimmed !== '' || !$this->currentnode->has_children()) {
                $this->currentnode->set_data($trimmed);
            }
        }
    }

    /**
     * Takes a chunk of parsed XML and processes it.
     *
     * @param array $data Data array from XML parser
     */
    public function process_chunk($data) {
        // Nothing to do. Required for abstract.
    }

    /**
     * Dispatches a completed node.
     *
     * @param xml_node $data The data
     */
    protected function process_complete_node(local\xml_node $node) {
        $this->previousnode = $node;
        if ($node->is_header()) {
            $this->previousheadernode = $node;
        }

        // Dispatch a completed node.
        if ($this->controller) {
            if ($node->is_header()) {
                $this->controller->process_header_node($node);
            } else {
                $this->controller->process_xml_object($node);
            }
        }
    }

    /**
     * Returns the last completed XML node that was a header.
     *
     * @return xml_node
     */
    public function get_previous_header_node() {
        return $this->previousheadernode;
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
     * Register paths.
     *
     * @param string $path Path to register.
     */
    public function add_path($path) {
        // We register with path keys because it allows faster lookup.
        $this->paths[$path] = $path;
    }

    /**
     * Lookup if the path is a registered path.
     *
     * @param string $path Path to lookup.
     */
    public function path_is_selected($path) {
        return isset($this->paths[$path]);
    }
}
