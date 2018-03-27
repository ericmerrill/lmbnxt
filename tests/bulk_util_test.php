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
 * Tests for the bulk utility.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

use enrol_lmb\bulk_util;
use enrol_lmb\local\processors\lis2;

class bulk_util_test extends xml_helper {

    public function test_get_terms_in_timeframe() {
        $this->resetAfterTest();
        $this->setup_bulk();

        $util = new bulk_util();
        $results = $util->get_terms_in_timeframe(1510000000);

        $this->assertTrue(isset($results['201730']));
        $this->assertTrue(isset($results['201730']['termupdate']));
        $this->assertEquals(1, $results['201730']['termupdate']);
        $this->assertTrue(isset($results['201730']['sectionupdate']));
        $this->assertEquals(2, $results['201730']['sectionupdate']);
        $this->assertTrue(isset($results['201730']['enrolupdates']));
        $this->assertEquals(12, $results['201730']['enrolupdates']);

        $this->assertTrue(isset($results['201720']));
        $this->assertFalse(isset($results['201720']['termupdate']));
        $this->assertTrue(isset($results['201720']['sectionupdate']));
        $this->assertEquals(1, $results['201720']['sectionupdate']);
        $this->assertTrue(isset($results['201720']['enrolupdates']));
        $this->assertEquals(5, $results['201720']['enrolupdates']);

        $this->assertFalse(isset($results['201710']));
    }

    public function test_get_term_enrols_active_count() {
        $this->resetAfterTest();
        $this->setup_bulk();

        $util = new bulk_util();
        $results = $util->get_term_enrols_active_count('201700');
        $this->assertEquals(0, $results);

        $results = $util->get_term_enrols_active_count('201710');
        $this->assertEquals(15, $results);

        $results = $util->get_term_enrols_active_count('201720');
        $this->assertEquals(20, $results);

        $results = $util->get_term_enrols_active_count('201730');
        $this->assertEquals(26, $results);
    }

    public function test_get_term_enrols_to_drop_count() {
        $this->resetAfterTest();
        $this->setup_bulk();

        $util = new bulk_util();
        // Check a term that doesn't exist, this would get any if they existed.
        $results = $util->get_term_enrols_to_drop_count('201700', 1520000000);
        $this->assertEquals(0, $results);

        // Because we didn't receive any messages for this term, everything would be to drop.
        $results = $util->get_term_enrols_to_drop_count('201710', 1510000000);
        $this->assertEquals(15, $results);

        $results = $util->get_term_enrols_to_drop_count('201720', 1510000000);
        $this->assertEquals(15, $results);
        $results = $util->get_term_enrols_to_drop_count('201720', 1520000000);
        $this->assertEquals(20, $results);

        $results = $util->get_term_enrols_to_drop_count('201730', 1510000000);
        $this->assertEquals(8, $results);
        $results = $util->get_term_enrols_to_drop_count('201730', 1520000000);
        $this->assertEquals(26, $results);
    }

    protected function setup_bulk() {
        $oldtime = 1500000000;
        $newtime = 1510000000;

        $users = [];
        for ($i = 0; $i < 20; $i++) {
            $users[$i] = $this->create_lmb_person();
        }

        $terms = [];
        $sections = [];
        $term = $this->create_lmb_term(['sdid' => '201710', 'messagetime' => $oldtime]);
        $terms['201710'] = $term;

        $section = $this->create_lmb_section(['termsdid' => '201710', 'messagetime' => $oldtime]);
        $sections['201710'][$section->sdid] = $section;
        for ($i = 0; $i < 5; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime]);
        }
        for ($i = 5; $i < 7; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime, 'status' => 0]);
        }

        $section = $this->create_lmb_section(['termsdid' => '201710', 'messagetime' => $oldtime]);
        $sections['201710'][$section->sdid] = $section;
        for ($i = 0; $i < 10; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime]);
        }

        $term = $this->create_lmb_term(['sdid' => '201720', 'messagetime' => $oldtime]);
        $terms['201720'] = $term;
        $section = $this->create_lmb_section(['termsdid' => '201720', 'messagetime' => $oldtime]);
        $sections['201720'][$section->sdid] = $section;
        for ($i = 0; $i < 5; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime]);
        }
        for ($i = 5; $i < 10; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime]);
        }
        for ($i = 10; $i < 12; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime, 'status' => 0]);
        }

        $section = $this->create_lmb_section(['termsdid' => '201720', 'messagetime' => $newtime]);
        $sections['201720'][$section->sdid] = $section;
        for ($i = 0; $i < 10; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime]);
        }

        $term = $this->create_lmb_term(['sdid' => '201730', 'messagetime' => $newtime]);
        $terms['201730'] = $term;
        $section = $this->create_lmb_section(['termsdid' => '201730', 'messagetime' => $newtime]);
        $sections['201730'][$section->sdid] = $section;
        for ($i = 0; $i < 5; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $oldtime]);
        }
        for ($i = 5; $i < 10; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime]);
        }
        for ($i = 10; $i < 12; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime, 'status' => 0]);
        }

        $section = $this->create_lmb_section(['termsdid' => '201730', 'messagetime' => $newtime]);
        $sections['201730'][$section->sdid] = $section;
        for ($i = 0; $i < 7; $i++) {
            $this->create_lmb_enrol($section, $users[$i], ['messagetime' => $newtime]);
        }

        // Now a course section that doesn't exist.
        for ($i = 0; $i < 6; $i++) {
            $this->create_lmb_enrol('99999.201730', $users[$i], ['messagetime' => $newtime]);
        }
        for ($i = 6; $i < 9; $i++) {
            $this->create_lmb_enrol('99999.201730', $users[$i], ['messagetime' => $oldtime]);
        }
        for ($i = 9; $i < 13; $i++) {
            $this->create_lmb_enrol('99999.201730', $users[$i], ['messagetime' => $newtime, 'status' => 0]);
        }
    }

    public function test_drop_old_term_enrols() {
        $this->resetAfterTest();
        $this->setup_bulk();

        $util = new bulk_util();
        $results = $util->drop_old_term_enrols('201730', 1510000000);


    }
}
