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
 * Generator tests.
 *
 * @package    mod_geogebra
 * @copyright  2018 Salva Valldeoriola <svallde2@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/geogebra/locallib.php');

/**
 * Generator tests class.
 *
 * @package    mod_geogebra
 * @copyright  2018 Salva Valldeoriola <svallde2@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_geogebra_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $timedue = new DateTime('NOW', core_date::get_server_timezone_object());
        $timedue = $timedue->getTimestamp();

        $this->assertFalse($DB->record_exists('geogebra', array('course' => $course->id)));
        $geog = $this->getDataGenerator()->create_module('geogebra',
                array('course' => $course->id, 'timedue' => $timedue, 'grade' => '5'));
        $this->assertEquals(1, $DB->count_records('geogebra', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('geogebra', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('geogebra', array('id' => $geog->id)));

        $params = array('course' => $course->id, 'name' => 'One more geogebra', 'timedue' => $timedue, 'grade' => '5');
        $geog = $this->getDataGenerator()->create_module('geogebra', $params);
        $this->assertEquals(2, $DB->count_records('geogebra', array('course' => $course->id)));
        $this->assertEquals('One more geogebra', $DB->get_field_select('geogebra', 'name', 'id = :id', array('id' => $geog->id)));
    }
}
