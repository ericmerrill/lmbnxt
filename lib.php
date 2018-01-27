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
 * An activity to interface with WebEx.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('enrollib.php');

class enrol_lmb_plugin extends enrol_plugin {
    // Base class overrides.
    public function can_add_instance($courseid) {
        return false;
    }

    public function can_edit_instance($instance) {
        return false;
    }

    // TODO - what to do with this?
    public function get_newinstance_link($courseid) {
        return NULL;
    }

    public function can_hide_show_instance($instance) {
        return false;
    }

    public function get_unenrolself_link($instance) {
        return NULL;
    }

    // TODO - Need to do things to impliment expirations I think...
}
