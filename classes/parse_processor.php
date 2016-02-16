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
    protected $pathclasses = array();
    protected $merged = array();
    protected $typeprocessors = array();

    public function __construct() {
        parent::__construct();

        $this->load_paths();
    }

    protected function load_paths() {
        local\types::register_processor_paths($this);
    }

    public function register_path($type, $path, $grouped = false) {
        if ($grouped) {
            $this->pathclasses[$path] = $type;
            $this->pathclasses['/enterprise'.$path] = $type;
        }

        $this->add_path($path);
        $this->add_path('/enterprise'.$path);
    }

    /**
     * Returns the classpath type for a path.
     */
    protected function get_path_type($path) {
        if (isset($this->pathclasses[$path])) {
            return $this->pathclasses[$path];
        }

        return false;
    }

    /**
     * Gets the processor for the path. Creates if doesn't exist.
     */
    protected function get_path_processor($path) {
        if (!$type = $this->get_path_type($path)) {
            return false;
        }

        if (!isset($this->typeprocessors[$type])) {
            $class = '\\enrol_lmb\\local\\types\\'.$type.'\\xml';
            $this->typeprocessors[$type] = new $class();
        }

        return $this->typeprocessors[$type];
    }

    public function process_chunk($data) {
    print "<pre>P";print_r($data['path']);print "</pre>\n";
        if ($this->path_is_selected($data['path'])) {
            //$this->process_pending_startend_notifications($data['path'], 'start');
            if ($proc = $this->get_path_processor($data['path'])) {
                $proc->process_data($data);
            }
        } else if ($parent = $this->selected_parent_exists($data['path'])) {
            $this->expand_path($parent, $data);
            if ($proc = $this->get_path_processor($data['path'])) {
                $proc->process_data($data);
            }
        }

        // Do nothing.
    }

    /**
     * For selected paths, notifies the processor that a new object is starting.
     **/
    public function before_path($path) {
        if ($this->path_is_selected($path)) {
            if ($proc = $this->get_path_processor($path)) {
                $proc->start_object();
            }
        }
    }

    /**
     * For selected paths, notifies the processor that the current object has ended.
     */
    public function after_path($path) {
        print "<pre>E";print_r($path);print "</pre>\n";

        if ($this->path_is_selected($path)) {
            if ($proc = $this->get_path_processor($path)) {
                $proc->end_object();
            }
        } else if ($parent = $this->selected_parent_exists($path)) {
            if ($proc = $this->get_path_processor($parent)) {
                $path = str_replace($parent.'/', '', $path);
                $patharray = explode('/', $path);
                $proc->mark_path_finished($patharray);
            }
        }
    }

    protected function notify_path_start($path) {
        // Nothing to do. Required for abstract.
    }

    protected function notify_path_end($path) {
        // Nothing to do. Required for abstract.
    }

    protected function dispatch_chunk($path) {
    print "<pre>D";print_r($path['path']);print "</pre>\n";
        // Nothing to do. Required for abstract.
    }


    /**
     * Normalizes the data object to the passes path level.
     */
    protected function expand_path($grouped, &$data) {
        $path = $data['path'];

        $hierarchyarr = explode('/', str_replace($grouped . '/', '', $path));
        $hierarchyarr = array_reverse($hierarchyarr, false);
        foreach ($hierarchyarr as $element) {
            $data['level']--;
            $data['tags'] = array($element => $data['tags']);
        }
        $data['path'] = $grouped;
    }
}
