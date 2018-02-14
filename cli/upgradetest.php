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
 * Imports a XML file into Moodle.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2016 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/enrol/lmb/upgradelib.php');

list($options, $unrecognized) = cli_get_params(array('up' => false, 'help' => false),
                                               array('u' => 'up'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "LMB file import CLI tool.
Process a file using the LMB plugin.

Options:
-f, --filepath          Path to the file to process. Use config setting if not specified.
--force                 Skip file modification time checks.
-h, --help              Print out this help
-s, --silent            Don't print logging output to stdout.
--log-level             Sets the logging level:
                            0 = All
                            1 = Notice and above
                            2 = Warning and above
                            3 = Fatal and above

For testing:
--no-db                 Disable saving to the database.


Example:
\$sudo -u www-data /usr/bin/php enrol/lmb/cli/fileprocess.php
";

    echo $help;
    die;
}

switch ($options['up']) {
    case ('1'):
        enrol_lmb_upgrade_migrate_old_enrols();
        break;
    case ('2'):
        enrol_lmb_upgrade_migrate_old_crosslists();
        break;
    case ('3'):
        enrol_lmb_upgrade_migrate_old_terms();
        break;
}
//enrol_lmb_upgrade_migrate_old_enrols();
//enrol_lmb_upgrade_migrate_old_crosslists();
