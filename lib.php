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
 * Library of interface functions and constants for module geogebra
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the geogebra specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod
 * @subpackage geogebra
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// File types
const GEOGEBRA_FILE_TYPE_LOCAL = 'local';
const GEOGEBRA_FILE_TYPE_EXTERNAL = 'external';

// Grading method
const GEOGEBRA_NO_GRADING = 0;
const GEOGEBRA_AVERAGE_GRADE = 1;
const GEOGEBRA_HIGHEST_GRADE = 2;
const GEOGEBRA_LOWEST_GRADE = 3;
const GEOGEBRA_FIRST_GRADE = 4;
const GEOGEBRA_LAST_GRADE = 5;

// GeoGebra who's updating activity
const GEOGEBRA_UPDATE_STUDENT = 0;
const GEOGEBRA_UPDATE_TEACHER = 1;

require_once $CFG->libdir . '/formslib.php';
require_once $CFG->dirroot . '/calendar/lib.php';


// Moodle core API.

/**
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 * @todo: review features before publishing the module
 *
 * @see plugin_supports() in lib/moodlelib.php
 */
function geogebra_supports($feature) {

    if ($feature === FEATURE_MOD_PURPOSE) {
        return MOD_PURPOSE_ASSESSMENT;
    }

    switch ($feature) {
        case FEATURE_GROUPS:
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            if (defined('FEATURE_SHOW_DESCRIPTION') && $feature === FEATURE_SHOW_DESCRIPTION) {
                return true;
            }
            return null;
    }

}

/**
 * Saves a new instance of the geogebra into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $geogebra An object from the form in mod_form.php
 * @param mod_geogebra_mod_form|null $mform
 * @throws dml_exception
 * @return int The id of the newly inserted geogebra record
 * @todo: create event (when timedue added)
 */
function geogebra_add_instance(stdClass $geogebra, mod_geogebra_mod_form $mform = null) {

    global $DB;

    $geogebra->timecreated = time();
    $cmid = $geogebra->coursemodule;

    geogebra_before_add_or_update($geogebra, $mform);

    $geogebra->id = $DB->insert_record('geogebra', $geogebra);

    // We need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $geogebra->id, ['id' => $cmid]);

    geogebra_after_add_or_update($geogebra, $mform);

    return $geogebra->id;

}

/**
 * Updates an instance of the geogebra in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $geogebra An object from the form in mod_form.php
 * @param mod_geogebra_mod_form|null $mform
 * @throws dml_exception
 * @return boolean Success/Fail
 */
function geogebra_update_instance(stdClass $geogebra, mod_geogebra_mod_form $mform = null) {

    global $DB;

    $geogebra->timemodified = time();
    $geogebra->id = $geogebra->instance;

    geogebra_before_add_or_update($geogebra, $mform);

    $DB->update_record('geogebra', $geogebra);

    geogebra_after_add_or_update($geogebra, $mform);

    return true;

}

/**
 * Removes an instance of the geogebra from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Identifier of the module instance
 * @throws dml_exception
 * @return boolean Success/Failure
 * @todo: delete event records (after adding this feature to the module)
 */
function geogebra_delete_instance($id) {

    global $DB;

    if (!$geogebra = $DB->get_record('geogebra', ['id' => $id])) {
        return false;
    }

    $result = true;

    $DB->delete_records('geogebra_attempts', ['geogebra' => $id]);

    // delete items from the gradebook
    if (!geogebra_grade_item_delete($geogebra)) {
        $result = false;
    }

    /** TODO: // delete files associated with this geogebra
     * $fs = get_file_storage();
     * if (! $fs->delete_area_files($context->id) ) {
     * $result = false;
     * }
     **/

    // Delete events related to this instance.
    $DB->delete_records('event', ['modulename' => 'geogebra', 'instance' => $id]);

    // delete the instance.
    $DB->delete_records('geogebra', ['id' => $id]);

    return $result;

}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $geogebra
 * @throws coding_exception
 * @return stdClass|null
 */
function geogebra_user_outline($course, $user, $mod, $geogebra) {

    global $CFG;
    require_once $CFG->libdir . '/gradelib.php';

    $result = null;
    $grades = grade_get_grades($course->id, 'mod', 'geogebra', $geogebra->id, $user->id);

    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        $result = new stdClass();
        $result->info = get_string('gradenoun') . ': ' . $grade->str_long_grade;

        //if grade was last modified by the user themselves use date graded, otherwise use date submitted
        if ($grade->usermodified === $user->id || empty($grade->datesubmitted)) {
            $result->time = $grade->dategraded;
        } else {
            $result->time = $grade->datesubmitted;
        }
    }

    return $result;

}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @throws coding_exception
 * @return true HTML
 * @todo: implement
 *
 */
function geogebra_user_complete($course, $user, $mod, $geogebra) {

    $outline = geogebra_user_outline($course, $user, $mod, $geogebra);
    print_r($outline->info);

    return true;

}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in geogebra activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo: implement
 */
function geogebra_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Return all activity in GeoGebra since a given time.
 *
 * @param array $activities sequentially indexed array of objects
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid defaults to 0
 * @param int $groupid defaults to 0
 * @return void adds items into $activities and increases $index
 * @todo: implement
 */
function geogebra_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
}

/**
 * Prints single activity item prepared by {@see geogebra_get_recent_mod_activity()}
 *
 * @return void
 * @todo: implement
 */
function geogebra_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Returns an array of users who are participanting in this geogebra
 *
 * Must return an array of users who are participants for a given instance
 * of geogebra. Must include every user involved in the instance,
 * independently of their role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $geogebraid ID of an instance of this module
 * @throws dml_exception
 * @return array false if no participants, array of objects otherwise
 */
function geogebra_get_participants($geogebraid) {

    global $DB;

    // Get students.
    $students = $DB->get_records_sql(
        "SELECT DISTINCT u.id, u.id AS userid
         FROM {user} u, {geogebra_sessions} js
         WHERE js.geogebraid = ? AND u.id = js.user_id",
        [$geogebraid]
    );

    // Get teachers.
    $teachers = $DB->get_records_sql(
        "SELECT DISTINCT u.id, u.id AS userid
         FROM {user} u, {geogebra_sessions} js
         WHERE js.geogebraid = ? AND u.id = js.user_id",
        [$geogebraid]
    );

    // Add teachers to students.
    if ($teachers) {
        foreach ($teachers as $teacher) {
            $students[$teacher->id] = $teacher;
        }
    }

    // Return students array (it contains an array of unique users).
    return ($students);

}

/**
 * Returns all other caps used in the module
 *
 * @return array
 * @example return array('moodle/site:accessallgroups');
 */
function geogebra_get_extra_capabilities() {

    return [
        'moodle/site:accessallgroups',
        'moodle/site:viewfullnames',
        'moodle/grade:managegradingforms',
    ];

}


// Gradebook API.

/**
 * Is a given scale used by the instance of geogebra?
 *
 * This function returns if a scale is being used by one geogebra
 * if it has support for grading and scales. See forum, glossary or
 * journal modules as reference.
 *
 * @param int $geogebraid ID of an instance of this module
 * @param $scaleid
 * @throws dml_exception
 * @return bool true if the scale is used by the given geogebra instance
 */
function geogebra_scale_used($geogebraid, $scaleid) {

    global $DB;

    $return = false;
    $rec = $DB->get_record('geogebra', ['id' => $geogebraid, 'grade' => -$scaleid]);

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;

}

/**
 * Checks if scale is being used by any instance of geogebra.
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @throws dml_exception
 * @return boolean true if the scale is used by any geogebra instance
 */
function geogebra_scale_used_anywhere($scaleid) {

    global $DB;

    return $scaleid and $DB->record_exists('geogebra', ['grade' => -$scaleid]);

}

/**
 * Creates or updates grade item for the give geogebra instance
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $geogebra instance object with extra cmidnumber and modname property
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @throws coding_exception
 * @return int 0 if ok
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_SCALE
 * @uses GRADE_TYPE_NONE
 */
function geogebra_grade_item_update(stdClass $geogebra, $grades = NULL) {

    global $CFG;
    require_once $CFG->libdir . '/gradelib.php';

    if (!isset($geogebra->courseid)) {
        $geogebra->courseid = $geogebra->course;
    }

    $params = [];
    $params['itemname'] = clean_param($geogebra->name, PARAM_NOTAGS);
 
    if (empty($geogebra->cmidnumber)) {
        $cm = get_coursemodule_from_instance('geogebra', $geogebra->id, $geogebra->course, false, MUST_EXIST);
        $geogebra->cmidnumber = $cm->idnumber;
    }

    $params['idnumber'] = $geogebra->cmidnumber;

    if (!isset($geogebra->grade)) {
        $geogebra->grade = 0;
    }

    if ($geogebra->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $geogebra->grade;
        $params['grademin'] = 0;
    } else if ($geogebra->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid'] = -$geogebra->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE; // allow text comments only
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    grade_update('mod/geogebra', $geogebra->courseid, 'mod', 'geogebra', $geogebra->id, 0, $grades, $params);

    return true;

}

/**
 * Delete grade item for given geogebra
 *
 * @param object $geogebra object
 * @return bool grade_item
 */
function geogebra_grade_item_delete($geogebra) {

    global $CFG;
    require_once $CFG->libdir . '/gradelib.php';

    return grade_update(
            'mod/geogebra',
            $geogebra->course,
            'mod',
            'geogebra',
            $geogebra->id,
            0,
            NULL,
            ['deleted' => 1]) === GRADE_UPDATE_OK;

}

/**
 * Returns the grade informacion for a user and geogebra activity
 * acording to the chosen grademethod.
 *
 * @param object $geogebra
 * @param int $userid
 * @throws dml_exception
 * @return mixed
 */
function geogebra_get_user_grades($geogebra, $userid) {

    global $CFG;
    require_once $CFG->dirroot . '/mod/geogebra/locallib.php';

    // sanity check
    if (!isset($geogebra->id)) {
        return false;
    }

    switch ($geogebra->grademethod) {
        case GEOGEBRA_NO_GRADING:
            $attempt = geogebra_get_nograding_grade($geogebra->id, $userid);
            break;
        case GEOGEBRA_AVERAGE_GRADE:
            $attempt = (geogebra_get_average_grade($geogebra->id, $userid));
            break;
        case GEOGEBRA_HIGHEST_GRADE:
            $attempt = (geogebra_get_highest_attempt_grade($geogebra->id, $userid));
            break;
        case GEOGEBRA_LOWEST_GRADE:
            $attempt = (geogebra_get_lowest_attempt_grade($geogebra->id, $userid));
            break;
        case GEOGEBRA_FIRST_GRADE:
            $attempt = (geogebra_get_first_attempt_grade($geogebra->id, $userid));
            break;
        case GEOGEBRA_LAST_GRADE:
            $attempt = (geogebra_get_last_attempt_grade($geogebra->id, $userid));
            break;
        default:
            return false;
    }

    return $attempt;

}

/**
 * Update geogebra grades in the gradebook
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $geogebra instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @param boolean $nullifnone return null if grade does not exist
 * @throws coding_exception
 * @throws dml_exception
 * @return void
 */
function geogebra_update_grades(stdClass $geogebra, $userid = 0, $nullifnone = true) {

    global $CFG;
    require_once $CFG->libdir . '/gradelib.php';

    if ($geogebra->grade === 0) {
        geogebra_grade_item_update($geogebra);
    } else if ($grades = geogebra_get_user_grades($geogebra, $userid)) {
        foreach ($grades as $k => $v) {
            if ($k === 'rawgrade' && $v === -1) {
                $grades[$k] = null;
            }
        }
        geogebra_grade_item_update($geogebra, $grades);
    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = NULL;
        geogebra_grade_item_update($geogebra, $grade);
    } else {
        geogebra_grade_item_update($geogebra);
    }

}


// File API.

/**
 * Returns the lists of all browsable file areas within the given module context
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @throws coding_exception
 * @return array of [(string)filearea] => (string)description
 */
function geogebra_get_file_areas($course, $cm, $context) {

    return [
        'content' => get_string('urledit', 'geogebra'),
        'extracted_files' => get_string('extractedfromggb', 'geogebra'),
    ];

}

/**
 * File browsing support for geogebra module content area.
 *
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @throws coding_exception
 * @return object file_info instance or null if not found
 */
function geogebra_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {

    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();
    if ($filearea === 'extracted_files') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        $urlbase = $CFG->wwwroot . '/pluginfile.php';

        if (!$storedfile = $fs->get_file($context->id, 'mod_geogebra', $filearea, $itemid, $filepath, $filename)) {
            if ($filepath === '/' && $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_geogebra', $filearea, $itemid);
            } else {
                // not found
                return null;
            }
        }

        return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, false);
    }

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        $urlbase = $CFG->wwwroot . '/pluginfile.php';

        if (!$storedfile = $fs->get_file($context->id, 'mod_geogebra', $filearea, $itemid, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_geogebra', $filearea, $itemid);
            } else {
                // not found
                return null;
            }
        }

        return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, false);
    }

    // note: geogebra_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the files from the geogebra file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 * @return void this should never return to the caller
 */
function geogebra_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload) {

    if ($context->contextlevel !== CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    if (!has_capability('mod/geogebra:view', $context)) {
        return;
    }

    if ($filearea !== 'extracted_files') {
        // intro is handled automatically in pluginfile.php
        return;
    }

    array_shift($args); // ignore revision - designed to prevent caching problems only

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_geogebra/$filearea/0/$relativepath", '/');

    if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
        send_file_not_found();
    }

    // Finally send the file.
    send_stored_file($file, 86400, 0, $forcedownload);

}


// Navigation API.

/**
 * Extends the settings navigation with the geogebra settings
 * This function is called when the context for the page is a geogebra module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node|null $geogebranode {@link navigation_node}
 * @throws coding_exception
 * @throws moodle_exception
 */
function geogebra_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $geogebranode = null) {

    global $PAGE;

    $keys = $geogebranode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys, true);

    if ($i === false && array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('moodle/grade:viewall', $PAGE->context)) {
        $node = navigation_node::create(
            get_string('preview_geogebra', 'geogebra'),
            new moodle_url('/mod/geogebra/view.php', ['id' => $PAGE->cm->id, 'action' => 'preview']),
            navigation_node::TYPE_SETTING, null, 'mod_preview_geogebra_preview',
            new pix_icon('i/preview', '')
        );
        $geogebranode->add_node($node, $beforekey);
    }
 
    $url = new moodle_url(
        '/mod/geogebra/report.php',
        ['id' => $PAGE->cm->id]
    );

    $geogebranode->add_node(
        navigation_node::create(
            get_string('results', 'geogebra'),
            $url,

            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        ),
        $beforekey
    );

}


// Reset.

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @throws coding_exception
 * @throws dml_exception
 */
function geogebra_reset_gradebook($courseid) {

    global $DB;

    $params = ['courseid' => $courseid];

    $sql = "SELECT j.*, cm.idnumber as cmidnumber, j.course AS courseid
            FROM {geogebra} j, {course_modules} cm, {modules} m
            WHERE m.name='geogebra' AND m.id=cm.module AND cm.instance=j.id AND j.course=:courseid";

    if ($geogebras = $DB->get_records_sql($sql, $params)) {
        foreach ($geogebras as $geogebra) {
            geogebra_grade_item_update($geogebra, 'reset');
        }
    }

}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all posts from the specified geogebra
 * and clean up any related data.
 *
 * @param stdClass $data The data submitted from the reset course.
 * @throws coding_exception
 * @throws dml_exception
 * @return array status array
 */
function geogebra_reset_userdata($data) {

    global $DB;

    $componentstr = get_string('modulenameplural', 'geogebra');
    $status = [];

    if (!empty($data->reset_geogebra_deleteallattempts)) {
        $select = 'geogebra IN (SELECT j.id FROM {geogebra} j WHERE j.course = :courseid)';
        $DB->delete_records_select('geogebra_attempts', $select, ['courseid' => $data->courseid]);

        // remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            geogebra_reset_gradebook($data->courseid);
        }

        $status[] = [
            'component' => $componentstr,
            'item' => get_string('deleteallattempts', 'geogebra'),
            'error' => false,
        ];
    }

    return $status;

}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the geogebra.
 *
 * @param stdClass $mform form passed by reference
 * @throws coding_exception
 */
function geogebra_reset_course_form_definition(&$mform) {

    $mform->addElement('header', 'geogebraheader', get_string('modulenameplural', 'geogebra'));
    $mform->addElement('checkbox', 'reset_geogebra_deleteallattempts', get_string('deleteallattempts', 'geogebra'));

}

/**
 * Course reset form defaults.
 */
function geogebra_reset_course_form_defaults($course) {
    return ['reset_geogebra_deleteallattempts' => 1];
}
