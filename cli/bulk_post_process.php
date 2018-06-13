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
 * Post processing after a bulk job.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \enrol_lmb\bulk_util;

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/enrol/lmb/upgradelib.php');

list($options, $unrecognized) = cli_get_params(array('starttime' => false, 'termid' => false, 'process' => false, 'help' => false),
                                               array('s' => 'starttime', 't' => 'termid'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['starttime'])) {
    $help = "Script for post processing after a bulk import.
Reports on, and removes, excess enrollments.

Options:
-s, --starttime         Required. Time the bulk job started.
                        Accepts a timestamp or anything strtotime accepts.
-t, --termid            The term sourcedid to process
--process               If set, then drops will be processed, rather than just
                        an informational display.
-h, --help              Print out this help



Example:
\$sudo -u www-data /usr/bin/php enrol/lmb/cli/bulk_post_process.php -s=\"-2 hours\"
";

    echo $help;
    die;
}

$time = $options['starttime'];
if (!is_numeric($time)) {
    $time = strtotime($time);
}

$timediff = time() - $time;
if ($timediff > YEARSECS) {
    mtrace("Selected starttime is more than a year in the past. Aborting.");
    exit(1);
}

$termid = $options['termid'];
$process = $options['process'];

if ($process && empty($termid)) {
    mtrace("The process option requires a termid to be set. Aborting.");
    exit(1);
}

mtrace("Running with a start time of ".userdate($time).".");

$util = new bulk_util();

$info = $util->get_terms_in_timeframe($time);

if (empty($termid)) {
    foreach ($info as $termkey => $term) {
        render_term($termkey, $term);
    }
} else {
    if (isset($info[$termid])) {
        $term = $info[$termid];
        render_term($termid, $term);
    } else {
        mtrace("Term not found. Aborting.");
    }
}

if ($process && $termid) {
    $util->get_term_enrols_to_drop_count($termid, $time);
}






function render_term($termid, $info) {
    mtrace($termid);
    mtrace(print_r($info, true));
}
