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
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\local\processors\lis2;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\data;

/**
 * Class for working with message calling for the deletion of a section.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2019 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_delete extends base {
    /**
     * Namespace associated with this object.
     */
    const NAMESPACE_DEF = "www.imsglobal.org/services/lis/cmsv1p0/wsdl11/sync/imscms_v1p0";

    /**
     * Path to the mapping file.
     */
    const MAPPING_PATH = '/enrol/lmb/classes/local/processors/lis2/mappings/section_delete.json';

    /**
     * Data class used by this type.
     */
    const DATA_CLASS = '\\enrol_lmb\\local\\data\\section';

    /**
     * Basic constructor.
     */
    public function __construct() {
        $this->load_mappings();
    }

    protected function post_mappings() {
        global $DB;

        parent::post_mappings();

        // Without a sdid, there isn't anything we can do.
        if (empty($this->dataobj->sdid)) {
            $this->dataobj = false;
            return;
        }

        $sdid = $this->dataobj->sdid;

        // Now we are going to try and find an existing.
        $record = $DB->get_record(data\section::TABLE, ['sdid' => $sdid]);

        if ($record) {
            $this->dataobj->load_from_record($record);
        } else {
            // If we couldn't find the record, there is nothing else to do.
            $this->dataobj = false;
            return;
        }

        // Now, set the information we know based on this information.
        $this->dataobj->status = 0;
        $this->dataobj->lis_status = 'Deleted';

        return;
    }
}
