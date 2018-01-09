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
 * A class for collecting XML data.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local;
defined('MOODLE_INTERNAL') || die();

/**
 * Collects and builds a tree of XML data..
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xml_node implements \Iterator {
    /** @var xml_node Parent of this node */
    protected $parent = null;

    /** @var string Name (path element) of this node */
    protected $name = null;

    /** @var array Children of this node */
    protected $children = array();

    /** @var array Array of node attributes */
    protected $attrs = array();

    /** @var mixed Value of the node */
    protected $value;

    /** @var int An pointer key used for child arrays. */
    protected $arraykey = 0;

    /** @var int Track if this is a header root node or not. */
    protected $isheader = 0;

    /**
     * Basic constructor.
     *
     * @param string $name The path name of the current element
     */
    public function __construct($name = null) {
        $this->set_name($name);
    }

    /**
     * Set all attributes at once.
     *
     * @param array $attrs Array of all attributes.
     */
    public function set_attributes($attrs) {
        $this->attrs = $attrs;
    }

    /**
     * Set a attribute.
     *
     * @param string $name Name of attribute.
     * @param mixed $value Value of attribute.
     */
    public function set_attribute($name, $value) {
        $this->attrs[$name] = $value;
    }

    /**
     * Set the data of this node.
     *
     * @param mixed $data All the data.
     */
    public function set_data($data) {
        $this->value = $data;
    }

    /**
     * Adds a node as a child of this node.
     *
     * @param xml_node $child Child node
     */
    public function add_child($child) {
        $name = $child->name;
        // Set the childs parent.
        $child->set_parent($this);
        if (isset($this->children[$name])) {
            // If they item already exists, check to see if we need to make a new array.
            if (!is_array($this->children[$name])) {
                $this->children[$name] = array($this->children[$name]);
            }
            $this->children[$name][] = $child;
        } else {
            $this->children[$name] = $child;
        }

    }

    public function has_children() {
        return !empty($this->children);
    }

    /**
     * Sets this nodes parent.
     *
     * @param xml_node $parent Parent node
     */
    public function set_parent($parent) {
        $this->parent = $parent;
    }

    /**
     * Returns this nodes parent.
     *
     * @return xml_node
     */
    public function get_parent() {
        return $this->parent;
    }

    /**
     * Return this nodes attributes.
     *
     * @return array
     */
    public function get_attributes() {
        return $this->attrs;
    }

    /**
     * Returns a particular attribute, or null if it doesn't exist.
     *
     * @param string $attribute The name of the attribute
     * @return scalar|null
     */
    public function get_attribute($attribute) {
        if (!isset($this->attrs[$attribute])) {
            return null;
        }
        return $this->attrs[$attribute];
    }

    /**
     * Returns this nodes value.
     *
     * @return mixed
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Sets this node's name.
     *
     * @param string $name The name to set
     */
    public function set_name($name) {
        $this->name = $name;
    }

    /**
     * Returns this node's name.
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Returns true if this node has a value or attributes.
     *
     * @return bool
     */
    public function has_data() {
        return !empty($this->attrs) || !is_null($this->value);
    }

    /**
     * Returns true if this node is the root node of a header.
     *
     * @return bool
     */
    public function is_header() {
        return (bool)$this->isheader;
    }

    /**
     * Set that this is the root node of a header.
     *
     * @param int|bool $isheader
     */
    public function set_is_header($isheader) {
        $this->isheader = ($isheader) ? 1 : 0;
    }

    /**
     * ----------------------- Magic Methods -----------------------.
     */

    /**
     * Returns requested child or null.
     *
     * @param string $name
     * @return xml_node|array|null
     */
    public function __get($name) {
        if (!isset($this->children[$name])) {
            return null;
        }

        return $this->children[$name];
    }

    /**
     * Returns if the requested child exists.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        if (!isset($this->children[$name])) {
            return false;
        }

        return true;
    }

    /**
     * Unset a child.
     *
     * @param string $name
     */
    public function __unset($name) {
        unset($this->children[$name]);
    }

    /**
     * ----------------------- Iterator interface -----------------------.
     */

    public function current() {
        $current = current($this->children);
        if (is_array($current)) {
            return $current[$this->arraykey];
            return current($current);
        }

        return $current;
    }

    public function key() {
        return key($this->children);
    }

    public function next() {
        $current = current($this->children);
        if (is_array($current)) {
            $this->arraykey++;
            if (isset($current[$this->arraykey])) {
                return;
            }
            $this->arraykey = 0;
        }
        $next = next($this->children);

        if (is_array($next)) {
            $this->arraykey = 0;
        }
    }

    public function rewind() {
        reset($this->children);
        $this->arraykey = 0;
    }

    public function valid() {
        $current = current($this->children);
        if ($current === false) {
            return false;
        }

        return true;
    }
}
