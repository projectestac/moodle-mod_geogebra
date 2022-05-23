<?php
// in STAC package takes care to dump the results for a course by 
// geogebra_dump_results($geogebra, $context, $cm, $course, $action);
// that in turn uses geogebra_dump_attempt_row($geogebra, $attempt, $auser, $cm, $context, $row);
// that in turn calls geogebra_dump_view_extended($cm->id, $attempt->id,$ggbfilename); 
// all of these are in STAC archivelib.php but geogebra_dump_view_extended that is in view_extended
// geogebra_dump_view_extended calls geogebra_dump_applet($geogebra, $cm, $context, null, $ispreview,$ggbfilename)
// geogebra_dump_applet calls geogebra_dump_content($geogebra, $context,$ggbfilename); 
// geogebra_dump_content writes a temporary html file on the server that can start GGB
// grade_export _exended in project on grade export extended  trims the calls to archive.php that builds the page with the details for all the tests.
// At the end of  grade_export _exended the line 
// echo '<script>window.onload = function() {dump_screenshots("'.$url.'");} </script>';
// trims the download of the screenshots from GGB
// STAC project that is the starter for geogebra_dump_results
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
 * Prints a particular instance of geogebra
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage geogebra
 * @copyright  2022 TWINGSISTER
 * @author     TWINGSISTER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/archivelib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$timedump=optional_param('timedump', 0, PARAM_TEXT);
$shortname=optional_param('courseshortname', 0, PARAM_TEXT);
$testname=optional_param('testname', 0, PARAM_TEXT);
if ($id) {
    $cm         = get_coursemodule_from_id('geogebra', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $geogebra  = $DB->get_record('geogebra', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/geogebra:view', $context);
$cangrade = is_siteadmin() || has_capability('moodle/grade:edit', $context, $USER->id, false);
if(!$cangrade)return;
$PAGE->set_url('/mod/geogebra/archive.php', array('id' => $cm->id));
$PAGE->set_title(format_string($geogebra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('results', 'geogebra'));

groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/geogebra/report.php?id=' . $cm->id);

echo '<div class="reportlink">'.geogebra_submittedlink().'</div>';


$action = optional_param('action', false, PARAM_TEXT);
//if ($cangrade) {
    //echo 'Date:'.$timedump.' Course:'.$shortname.
    echo ' Geogebra quiz: '.$testname;
    // User can grade (probably is a teacher) so, by default, show results page
    geogebra_dump_results($geogebra, $context, $cm, $course, $action);
//} //else {
    // Show GGB applet with last attempt
//    geogebra_view_userid_results($geogebra, $USER->id, $cm, $context, $action);
 //   echo 'User view Geogebra results';
//}

echo $OUTPUT->footer();
