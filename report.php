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
 * Prints a particular instance of geogebra
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage geogebra
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or

if ($id) {
    $cm         = get_coursemodule_from_id('geogebra', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $geogebra  = $DB->get_record('geogebra', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    throw new \moodle_exception('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/geogebra:view', $context);

$PAGE->set_url('/mod/geogebra/report.php', array('id' => $cm->id));
$PAGE->set_title(format_string($geogebra->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('results', 'geogebra'));

groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/geogebra/report.php?id=' . $cm->id);

echo '<div class="reportlink">'.geogebra_submittedlink().'</div>';

$cangrade = is_siteadmin() || has_capability('moodle/grade:edit', $context, $USER->id, false);

$action = optional_param('action', false, PARAM_TEXT);
if ($cangrade) {
    // User can grade (probably is a teacher) so, by default, show results page
    geogebra_view_results($geogebra, $context, $cm, $course, $action);
} else {
    // Show GGB applet with last attempt
    geogebra_view_userid_results($geogebra, $USER->id, $cm, $context, $action);
}

echo $OUTPUT->footer();
