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

    /**
     * Constructor.
     *
     * @param bool $case_folding If true, all tags and attributes converted to upper-case
     */
    public function __construct($case_folding = true) {
        parent::__construct($case_folding);

        $this->load_types();
    }

    /**
     * Loads all of the standard types.
     */
    protected function load_types() {
        $types = local\types::get_types();
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

    // Inherited from progressive_parser.
    protected function end_tag($parser, $tag) {
        // Ending rencently started tag, add value to current tag
        if ($this->level == $this->prevlevel) {
            $this->currtag['cdata'] = $this->postprocess_cdata($this->accum);
            if (isset($this->topush['tags'][$this->currtag['name']])) {
                $this->publish($this->topush);
                $this->topush['tags'] = array();
            }
            $this->topush['tags'][$this->currtag['name']] = $this->currtag;
            $this->currtag = array();
        }

        // Leaving one level, publish all the information available
        if ($this->level < $this->prevlevel) {
            if (!empty($this->topush['tags'])) {
                $this->publish($this->topush);
            }
            $this->currtag = array();
            $this->topush = array();
        }

        // For the records
        $this->prevlevel = $this->level;

        // Inform processor we have finished one tag
        $this->inform_end($this->path);

        // Normal update of parser internals
        $this->level--;
        $this->path = \progressive_parser::dirname($this->path);
    }


    /*
     * Process the XML, delegating found chunks to the @progressive_parser_processor
     */
    public function process() {
        if (empty($this->processor)) {
            throw new progressive_parser_exception('undefined_parser_processor');
        }
        if (empty($this->file) && empty($this->contents)) {
            throw new progressive_parser_exception('undefined_xml_to_parse');
        }
        if (is_null($this->xml_parser)) {
            throw new progressive_parser_exception('progressive_parser_already_used');
        }
        if ($this->file) {
            $fh = fopen($this->file, 'r');
            // We need to wrap tags around the imcoming file, incase of multiple part messages.

            $first = fread($fh, 1024);
            $wellformed = false;
            if (preg_match('|<\\?xml|i', $first) || preg_match('|<!DOCTYPE|i', $first)) {
                $wellformed = true;
            } else {
                $first = '<lmb>'.$first;
            }


            $this->parse($first, false);
            while ($buffer = fread($fh, 8192)) {
                $this->parse($buffer, false);
            }
            if (!$wellformed) {
                $this->parse('</lmb>', false);
            }
            $this->parse('', true);
            fclose($fh);
        } else {
            $this->parse('<lmb>'.$this->contents.'</lmb>', true);
        }
        xml_parser_free($this->xml_parser);
        $this->xml_parser = null;
    }
}
