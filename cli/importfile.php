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

list($options, $unrecognized) = cli_get_params(array('force' => false,
                                                     'filepath' => null,
                                                     'silent' => false,
                                                     'help' => false,
                                                     'log-level' => false,
                                                     'no-db' => false),
                                               array('f' => 'filepath', 's' => 'silent'));

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


$silent = (bool)$options['silent'];
$force = (bool)$options['force'];
$filepath = $options['filepath'];

if (!empty($filepath)) {
    if (stripos($filepath, '/') !== 0) {
        if (!empty($_SERVER['PWD'])) {
            $filepath = $_SERVER['PWD'].'/'.$filepath;
        }
    }

    if (!file_exists($filepath) || !is_readable($filepath)) {
        mtrace("Source file {$filepath} is not readable. Try an absolute path.");
        die;
    }
}

// Set a logging level.
if ($options['log-level'] !== false && is_numeric($options['log-level'])) {
    $logger = \enrol_lmb\logging::instance();
    $logger->set_logging_level($options['log-level']);
}

$starttime = microtime(true);
$controller = new \enrol_lmb\controller();

if (!empty($options['no-db'])) {
    $controller->set_option('nodb', true);
}

$controller->import_file($filepath);
$endtime = microtime(true);

mtrace(round($endtime - $starttime, 3).'s');
