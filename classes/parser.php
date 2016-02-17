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
    protected $controller;

    public function __construct($case_folding = true) {

        parent::__construct($case_folding);
    }

    public function process_file($path) {
        if (!is_readable($path)) {
            debugging("XML path $path not readable.", DEBUG_DEVELOPER);
            return false;
        }

        $this->set_file($path);

        $processor = new parse_processor($this->controller);
        $this->set_processor($processor);
        // TODO $parser->set_progress($progress).
        $this->process();
    }

    public function process_string($string) {
        $this->set_contents($string);

        $processor = new parse_processor($this->controller);
        $this->set_processor($processor);
        // TODO $parser->set_progress($progress).
        $this->process();
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
}
