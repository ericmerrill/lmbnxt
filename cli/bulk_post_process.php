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

list($options, $unrecognized) = cli_get_params(array('starttime' => false,
                                                     'endtime' => false,
                                                     'termid' => false,
                                                     'source' => false,
                                                     'process' => false,
                                                     'coursetimes' => false,
                                                     'help' => false),
                                               array('s' => 'starttime',
                                                     'e' => 'endtime',
                                                     't' => 'termid',
                                                     'h' => 'help'));

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
-e, --endtime           Optional. The time the bulk job ended. Help limit scope of some cleanup tasks.
                        Accepts a timestamp or anything strtotime accepts.
-t, --termid            The term sourcedid to process
--source                Limit processing to records with the given sdid source.
--process               If set, then drops will be processed, rather than just
                        an informational display.
--coursetimes           Adjust course start and end times based on quirk settings to compensate for ILP bugs.
-h, --help              Print out this help



Example:
\$sudo -u www-data /usr/bin/php enrol/lmb/cli/bulk_post_process.php -s=\"-2 hours\"
";

    echo $help;
    die;
}

$starttime = $options['starttime'];
if (!is_numeric($starttime)) {
    $starttime = strtotime($starttime);
}

$endtime = $options['endtime'];
if (!is_numeric($endtime)) {
    $endtime = strtotime($endtime);
}

$timediff = time() - $starttime;
if ($timediff > YEARSECS) {
    mtrace("Selected starttime is more than a year in the past. Aborting.");
    exit(1);
}

if (is_numeric($starttime) && is_numeric($endtime) && ($starttime >= $endtime)) {
    mtrace("Endtime is before starttime. Aborting.");
    exit(1);
}

$termid = $options['termid'];
$source = $options['source'];
$process = $options['process'];
$coursetimes = $options['coursetimes'];

if ($process && empty($termid)) {
    mtrace("The process option requires a termid to be set. Aborting.");
    exit(1);
}

if ($coursetimes && empty($termid)) {
    mtrace("The coursetimes option requires a termid to be set. Aborting.");
    exit(1);
}

mtrace("Running with a start time of ".userdate($starttime).".");

$util = new bulk_util();

$info = $util->get_terms_in_timeframe($starttime, $endtime, $source);

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
    $util->drop_old_term_enrols($termid, $starttime, $source);
}

if ($coursetimes && $termid) {
    $util->adjust_term_section_dates($termid, $starttime, $endtime, $source);
}





function render_term($termid, $info) {
    mtrace($termid);
    mtrace(print_r($info, true));
}
