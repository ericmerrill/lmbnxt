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
 * A tool for working with dates.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_lmb;
defined('MOODLE_INTERNAL') || die();

use enrol_lmb\settings;

/**
 * A tool for working with dates.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class date_util {
    /**
     * Convert a string to a date the 'normal' way, with no tricks.
     *
     * @param string $value The datetime to convert
     * @return int|false
     */
    public static function string_to_timestamp($value) {
        if (is_int($value) || ctype_digit($value)) {
            // If this is either an actual integer, or all the characters are ints, then don't convert.
            return (int)$value;
        }

        // TODO This really needs timezone work...
        // Need to convert to straight date in some cases...
        $time = strtotime($value);

        return $time;
    }

    /**
     * Check the quirktimezoneoffsets setting, and if set, apply ILP quirk correction, if not, do standard correction.
     *
     * @param string $value The datetime to convert
     * @return int|false
     */
    public static function correct_ilp_timeframe_quirk($value) {
        $settings = settings::get_settings();
        if ($settings->get('quirktimezoneoffsets')) {
            return static::correct_ilp_date($value);
        } else {
            return static::string_to_timestamp($value);
        }
    }

    /**
     * Apply timezone/date correction magic to try and deal with weird dates from some versions of ILP.
     *
     * @param string $value The datetime to convert
     * @return int|false
     */
    public static function correct_ilp_date($value) {
        if (is_int($value) || ctype_digit($value)) {
            // If this is either an actual integer, or all the characters are ints, then don't convert.
            return (int)$value;
        }

        // ILP contains a bug that causes dates to sometimes be reported with incorrect/inconsistent timezone offsets.
        // Check for a ISO 8601 date with a timezone offset.
        if (preg_match('|^(\d{4}-\d{2}-\d{2}T\d{2}\:\d{2}\:\d{2})[\+\-]\d{2}\:\d{2}$|', $value)) {
            // Load into a datetime and get the timezone offset.
            $dt = new \DateTime($value);
            $offset = $dt->getOffset();

            // We also need to check if we need to shift to the local timezone, which just compounds problems...
            $srvdate = new \DateTime();
            $srvoffset = $srvdate->getOffset();
            $diff = $offset - $srvoffset;
            if (!empty($diff)) {
                if ($diff > 0) {
                    $int = new \DateInterval('PT'.$diff.'S');
                    $dt->add($int);
                } else {
                    $int = new \DateInterval('PT'.(-1 * $diff).'S');
                    $dt->sub($int);

                }
                $dt->setTimezone($srvdate->getTimezone());
            }

            // Now we need to invert the offset so that we can add it back into time, correcting the bad offset.
            $offset = $offset * -1;
            if ($offset > 0) {
                $int = new \DateInterval('PT'.$offset.'S');
                $dt->add($int);
            } else {
                $int = new \DateInterval('PT'.(-1 * $offset).'S');
                $dt->sub($int);
            }

            $time = $dt->getTimestamp();
        } else {
            $time = strtotime($value);
        }

        if ($time === false) {
            logging::instance()->log_line("Could not convert ILP time \"{$value}\".", logging::ERROR_WARN);
            return 0;
        }

        return $time;
    }
}
