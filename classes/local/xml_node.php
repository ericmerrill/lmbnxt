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

namespace enrol_lmb\local;
defined('MOODLE_INTERNAL') || die();

class xml_node implements \IteratorAggregate {
    private $par = null;
    public $name = null;
    private $children = array();
    public $attrs = array();
    public $value;
    public $finished = false;

    public function __construct($name = null) {
        $this->name = $name;
    }

    public function getIterator() {
        return new ArrayIterator($this->children);
    }

    public function build_from_array($data) {
        if (isset($data['name']) && !is_array($data['name']) && (array_key_exists('cdata', $data) || isset($data['attrs']))) {
            unset($data['name']);
            if (array_key_exists('cdata', $data)) {
                $this->value = $data['cdata'];
                unset($data['cdata']);
            }
            if (array_key_exists('attrs', $data)) {
                $this->attrs = $data['attrs'];
                unset($data['attrs']);
            }
        }

        foreach ($data as $name => $value) {
            if (array_key_exists($name, $this->children)) {
                $this->children[$name]->build_from_array($value);
            } else {
                $child = new xml_node($name);
                $child->build_from_array($value);
                $this->add_child($child);
            }

        }
    }

    public function mark_node_finished($patharray) {
    //print "<pre>";var_dump($patharray);print "</pre>";
        if (is_array($patharray) && empty($patharray)) {
            $this->finished = true;
            echo "finished";
            return;
        }
        $child = array_shift($patharray);
        if (!array_key_exists($child, $this->children)) {
        //print $child;
        //print "<pre>";print_r($this->children);print "</pre>";
            return;
        }

        if (is_array($this->children[$child])) {
            $last = end($this->children[$child]);
            $last->mark_node_finished($patharray);
            reset($this->children);
        } else if (is_object($this->children[$child])) {
            $this->children[$child]->mark_node_finished($patharray);
        }
    }

    public function add_child($child) {
        $name = $child->name;
        $child->set_parent($this);
        if (array_key_exists($name, $this->children)) {
            if (!is_array($this->children[$name])) {
                $this->children[$name] = array($this->children[$name]);
            }
            $this->children[$name][] = $child;
        } else {
            $this->children[$name] = $child;
        }

    }

    public function set_parent($parent) {
        $this->par = $parent;
    }
}
