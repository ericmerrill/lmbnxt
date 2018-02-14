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
use enrol_lmb\local\data\term;
use enrol_lmb\local\moodle;

/**
 * Class for working with course section message types.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2017 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends base {
    use trait_timeframe;

    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/cmsv1p0/wsdl11/sync/imscms_v1p0";

    /**
     * The data object path for this object.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\section';

    /**
     * Path to this objects mappings.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/lis2/mappings/section.json';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    protected function post_mappings() {
        parent::post_mappings();

        // See if we can extract the CRN from the SourcedID.
        $sdid = $this->dataobj->sdid;
        $term = $this->dataobj->termsdid;

        if (!empty($sdid) && !empty($term)) {
            // Only do this with this specfic format...
            if (preg_match('|^(\d{5}).'.$term.'$|', $sdid, $matches)) {
                $this->dataobj->crn = $matches[1];
            }
        }

        // See if the rubric has the term prepended to it and remove it.
        $rubric = $this->dataobj->rubric;
        if (!empty($rubric) && !empty($term)) {
            // Only do this with this specfic format...
            if (preg_match('|^'.$term.' (.*)$|', $rubric, $matches)) {
                $this->dataobj->rubric = $matches[1];
            }
        }

        // Now see if the term name is added to the title, and remove if so.
        $title = $this->dataobj->title;
        if (!empty($title) && !empty($term)) {
            if ($termobj = term::get_term($term)) {
                if (preg_match('|^'.$termobj->description.' - (.*)$|', $title, $matches)) {
                    $this->dataobj->title = $matches[1];
                }
            }
        }

        // And remove the rubric from the title.
        $title = $this->dataobj->title;
        $rubric = $this->dataobj->rubric;
        if (!empty($title) && !empty($rubric)) {
            if (preg_match('|^(.*) \('.$rubric.'\)$|', $title, $matches)) {
                $this->dataobj->title = $matches[1];
            }
        }

        if (empty($this->dataobj->deptsdid) && !empty($this->dataobj->deptunit)) {
            // Some course messages seem to be missing the normal dept values, so we can get it from here.
            $this->dataobj->deptsdid = $this->dataobj->deptunit;
        }

        $rubric = $this->dataobj->rubric;
        if (!empty($rubric)) {
            if (preg_match('|^([a-zA-Z\d]*)-([a-zA-Z\d]*)-([a-zA-Z\d]*)$|', $rubric, $matches)) {
                $this->dataobj->rubricdept = $matches[1];
                $this->dataobj->coursenumber = $matches[2];
                $this->dataobj->sectionnumber = $matches[3];
            }
        }
    }

}
