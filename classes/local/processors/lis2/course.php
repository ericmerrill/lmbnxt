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

namespace enrol_lmb\local\processors\lis2;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\xml\trait_timeframe;

/**
 * Class for working with message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends base {
    use trait_timeframe;

    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/cmsv1p0/wsdl11/sync/imscms_v1p0";

    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\course';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/lis2/mappings/course.json';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

}
