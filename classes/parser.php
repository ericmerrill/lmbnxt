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
 * Primary XML parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/backup/util/xml/parser/progressive_parser.class.php');
/**
 * Class for parsing a XML file.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class parser extends \progressive_parser {
    /** @var controller A reference to the controller object */
    protected $controller;

    /** @var array An array of registered types */
    protected $types = array();

    /** @var bool True if we are on a system using blackslash paths */
    protected $backslashpaths = false;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(true);

        // Check if this system is using backslash paths.
        $filepath = dirname(__FILE__);
        if (strpos('\\', $filepath) !== false) {
            $this->backslashpaths = true;
        }

        $this->load_types();
    }

    /**
     * Loads all of the standard types.
     */
    protected function load_types() {
        $types = local\processors\types::get_types();
        foreach ($types as $type) {
            $this->add_type($type);
        }
    }

    /**
     * Add a type to the parser.
     *
     * @param string $type The type to add
     */
    public function add_type($type) {
        $this->types[] = $type;
    }

    /**
     * Creates a processor object to use with the parser.
     *
     * @return parse_processor The object to use
     */
    protected function create_processor() {
        $processor = new parse_processor($this->controller);

        // Now register each type as a path.
        foreach ($this->types as $type) {
            $processor->register_path('/'.$type);
        }

        return $processor;
    }

    /**
     * Processes an XML file.
     *
     * @param string $path The path to an XML file
     * @return bool Success or failure
     */
    public function process_file($path) {
        if (!is_readable($path)) {
            debugging("XML path $path not readable.", DEBUG_DEVELOPER);
            return false;
        }

        $this->set_file($path);

        $processor = $this->create_processor();
        $this->set_processor($processor);
        // TODO Use a progress object.
        $this->process();
        // TODO Exception catching.

        return true;
    }

    /**
     * Processes a string of XML.
     *
     * @param string $string The XML
     */
    public function process_string($string) {
        $this->set_contents($string);

        $processor = $this->create_processor();
        $this->set_processor($processor);
        // TODO Use a progress object.
        $this->process();
        // TODO Exception catching.
    }

    /**
     * Set the controller object.
     *
     * @param controller $controller Controller object
     */
    public function set_controller(controller $controller) {
        $this->controller = $controller;
    }

    /**
     * Returns the parse processor.
     *
     * @return parse_procssor
     */
    public function get_processor() {
        return $this->processor;
    }

    /**
     * Processes an incoming tag notification.
     *
     * @param object $parser Reference of the parser.
     * @param string $tag The opening tag name.
     * @param array $attributes The attributes associated with the open tag.
     */
    protected function start_tag($parser, $tag, $attributes) {
        // Increase our level and append the incoming tag.
        $this->level++;
        $this->path .= '/' . $tag;

        // Tell the processor we are starting a tag and pass the attributes.
        $this->processor->start_tag($tag, $this->path, $attributes);
    }

    /**
     * Called when a tag has ended. Contents are considered complete.
     *
     * @param object $parser Reference of the parser.
     * @param string $tag The opening tag name.
     * $param array $attributes The attributes associated with the open tag.
     */
    protected function end_tag($parser, $tag) {
        $this->processor->add_data($this->accum);
        $this->accum = '';
        $this->processor->end_tag($tag, $this->path);

        $this->level--;
        $this->path = $this->lmb_dirname($this->path);
    }

    /**
     * Get the parent path of the passed path.
     *
     * @param string $path The back to get the parent path of.
     */
    protected function lmb_dirname($path) {
        // Only do the string replace if this system uses backslashes.
        if ($this->backslashpaths) {
            // On Windows systems, paths are returned in backslash format. See MDL-24381.
            return str_replace('\\', '/', dirname($path));
        } else {
            return dirname($path);
        }
    }

    /**
     * Process the XML, delegating found chunks to the @progressive_parser_processor.
     */
    public function process() {
        if (empty($this->processor)) {
            throw new \progressive_parser_exception('undefined_parser_processor');
        }
        if (empty($this->file) && empty($this->contents)) {
            throw new \progressive_parser_exception('undefined_xml_to_parse');
        }
        if (is_null($this->xml_parser)) {
            throw new \progressive_parser_exception('progressive_parser_already_used');
        }

        if ($this->file) {
            // If we are opening a file.
            $fh = fopen($this->file, 'r');

            $first = fread($fh, 1024);
            $wellformed = false;
            if (preg_match('|<\\?xml|i', $first) || preg_match('|<!DOCTYPE|i', $first)) {
                // If it starts with XML or DOCTYPE, we are going to assume the doc is well formed.
                $wellformed = true;
            } else {
                // We need to wrap tags around the imcoming file, incase of multiple part messages.
                $first = '<lmb>'.$first;
            }

            // Process the first chunk.
            $this->parse($first, false);

            while ($buffer = fread($fh, 8192)) {
                // Process the file, one 8k chunk at a time.
                $this->parse($buffer, false);
            }

            if (!$wellformed) {
                // If we prepended an open tag, appand the final tag.
                $this->parse('</lmb>', false);
            }

            // Close the processing of the file.
            $this->parse('', true);
            fclose($fh);
        } else {
            if (preg_match('|<\\?xml|i', $this->contents) || preg_match('|<!DOCTYPE|i', $this->contents)) {
                // If it starts with XML or DOCTYPE, we are going to assume the doc is well formed.
                $this->parse($this->contents, true);
            } else {
                // We need to wrap tags around the imcoming file, incase of multiple part messages.
                $this->parse('<lmb>'.$this->contents.'</lmb>', true);
            }

        }

        // Clear the parser.
        xml_parser_free($this->xml_parser);
        $this->xml_parser = null;
    }
}
