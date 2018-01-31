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
 * Tests for the LIS xml parser.
 *
 * @package    enrol_lmb
 * @author     Eric Merrill <merrill@oakland.edu>
 * @copyright  2018 Oakland University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_lmb\local\processors\lis2;
use enrol_lmb\local\data;
use enrol_lmb\local\exception;

global $CFG;
require_once($CFG->dirroot.'/enrol/lmb/tests/helper.php');

class lis2_person_test extends xml_helper {
    public function test_person() {
        global $CFG;
        $this->resetAfterTest(true);

        $node = $this->get_node_for_file($CFG->dirroot.'/enrol/lmb/tests/fixtures/lis2/parse/person_replace.xml');

        $converter = new lis2\person();

        $person = $converter->process_xml_to_data($node);

        $this->assertInstanceOf(data\person::class, $person);
        $this->assertEquals('msgSourcedID', $person->messagereference);
        $this->assertEquals('ILP', $person->referenceagent);
        $this->assertEquals('extensionSourcedID', $person->sdid);

        // Some name stuff.
        $this->assertEquals('Mr. Test A User Jr.', $person->fullname);
        $this->assertEquals('Nick', $person->nickname);
        $this->assertEquals('User', $person->familyname);
        $this->assertEquals('Test', $person->givenname);
        $this->assertEquals('Mr.', $person->prefix);
        $this->assertEquals('Jr.', $person->suffix);
        $this->assertEquals('A', $person->middlename);

        $this->assertEquals('BannerIDExtension', $person->sctid);

        // Some UserID stuff.
        $this->assertInternalType('array', $person->userid);
        $this->assertCount(4, $person->userid);

        $userid = $person->userid['Logon ID'];
        $this->assertEquals('logonUserID', $userid->userid);
        $this->assertEquals('11111111-2222-1111-3333-111111111111', $userid->password);

        $userid = $person->userid['SCTID'];
        $this->assertEquals('SCTIDUserid', $userid->userid);
        $this->assertFalse(isset($userid->password));

        $userid = $person->userid['Email ID'];
        $this->assertEquals('emailUserID', $userid->userid);
        $this->assertEquals('{SSHA}FakeSHAPassword==', $userid->password);
        $this->assertEquals('SSHA', $userid->pwencryptiontype);

        $userid = $person->userid['Other ID'];
        $this->assertEquals('otherUserID', $userid->userid);
        $this->assertFalse(isset($userid->password));

        // Check extensions.
        $this->assertInternalType('array', $person->extension);
        $this->assertCount(3, $person->extension);

        $extension = $person->extension['BannerID'];
        $this->assertEquals('BannerIDExtension', $extension->value);
        $this->assertEquals('String', $extension->type);

        $extension = $person->extension['SourcedId'];
        $this->assertEquals('extensionSourcedID', $extension->value);
        $this->assertEquals('String', $extension->type);

        $extension = $person->extension['UnknownExtension'];
        $this->assertEquals('UnknownValue', $extension->value);
        $this->assertFalse($extension->type);

        // Contact info.
        $this->assertEquals('useremail@example.com', $person->email);

        // Roles.
        $this->assertEquals(1, $person->rolestaff);
        $this->assertEquals(1, $person->rolestudent);
        $this->assertEquals(1, $person->roleprospectivestudent);
        $this->assertEquals(1, $person->rolefaculty);
        $this->assertEquals(1, $person->rolealumni);
        $this->assertEquals('Faculty', $person->primaryrole);

        // Now make sure it's falling back correctly with missing nodes.
        unset($node->PERSONRECORD->PERSON->EXTENSION);
        $person = $converter->process_xml_to_data($node);

        $this->assertEquals('personSourcedID', $person->sdid);
        $this->assertEquals('SCTIDUserid', $person->sctid);

        return;

    }

}
