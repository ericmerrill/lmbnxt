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
 * A course unhide task for enrol_lmb.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb\task;

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\data;
use enrol_lmb\logging;
use enrol_lmb\settings;

/**
 * A task for the auto-unhiding of courses.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unhide_courses extends \core\task\scheduled_task {
    /**
     * Return the task name.
     *
     * @return string    Name of task
     */
    public function get_name() {
        return get_string('task_unhidecourses', 'enrol_lmb');
    }

    /**
     * Do the task.
     */
    public function execute() {
        global $CFG, $DB;

        $settings = settings::get_settings();

        if (empty($settings->get('cronunhidecourses'))) {
            return;
        }

        // Get the current date-time.
        $date = new \DateTime();
        // I set it to noon so that any time change in the addition below won't cause a date change.
        $date->setTime(12, 0, 0);
        // Add the number of days into the future we are working with.
        $date->add(\DateInterval::createFromDateString($settings->get('cronunhidedays').' day'));
        // Set it to the end of the day, so we unhide courses that start anytime during the day.
        $date->setTime(23, 59, 59);
        $endtime = $date->getTimestamp();

        $starttime = $settings->get('prevunhideendtime');
        if (empty($starttime)) {
            $starttime = $endtime - (3 * DAYSECS);
        }

        logging::instance()->log_line('Cron unhiding course');

        // SQL that only updates courses that are from LMB.
        $sql = 'UPDATE {course}
                   SET visible=1,
                       visibleold=1
                 WHERE visible=0
                   AND (idnumber IN (SELECT sdid FROM {'.data\section::TABLE.'})
                        OR
                        idnumber IN (SELECT sdid FROM {'.data\crosslist::TABLE.'}))
                   AND startdate > :start
                   AND startdate <= :end';

        $params = ['start' => $starttime, 'end' => $endtime];

        $settings->set('prevunhideendtime', $endtime);

        $DB->execute($sql, $params);
    }
}
