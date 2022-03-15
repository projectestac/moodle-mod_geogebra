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
 * Privacy provider tests.
 *
 * @package    mod_geogebra
 * @copyright  2018 Salva Valldeoriola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use mod_geogebra\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/geogebra/locallib.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_geogebra
 * @copyright  2018 Salva Valldeoriola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_geogebra_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {
    /** @var stdClass The student object. */
    protected $student;

    /** @var stdClass The geogebra object. */
    protected $geogebra;

    /** @var stdClass The course object. */
    protected $course;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $timedue = new DateTime('NOW', core_date::get_server_timezone_object());

        $params = [
            'course' => $course->id,
            'name' => 'First Geogebra activity',
            'timedue' => $timedue->getTimestamp(),
            'grade' => '5'
        ];

        $plugingenerator = $generator->get_plugin_generator('mod_geogebra');
        // The geogebra activity the user will answer.
        $geogebra = $plugingenerator->create_instance($params);
        // Create a student which will make a geogeba activity.
        $student = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id,  $course->id, $studentrole->id);

        $var = 'grade=5&duration=1&attempts=1&state:=0';
        geogebra_add_attempt($geogebra->id, $student->id, $var);

        $this->student = $student;
        $this->geogebra = $geogebra;
        $this->course = $course;
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('mod_geogebra');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('geogebra_attempts', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('geogebra', $privacyfields);
        $this->assertArrayHasKey('vars', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('gradecomment', $privacyfields);
        $this->assertArrayHasKey('finished', $privacyfields);
        $this->assertArrayHasKey('dateteacher', $privacyfields);
        $this->assertArrayHasKey('datestudent', $privacyfields);

        $this->assertEquals('privacy:metadata:geogebra_attempts', $table->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $cm = get_coursemodule_from_instance('geogebra', $this->geogebra->id);

        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $this->assertCount(1, $contextlist);
        $contextforuser = $contextlist->current();
        $cmcontext = context_module::instance($cm->id);
        $this->assertEquals($cmcontext->id, $contextforuser->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context() {
        $cm = get_coursemodule_from_instance('geogebra', $this->geogebra->id);
        $cmcontext = context_module::instance($cm->id);

        // Export all of the data for the context.
        $this->export_context_data_for_user($this->student->id, $cmcontext, 'mod_geogebra');
        $writer = \core_privacy\local\request\writer::with_context($cmcontext);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $geogebra = $this->geogebra;
        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_instance('geogebra', $this->geogebra->id);

        // Create another student who will answer the geoebra activity.
        $student = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id, $this->course->id, $studentrole->id);

        $var = 'grade=5&duration=1&attempts=2&state=0';
        geogebra_add_attempt($geogebra->id, $student->id, $var);

        // Before deletion, we should have 2 responses.
        $count = $DB->count_records('geogebra_attempts', ['geogebra' => $geogebra->id]);
        $this->assertEquals(2, $count);

        // Delete data based on context.
        $cmcontext = context_module::instance($cm->id);
        provider::delete_data_for_all_users_in_context($cmcontext);

        // After deletion, the attempts for that geogebra activity should have been deleted.
        $count = $DB->count_records('geogebra_attempts', ['geogebra' => $geogebra->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user_() {
        global $DB;

        $geogebra = $this->geogebra;
        $generator = $this->getDataGenerator();
        $cm1 = get_coursemodule_from_instance('geogebra', $this->geogebra->id);

        $timedue = new DateTime('NOW', core_date::get_server_timezone_object());
        // Create a second geogebra activity.
        $params = [
            'course' => $this->course->id,
            'name' => 'Geogebra 2',
            'timedue' => $timedue->getTimestamp(),
            'grade' => '5'
        ];
        $plugingenerator = $generator->get_plugin_generator('mod_geogebra');
        $geogebra2 = $plugingenerator->create_instance($params);
        $plugingenerator->create_instance($params);
        $cm2 = get_coursemodule_from_instance('geogebra', $geogebra2->id);

        // Make an attempt for the first student for the 2nd geogebra  activity.
        $var = 'grade=5&duration=1&attempts=1&state=0';
        geogebra_add_attempt($geogebra2->id, $this->student->id, $var);

        // Create another student who will answer the first geogebra  activity.
        $otherstudent = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($otherstudent->id, $this->course->id, $studentrole->id);

        $var = 'grade=5&duration=1&attempts=2&state=0';
        geogebra_add_attempt($geogebra->id, $otherstudent->id, $var);

        // Before deletion, we should have 2 responses.
        $count = $DB->count_records('geogebra_attempts', ['geogebra' => $geogebra->id]);
        $this->assertEquals(2, $count);

        $context1 = context_module::instance($cm1->id);
        $context2 = context_module::instance($cm2->id);
        $contextlist = new \core_privacy\local\request\approved_contextlist($this->student, 'geogebra',
            [context_system::instance()->id, $context1->id, $context2->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion, the attempts for the first student should have been deleted.
        $count = $DB->count_records('geogebra_attempts', ['geogebra' => $geogebra->id, 'userid' => $this->student->id]);
        $this->assertEquals(0, $count);

        // Confirm that we only have one attempt available.
        $attempts = $DB->get_records('geogebra_attempts');
        $this->assertCount(1, $attempts);
        $lastresponse = reset($attempts);
        // And that it's the other student's response.
        $this->assertEquals($otherstudent->id, $lastresponse->userid);
    }

    /**
     * Test for provider::get_users_in_context().
     */
    public function test_get_users_in_context() {
        $cm = get_coursemodule_from_instance('geogebra', $this->geogebra->id);
        $cmcontext = context_module::instance($cm->id);

        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'mod_geogebra');
        \mod_geogebra\privacy\provider::get_users_in_context($userlist);

        $this->assertEquals(
                [$this->student->id],
                $userlist->get_userids()
        );
    }

    /**
     * Test for provider::get_users_in_context() with invalid context type.
     */
    public function test_get_users_in_context_invalid_context_type() {
        $systemcontext = context_system::instance();

        $userlist = new \core_privacy\local\request\userlist($systemcontext, 'mod_geogebra');
        \mod_geogebra\privacy\provider::get_users_in_context($userlist);

        $this->assertCount(0, $userlist->get_userids());
    }

    /**
     * Test for provider::delete_data_for_users().
     */
    public function test_delete_data_for_users() {
        global $DB;

        $geogebra = $this->geogebra;
        $generator = $this->getDataGenerator();
        $cm1 = get_coursemodule_from_instance('geogebra', $this->geogebra->id);

        // Create a second geogebra activity.
        $timedue = new DateTime('NOW', core_date::get_server_timezone_object());
        $params = [
                'course' => $this->course->id,
                'name' => 'Geogebra 2',
                'timedue' => $timedue->getTimestamp(),
                'grade' => '5'
        ];
        $plugingenerator = $generator->get_plugin_generator('mod_geogebra');
        $geogebra2 = $plugingenerator->create_instance($params);
        $plugingenerator->create_instance($params);
        $cm2 = get_coursemodule_from_instance('geogebra', $geogebra2->id);

        // Make a selection for the first student for the 2nd geogebra activity.
        $var = 'grade=5&duration=1&attempts=1&state=0';
        geogebra_add_attempt($geogebra2->id, $this->student->id, $var);

        // Create 2 other students who will answer the first choice activity.
        $otherstudent = $generator->create_and_enrol($this->course, 'student');
        $anotherstudent = $generator->create_and_enrol($this->course, 'student');

        $var = 'grade=5&duration=1&attempts=1&state=0';
        geogebra_add_attempt($geogebra->id, $otherstudent->id, $var);
        geogebra_add_attempt($geogebra->id, $anotherstudent->id, $var);

        // Before deletion, we should have 3 attempts in the first geogebra activity.
        $count = $DB->count_records('geogebra_attempts', ['geogebra' => $geogebra->id]);
        $this->assertEquals(3, $count);

        $context1 = context_module::instance($cm1->id);
        $approveduserlist = new \core_privacy\local\request\approved_userlist($context1, 'geogebra',
                [$this->student->id, $otherstudent->id]);
        provider::delete_data_for_users($approveduserlist);

        // After deletion, the geogebra attempts of the 2 students provided above should have been deleted
        // from the first geogebra activity. So there should only remain 1 attempt which is for $anotherstudent.
        $geogebraattempts = $DB->get_records('geogebra_attempts', ['geogebra' => $geogebra->id]);
        $this->assertCount(1, $geogebraattempts);
        $lastresponse = reset($geogebraattempts);
        $this->assertEquals($anotherstudent->id, $lastresponse->userid);

        // Confirm that the attempt that was submitted in the other geogebra activity is intact.
        $geogebraattempts = $DB->get_records_select('geogebra_attempts', 'geogebra <> ?', [$geogebra->id]);
        $this->assertCount(1, $geogebraattempts);
        $lastresponse = reset($geogebraattempts);
        // And that it's for the geogebra2 activity.
        $this->assertEquals($geogebra2->id, $lastresponse->geogebra);
    }
}
