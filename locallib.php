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
 * Internal library of functions for module geogebra
 *
 * All the geogebra specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod
 * @subpackage geogebra
 * @copyright  2011 Departament d'Ensenyament de la Generalitat de Catalunya
 * @author     Sara Arjona Téllez <sarjona@xtec.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filelib.php");

function geogebra_before_add_or_update(&$geogebra, $mform) {
    geogebra_update_attributes($geogebra);
    if (is_null($mform)) {
        $geogebra->url = '';
    } else {
        if ($mform->get_data()->filetype === GEOGEBRA_FILE_TYPE_LOCAL) {
            $geogebra->url = $mform->get_data()->geogebrafile;
        } else {
            $geogebra->url = $geogebra->geogebraurl;
        }
    }
    return true;
}

function geogebra_after_add_or_update($geogebra, $mform) {
    global $DB;

    if (is_null($mform)) {
        $geogebra->url = '';
    } else {
        if ($mform->get_data()->filetype === GEOGEBRA_FILE_TYPE_LOCAL) {
            $filename = geogebra_set_mainfile($geogebra);
            $geogebra->url = $filename;
            $result = $DB->update_record('geogebra', $geogebra);
        }
    }

    if (isset($geogebra->timedue) && $geogebra->timedue) {
        $event = new stdClass();
        if ($event->id = $DB->get_field('event', 'id', array('modulename' => 'geogebra', 'instance' => $geogebra->id))) {
            $event->name        = $geogebra->name;
            $event->description = format_module_intro('geogebra', $geogebra, $geogebra->coursemodule, false);
            $event->timestart   = $geogebra->timedue;

            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            $event = new stdClass();
            $event->name        = $geogebra->name;
            $event->description = format_module_intro('geogebra', $geogebra, $geogebra->coursemodule, false);
            $event->courseid    = $geogebra->course;
            $event->groupid     = 0;
            $event->userid      = 0;
            $event->modulename  = 'geogebra';
            $event->instance    = $geogebra->id;
            $event->eventtype   = 'due';
            $event->timestart   = $geogebra->timedue;
            $event->timeduration = 0;

            calendar_event::create($event);
        }
    } else {
        $DB->delete_records('event', array('modulename' => 'geogebra', 'instance' => $geogebra->id));
    }

    // get existing grade item
    geogebra_grade_item_update($geogebra);

    return true;
}

/**
 * Get an array with the file types
 *
 * @return array   The array with each file type
 */
function geogebra_get_file_types() {
    $filetypes = array(GEOGEBRA_FILE_TYPE_LOCAL => get_string('filetypelocal', 'geogebra'));
    $filetypes[GEOGEBRA_FILE_TYPE_EXTERNAL] = get_string('filetypeexternal', 'geogebra');
    return $filetypes;
}

/**
 * Display the geogebra intro
 *
 */
function geogebra_view_intro($geogebra, $cm) {
    global $OUTPUT, $CFG;

    echo $OUTPUT->heading(format_string($geogebra->name, false));
    echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
    echo format_module_intro('geogebra', $geogebra, $cm->id);
    echo $OUTPUT->box_end();
}

/**
 * Display the geogebra dates
 *
 * Prints the geogebra start and end dates in a box.
 */
function geogebra_view_dates($geogebra, $cm, $timenow=null) {
    global $OUTPUT;

    if (!$geogebra->timeavailable && !$geogebra->timedue) {
        return;
    }

    if (is_null($timenow)) {
        $timenow = time();
    }

    echo $OUTPUT->box_start('generalbox boxaligncenter geogebradates', 'dates');
    if ($geogebra->timeavailable) {
        echo '<div class="title-time">'.get_string('availabledate', 'geogebra').': </div>';
        echo '<div class="data-time">'.userdate($geogebra->timeavailable).'</div>';
    }
    if ($geogebra->timedue) {
        echo '<div class="title-time">'.get_string('duedate', 'geogebra').': </div>';
        echo '<div class="data-time">'.userdate($geogebra->timedue).'</div>';
    }
    echo $OUTPUT->box_end();
}

/**
 * Display the geogebra applet
 *
 * @param type $geogebra
 * @param type $cm
 * @param type $context
 * @param type $attempt
 * @param type $ispreview if is a preview or not
 * @param type $timenow
 */
function geogebra_view_applet($geogebra, $cm, $context, $attempt = null, $ispreview = false) {
    global $OUTPUT, $PAGE, $CFG, $USER;

    $timenow = time();

    if ($attempt) {
        $viewmode = 'view';
        $userid = $attempt->userid;
    } else {
        $userid = $USER->id;
        if ($ispreview) {
            $viewmode = 'preview';
        } else {
            $viewmode = 'submit';
        }
    }


    $isopen = (empty($geogebra->timeavailable) || $geogebra->timeavailable < $timenow);
    if (!$isopen) {
        $content .= $OUTPUT->notification(get_string('notopenyet', 'geogebra', userdate($geogebra->timeavailable)));
        if (!$ispreview) {
            return $content;
        }
    }

    $isclosed = (!empty($geogebra->timedue) && $geogebra->timedue < $timenow);
    if ($isclosed) {
        $content .= $OUTPUT->notification(get_string('expired', 'geogebra', userdate($geogebra->timedue)));
        if (!$ispreview) {
            return $content;
        }
    }

    $attempts = geogebra_count_finished_attempts($geogebra->id, $userid);

    if ($ispreview || $geogebra->maxattempts < 0 || $attempts < $geogebra->maxattempts) {
        // Show results when viewmode is "view"
        if (!empty($attempt)) {
            // TODO: Change $USER by selected userid
            geogebra_view_userid_results($geogebra, $userid, $cm, $context, $viewmode, $attempt);
        } else if (!$ispreview) {
            echo $OUTPUT->box_start('generalbox');
            if ($geogebra->maxattempts < 0) {
                echo get_string('unlimitedattempts', 'geogebra').'<br/>';
            } else if ($attempts == $geogebra->maxattempts - 1) {
                echo get_string('lastattemptremaining', 'geogebra').'<br/>';
            } else {
                echo get_string('attemptsremaining', 'geogebra').($geogebra->maxattempts - $attempts).'<br/>';
            }

            // If there is some unfinished attempt, show it
            $attempt = geogebra_get_unfinished_attempt($geogebra->id, $userid);
            if (!empty($attempt)) {
                echo '('.get_string('resumeattempt', 'geogebra').')';
            }
            echo $OUTPUT->box_end();
        }

        geogebra_print_content($geogebra, $context);

        // If not preview mode, load state
        $parsedvars = null;
        if ($attempt) {
            parse_str($attempt->vars, $parsedvars);
        }
        if (isset($parsedvars['state'])) {
            // Continue previuos attempt
            $eduxtecadapterparameters = http_build_query(array(
                'state' => $parsedvars['state'],
                'grade' => $parsedvars['grade'],
                'duration' => $parsedvars['duration'],
                'attempts' => $parsedvars['attempts']
                    ), '', '&');
        } else {
            // New attempt
            $attempts = geogebra_count_finished_attempts($geogebra->id, $userid) + 1;
            $eduxtecadapterparameters = http_build_query(array(
                'attempts' => $attempts
                    ), '', '&');
        }
        $PAGE->requires->js('/mod/geogebra/geogebra_view.js');
        echo '<form id="geogebra_form" method="POST" action="attempt.php">';
        echo '<input type="hidden" name="appletInformation" />';
        echo '<input type="hidden" name="id" value="'.$context->instanceid.'"/>';
        echo '<input type="hidden" name="n" value="'.$geogebra->id.'"/>';
        echo '<input type="hidden" name="f" value="0"/>';
        // Only show submit buttons if not view mode
        if (empty($attempt) || !$attempt->finished) {
            echo '<input id="geogebra_form_save" type="button" value="' . get_string('savewithoutsubmitting', 'geogebra') . '" />';
            echo '<input id="geogebra_form_submit" type="button" value="' . get_string('submitandfinish', 'geogebra') . '" />';
        }
        echo '<input type="hidden" name="prevAppletInformation" value="' . $eduxtecadapterparameters . '" />';
        echo ' </form>';
    } else {
        echo $OUTPUT->box(get_string('msg_noattempts', 'geogebra'), 'generalbox boxaligncenter');
    }
    geogebra_view_dates($geogebra, $context, $timenow);
}

function geogebra_get_id($url) {
    $parts = explode('/', $url);
    foreach ($parts as $i => $part) {
        if ($part == 'id') {
            return $parts[$i + 1];
        }
    }
    return false;
}

function geogebra_get_script_param($name, $attributes) {
    return (isset($attributes[$name]) && $attributes[$name]) ? 'true' : 'false';
}

function geogebra_print_content($geogebra, $context) {
    parse_str($geogebra->attributes, $attributes);

    $attribnames = array('enableRightClick', 'showAlgebraInput', 'showMenuBar', 'showToolBar',
        'showToolBarHelp', 'enableLabelDrags', 'showResetIcon', 'useBrowserForJS');

    $attribs = array(
        'randomSeed' => $geogebra->seed,
        'width' => $geogebra->width,
        'height' => $geogebra->height,
        'language' => $attributes['language']
        );

    // If seed is 0 or not set (default is 0) then all random elements in the
    // GGB activity will be randomly assigned for every access to the GGB activity
    // set seed to have all instances with the same random values.
    if ((int)$geogebra->seed === 0) {
        unset($attribs['randomSeed']);
    }

    if (geogebra_is_valid_external_url($geogebra->url)) {
        // Get contents if specified GGB is external
        $materialid = geogebra_get_id($geogebra->url);
        if (!$materialid) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $geogebra->url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $content = curl_exec($curl);
            curl_close($curl);
        }
    } else {
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_geogebra', 'content', 0, '/', $geogebra->url);
        if ($file) {
            $content = $file->get_content();
        }
    }

    if (isset($content) && !empty($content)) {
        $attribs['ggbBase64'] = base64_encode($content);
        // $attribs['ggbBase64'] = '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';
    } else if (isset($materialid) && !empty($materialid)) {
        $attribs['material_id'] = $materialid;
    } else {
        return false;
    }

    // Add loading of fflate
    echo '<script type="text/javascript" src="' . get_config('geogebra', 'fflate') . '"></script>';

    // Check if the activity has a custom URL for deploy ggb
    $deployggburl = !empty($geogebra->urlggb) ? $geogebra->urlggb : get_config('geogebra', 'deployggb');

    // Add loading of GeoGebra
    echo '<script type="text/javascript" src="' . $deployggburl . '"></script>';

    // Get activity width
    $width = $geogebra->width === 0 ? '100%' : $geogebra->width . 'px';

    echo '<script>window.onload = function() {
        var applet = new GGBApplet({';
    foreach ($attribnames as $name) {
        echo $name.': '.geogebra_get_script_param($name, $attributes).',';
    }
    foreach ($attribs as $name => $value) {
        echo $name.': "'.$value.'",';
    }
    echo '}, true);
        applet.inject("applet_container", "preferHTML5");
    }
    </script>
    <div id="applet_container" style="width: ' . $width .'; height: ' . $geogebra->height . 'px;"></div>';

    // Include also javascript code from GGB file
    geogebra_get_js_from_geogebra($context, $geogebra);

}

/**
 * Execute Javascript that is embedded in the geogebra file, if it exists
 * File must be named geogebra_javascript.js
 * @param  object $context  Of the activity to get the files
 * @param  object $geogebra object with the activity info
 */
function geogebra_get_js_from_geogebra($context, $geogebra) {
    global $CFG;

    $content = false;

    if (geogebra_is_valid_external_url($geogebra->url)) {
        require_once("$CFG->libdir/filestorage/zip_packer.php");
        // Prepare tmp dir (create if not exists, download ggb file...)
        $dirname = 'mod_geogebra_'.time().rand(0, 9999);
        $tmpdir = make_temp_directory($dirname);
        if (!$tmpdir) {
            debugging("Cannot create temp directory $dirname");
            return;
        }

        $materialid = geogebra_get_id($geogebra->url);
        if ($materialid) {
            $ggbfile = "https://tube.geogebra.org/material/download/format/file/id/$materialid";
        } else {
            $ggbfile = $geogebra->url;
        }
        $filename = pathinfo($ggbfile, PATHINFO_FILENAME);
        $tmpggbfile = tempnam($tmpdir, $filename.'_');

        // Download external GGB and extract javascript file
        if (!download_file_content($ggbfile, null, null, false, 300, 20, false, $tmpggbfile)) {
            debugging("Error copying from $ggbfile");
            return;
        }

        // Extract geogebra js from GGB file
        $zip = new zip_packer();
        $extract = $zip->extract_to_pathname($tmpggbfile, $tmpdir, array('geogebra_javascript.js'));
        if ($extract && $extract['geogebra_javascript.js']) {
            unlink($tmpggbfile);
        } else {
            @unlink($tmpggbfile);
            @rmdir($tmpdir);
            debugging("Cannot open zipfile $tmpggbfile");
            return;
        }

        $content = file_get_contents($tmpdir.'/geogebra_javascript.js');

        // Delete temporary files
        unlink($tmpdir.'/geogebra_javascript.js');
        rmdir($tmpdir);
    } else {
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_geogebra', 'extracted_files', 0, '/', 'geogebra_javascript.js');
        if ($file) {
            $content = $file->get_content();
        }
    }

    if (empty($content)) {
        //debugging("Empty content");
        return;
    }

    // Modified: 20/10/2021
    // Global variable `ggbApplet` not yet used
    echo '<script type="text/javascript">
    if (typeof ggbApplet == \'undefined\') {
        ggbApplet = document.ggbApplet;
    }
    ' . $content . '</script>';
}

/**
 * Returns a link with info about the state of the geogebra attempts
 *
 * This is used by view_header to put this link at the top right of the page.
 * For teachers it gives the number of attempted geogebras with a link
 * For students it gives the time of their last attempt.
 *
 * @global object
 * @global object
 * @param bool $allgroup print all groups info if user can access all groups, suitable for index.php
 * @return string
 */
function geogebra_submittedlink($allgroups = false) {
    global $USER;
    global $CFG;

    $submitted = '';
    $urlbase = "{$CFG->wwwroot}/mod/geogebra/";

    /*      $context = context_module::instance($this->cm->id);
        if (has_capability('moodle/grade:viewall', $context)) {
            if ($allgroups and has_capability('moodle/site:accessallgroups', $context)) {
                $group = 0;
            } else {
                $group = groups_get_activity_group($this->cm);
            }
            $submitted = 'teacher';
        } else {
            if (isloggedin()) {
                $submitted = 'student';
            }
        }
    */
    return $submitted;
}

/**
 * Get moodle server
 *
 * @return string                myserver.com:port
 */
function geogebra_get_server() {
    global $CFG, $OUTPUT;

    if (!empty($CFG->wwwroot)) {
        $url = parse_url($CFG->wwwroot);
    }

    if (!empty($url['host'])) {
        $hostname = $url['host'];
    } else if (!empty($_SERVER['SERVER_NAME'])) {
        $hostname = $_SERVER['SERVER_NAME'];
    } else if (!empty($_ENV['SERVER_NAME'])) {
        $hostname = $_ENV['SERVER_NAME'];
    } else if (!empty($_SERVER['HTTP_HOST'])) {
        $hostname = $_SERVER['HTTP_HOST'];
    } else if (!empty($_ENV['HTTP_HOST'])) {
        $hostname = $_ENV['HTTP_HOST'];
    } else {
        $OUTPUT->notification('Warning: could not find the name of this server!');
        return false;
    }

    if (!empty($url['port'])) {
        $hostname .= ':'.$url['port'];
    } else if (!empty($_SERVER['SERVER_PORT'])) {
        if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
            $hostname .= ':'.$_SERVER['SERVER_PORT'];
        }
    }

    return $hostname;
}

/**
 * Get moodle path
 *
 * @return string /path_to_moodle
 */
function geogebra_get_path() {
    global $CFG;

        $path = '/';
    if (!empty($CFG->wwwroot)) {
        $url = parse_url($CFG->wwwroot);
        if (array_key_exists('path', $url)) {
            $path = $url['path'];
        }
    }
    return $path;
}

function geogebra_get_filemanager_options() {
    $options = array();
    $options['return_types'] = 3;  // 3 == FILE_EXTERNAL & FILE_INTERNAL. These two constant names are defined in repository/lib.php
    $options['accepted_types'] = 'archive';
    $options['accepted_types'] = '*'; // array('.ggb');
    $options['maxbytes'] = 0;
    $options['subdirs'] = 0;
    $options['maxfiles'] = 1;
    return $options;
}

function geogebra_set_mainfile($data) {
    $cmid = $data->coursemodule;
    $draftitemid = $data->url;

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_geogebra', 'content', 0, geogebra_get_filemanager_options());
    }

    $filename = geogebra_extract_package($cmid);

    /* Codi antic
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_geogebra', 'content', 0, 'sortorder', false);
        if (count($files) == 1) {
            // only one file attached, set it as main file automatically
            $file = reset($files);
            file_set_sortorder($context->id, 'mod_geogebra', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);
            $filename = $file->get_filename();
        }
    */
    return $filename;
}

/**
 * Extracts GGB package, sets up all variables.
 * @param int $cmid
 * @return filename
 */
function geogebra_extract_package($cmid) {
    global $DB;

    $fs = get_file_storage();
    $context = context_module::instance($cmid);
    $files = $fs->get_area_files($context->id, 'mod_geogebra', 'content', 0, 'sortorder', false);
    if (count($files) == 1) {
        // only one file attached, set it as main file automatically
        $package = reset($files);
        file_set_sortorder($context->id, 'mod_geogebra', 'content', 0, $package->get_filepath(), $package->get_filename(), 1);
        $filename = $package->get_filename();

        // Extract files
        $fs->delete_area_files($context->id, 'mod_geogebra', 'extracted_files');

        $packer = get_file_packer('application/zip');
        $package->extract_to_storage($packer, $context->id, 'mod_geogebra', 'extracted_files', 0, '/');
    }

    return $filename;
}

function geogebra_is_valid_external_url($url) {
    // URL of form geogebra.org/m/<id> is invalid.
    if (preg_match('/^(http:\/\/|https:\/\/)([www\.]*)(geogebra\.org\/m\/)[a-z\d;:@&%=+\/\$_.-]*$/i',$url) === 1) {
        \core\notification::warning(get_string('invalidurl', 'geogebra'));
        $result = 0;
    } else {
        // Other resources
        $result = preg_match('/(http:\/\/|https:\/\/|www).*\/*(\?[a-z+&\$_.-][a-z\d;:@&%=+\/\$_.-]*)?$/i', $url);
    }
    return $result;
}

function geogebra_is_valid_file($filename) {
    return preg_match('/.ggb$/i', $filename);
}

// Attempts

/**
 * Convert specified time (in milliseconds) to XX' YY'' format
 *
 * @param type $time time (in milliseconds) to format
 */
function geogebra_format_time($time) {
    return floor($time / 60000)."' ".round(fmod($time, 60000) / 1000, 0)."''";
}

/**
 * Format time from milliseconds to string
 *
 * @return string Formated string [x' y''], where x are the minutes and y are the seconds.
 * @param int $time	The time (in ms)
 */
function geogebra_time2str($time) {
    return floor($time/60)."' ".round(fmod($time,60),0)."''";
}

function geogebra_view_results($geogebra, $context, $cm, $course, $action) {
    global $CFG, $DB, $OUTPUT, $PAGE, $USER;

    // Show students list with their results
    require_once($CFG->libdir.'/gradelib.php');
    $perpage = optional_param('perpage', 10, PARAM_INT);
    $perpage = ($perpage <= 0) ? 10 : $perpage;
    $page    = optional_param('page', 0, PARAM_INT);

    // Find out current groups mode
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm, true);

    // Get all ppl that are allowed to submit geogebra
    list($esql, $params) = get_enrolled_sql($context, 'mod/geogebra:submit', $currentgroup);
    $sql = "SELECT u.id FROM {user} u ".
           "LEFT JOIN ($esql) eu ON eu.id=u.id ".
           "WHERE u.deleted = 0 AND eu.id=u.id ";

    $users = $DB->get_records_sql($sql, $params);
    if (!empty($users)) {
        $users = array_keys($users);
    }

    // If groupmembersonly used, remove users who are not in any group
    if ($users and !empty($CFG->enablegroupmembersonly) and $cm->groupmembersonly) {
        if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id')) {
            $users = array_intersect($users, array_keys($groupingusers));
        }
    }


    // TODO: Review to show all users information
    if (!empty($users)) {

        // Create results table
        $extrafields = get_extra_user_fields($context);
        $tablecolumns = array_merge(array('picture', 'fullname'), $extrafields,
                array('attempts', 'duration', 'grade', 'comment', 'datestudent', 'dateteacher', 'status'));

        $extrafieldnames = array();
        foreach ($extrafields as $field) {
            $extrafieldnames[] = get_user_field_name($field);
        }

        $tableheaders = array_merge(
                array('', get_string('fullnameuser')),
                $extrafieldnames,
                array(
                    get_string('attempts', 'geogebra'),
                    get_string('duration', 'geogebra'),
                    get_string('grade'),
                    get_string('comment', 'geogebra'),
                    get_string('lastmodifiedsubmission', 'geogebra'),
                    get_string('lastmodifiedgrade', 'geogebra'),
                    get_string('status', 'geogebra'),
                ));

        require_once($CFG->libdir.'/tablelib.php');
        $table = new flexible_table('mod-geogebra-results');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/geogebra/report.php?id='.$cm->id.'&amp;currentgroup='.$currentgroup);

        $table->sortable(true, 'lastname'); // Sorted by lastname by default
        $table->collapsible(true);
        $table->initialbars(true);

        $table->column_suppress('picture');
        $table->column_suppress('fullname');

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        foreach ($extrafields as $field) {
            $table->column_class($field, $field);
        }

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'attempts');
        $table->set_attribute('class', 'results generaltable generalbox');
        $table->set_attribute('width', '100%');

        $table->no_sorting('attempts');
        $table->no_sorting('duration');
        $table->no_sorting('grade');
        $table->no_sorting('comment');
        $table->no_sorting('datestudent');
        $table->no_sorting('dateteacher');
        $table->no_sorting('status');

        // Start working -- this is necessary as soon as the niceties are over
        $table->setup();

        // Construct the SQL
        list($where, $params) = $table->get_sql_where();
        if ($where) {
            $where .= ' AND ';
        }

        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
        }

        $ufields = user_picture::fields('u', $extrafields);

        $select = "SELECT $ufields ";
        $sql = 'FROM {user} u WHERE '.$where.'u.id IN ('.implode(',', $users).') ';

        $ausers = $DB->get_records_sql($select.$sql.$sort, $params, $table->get_page_start(), $table->get_page_size());

        $table->pagesize($perpage, count($users));
        $offset = $page * $perpage; // Offset used to calculate index of student in that particular query, needed for the pop up to know who's next
        if ($ausers !== false) {
            // $grading_info = grade_get_grades($course->id, 'mod', 'geogebra', $geogebra->id, array_keys($ausers));
            foreach ($ausers as $auser) {
                $picture = $OUTPUT->user_picture($auser);
                $userlink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $auser->id . '&amp;course=' . $course->id . '">' .
                fullname($auser, has_capability('moodle/site:viewfullnames', $context)) . '</a>';

                $row = array($picture, $userlink);

                foreach ($extrafields as $field) {
                    $row[] = $auser->{$field};
                }

                // Attempts summary
                $attempts = geogebra_get_user_attempts($geogebra->id, $auser->id);
                $attemptssummary = geogebra_get_user_grades($geogebra, $auser->id);
                if ($attemptssummary) {
                    $row[] = $attemptssummary->attempts;
                    $row[] = geogebra_time2str($attemptssummary->duration);
                    $row[] = $attemptssummary->grade;
                    $rowclass = ($attemptssummary->attempts > 0)?'summary-row':"";
                } else {
                    $row[] = "";
                    $row[] = "";
                    $row[] = "";
                    $rowclass = "";
                }
                $row[] = "";
                $row[] = "";
                $row[] = "";
                $row[] = "";

                $table->add_data($row, $rowclass);

                // Show attempts information
                foreach ($attempts as $attempt) {
                    $row = array();
                    // In the attempts row, show only the summary of the attempt (it's not necessary to repeat user information)
                    for ($i = 0; $i < count($extrafields) + 2; $i++) {
                        array_push($row, '');
                    }
                    // Attempt information
                    $row = geogebra_get_attempt_row($geogebra, $attempt, $auser, $cm, $context, $row);
                    /*array_push($row, $attempt->duration);
                    array_push($row, $attempt->grade);
                    array_push($row, $attempt->comment);*/
                    $table->add_data($row);
                }
            }
        }
        $table->print_html();  // Print the whole table
    } else {
        echo $OUTPUT->notification(get_string('msg_nosessions', 'geogebra'), 'notifymessage');
    }

}

function geogebra_get_results_table_columns($cm = null) {
    // $tablecolumns = array('picture', 'fullname', 'attempts', 'duration', 'grade', 'comment', 'datestudent', 'dateteacher', 'status');
    // $tablecolumns = array('attempts', 'duration', 'grade', 'comment', 'datestudent', 'dateteacher', 'status');
    $tablecolumns = array('attempts', 'duration', 'grade');
    if (!empty($cm)) {
        array_push($tablecolumns, 'comment');
    }

    array_push($tablecolumns, 'datestudent');
    array_push($tablecolumns, 'dateteacher');

    if (!empty($cm)) {
        array_push($tablecolumns, 'status');
    }

    $tableheaders = array();

    foreach ($tablecolumns as $tablecolumn) {
        $tableheaders[] = get_string($tablecolumn, 'geogebra');
    }

    return array('tablecolumns' => $tablecolumns, 'tableheaders' => $tableheaders);
}

function geogebra_view_userid_results($geogebra, $userid, $cm, $context, $viewmode, $attempt = null) {
    global $CFG, $DB, $OUTPUT, $PAGE, $USER;

    require_once($CFG->libdir.'/tablelib.php');
    $table = new flexible_table('mod-geogebra-results');

    $user = $DB->get_record('user', array('id' => $userid));

    $tablecolumns = geogebra_get_results_table_columns($viewmode == 'view' ? null : $cm);
    $table->define_columns($tablecolumns['tablecolumns']);
    $table->define_headers($tablecolumns['tableheaders']);
    $table->define_baseurl($CFG->wwwroot.'/mod/geogebra/view.php?id='.$cm->id);

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'attempts');
    $table->set_attribute('class', 'results generaltable generalbox');
    $table->set_attribute('width', '100%');

    // Start working -- this is necessary as soon as the niceties are over
    $table->setup();

    // Construct the SQL
    list($where, $params) = $table->get_sql_where();
    if ($where) {
        $where .= ' AND ';
    }

    // $sort = ' ORDER BY attemptid';

    // Show results only for specified user
    if (!empty($attempt)) {
        // Show only results of specified attempt
        $table = new html_table();
        $table->size = array('10%', '90%');

        parse_str($attempt->vars, $parsedvars);
        $numattempt = $parsedvars['attempts'];
        if (!$attempt->finished) {
            $numattempt .= ' (' . get_string('unfinished', 'geogebra') . ')';
        }
        $duration = geogebra_time2str($parsedvars['duration']);
        $grade = $parsedvars['grade'];

        if ($grade < 0 ) {
            $grade = '';
        }
        if (is_siteadmin() || has_capability('moodle/grade:viewall', $context, $USER->id, false)) {
            // Show form to grade and comment this attempt
            require_once('gradeform.php');

            $data = new stdClass();
            $data->id = $cm->id;
            $data->student = $user->id;
            $data->attemptid = $attempt->id;
            $data->attempt = $numattempt;
            $data->duration = $duration;
            $data->grade = $grade;
            $data->comment_editor['text'] = $attempt->gradecomment;
            $data->comment_editor['format'] = FORMAT_HTML;

            // Create form
            $mform = new mod_geogebra_grade_form(null, array($geogebra, $data, null), 'post', '', array('class' => 'gradeform'));
            $action = optional_param('action', false, PARAM_TEXT);
            if ($action == 'submitgrade') {
                // Upgrade submitted grade
                $grade = optional_param('grade', '', PARAM_INT);
                $gradecomment = optional_param_array('comment_editor', '', PARAM_RAW);
                $attemptid = optional_param('attemptid', '', PARAM_INT);
                $attempt = geogebra_get_attempt($attemptid);
                parse_str($attempt->vars, $parsedvars);
                $parsedvars['grade'] = $grade;
                $attempt->vars = http_build_query($parsedvars, '', '&');

                if ($formdata = $mform->get_data()) {
                    // TODO: Use $formdata insteadof optional_param
                    geogebra_update_attempt($attemptid, $attempt->vars, GEOGEBRA_UPDATE_TEACHER, $gradecomment['text']);
                }
            }

        } else {
            if ($geogebra->grade < 0 ) {
                // Get scale name
                $grademenu = make_grades_menu($geogebra->grade);
                if (!empty($grade)) {
                    $grade = $grademenu[$grade];
                }
            }
            // Show attempt
            geogebra_add_table_row_tuple($table, get_string('attempt', 'geogebra'), $numattempt);
            geogebra_add_table_row_tuple($table, get_string('duration', 'geogebra'), $duration);
            geogebra_add_table_row_tuple($table, get_string('grade'), $grade);
            geogebra_add_table_row_tuple($table, get_string('comment', 'geogebra'), $attempt->gradecomment);
        }

        // Print attempt information with grade and comment form if user can grade
        if (!empty($mform)) {
            // Print user information
            $picture = $OUTPUT->user_picture($user);
            $userlink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&amp;course=' . $geogebra->course . '">'
                . fullname($user, has_capability('moodle/site:viewfullnames', $context)) . '</a>';
            //echo $picture.' '.$userlink.' ('.$user->email.')';
            echo $picture.' '.$userlink;
            // Print form
            $mform->display();
        } else {
            echo html_writer::table($table);
        }

    } else {
        // Show all attempts information
        $attempts = geogebra_get_user_attempts($geogebra->id, $user->id);
        foreach ($attempts as $attempt) {
            $row = geogebra_get_attempt_row($geogebra, $attempt, $user, $cm, $context);
            $rowclass = '';
            $table->add_data($row, $rowclass);
        }
        $table->print_html();  // Print the whole table
    }

}

/**
 *
 * @global type $CFG
 * @param type $attempt
 * @param type $user
 * @param type $cm
 * @param array $row
 * @return array
 */
function geogebra_get_attempt_row($geogebra, $attempt, $user, $cm = null, $context = null, $row = null) {
    global $CFG, $USER;

    if (empty($row)) {
        $row = array();
    }
    parse_str($attempt->vars, $parsedvars);
    $numattempt = $parsedvars['attempts'];
    if (!$attempt->finished) {
        $numattempt .= ' (' . get_string('unfinished', 'geogebra') . ')';
    }
    array_push($row, $numattempt);
    $duration = geogebra_time2str($parsedvars['duration']);
    array_push($row, $duration);
    $grade = $parsedvars['grade'];
    if ($grade < 0 ) {
        $grade = '-';
    } else if ($geogebra->grade < 0 ) {
        // Get scale name
        $grademenu = make_grades_menu($geogebra->grade);
        $grade = $grademenu[$grade];
    }
    array_push($row, $grade);
    // $row = array($numattempt, $duration, $grade, $gradecomment, $datestudent, $dateteacher);
    if (!empty($cm)) {
        $gradecomment = !empty($attempt->gradecomment) ? shorten_text(trim(strip_tags(format_text($attempt->gradecomment))), 25) : '';
        array_push($row, $gradecomment);
    }
    $datestudent = !empty($attempt->datestudent) ? userdate($attempt->datestudent) : '';
    array_push($row, $datestudent);
    $dateteacher = !empty($attempt->dateteacher) ? userdate($attempt->dateteacher) : '';
    array_push($row, $dateteacher);
    if (!empty($cm)) {
        $textlink = get_string('viewattempt', 'geogebra');
        if (is_siteadmin() || has_capability('moodle/grade:viewall', $context, $USER->id, false)) {
            if ($attempt->dateteacher < $attempt->datestudent ) {
                $textlink = '<span class="pendinggrade" >'. get_string('grade'). '</span>';
            } else {
                $textlink = get_string('update');
            }
        }
        $status = '<a href="' . $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id . '&student=' . $user->id .'&attemptid='.$attempt->id.'"> ' . $textlink . '</a>';
        array_push($row, $status);
    }
    return $row;
}

function geogebra_add_table_row_tuple(html_table $table, $first, $second) {
    $row = new html_table_row();
    $cell1 = new html_table_cell('<b>'.$first.'</b>');
    $cell2 = new html_table_cell($second);
    $row->cells = array($cell1, $cell2);
    $table->data[] = $row;
}

/**
 * Workaround to fix an Oracle's bug when inserting a row with date
 */
function geogebra_normalize_date () {
    global $CFG, $DB;
    if ($CFG->dbtype == 'oci') {
        $sql = "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'";
        $DB->execute($sql);
    }
}

/**
 * Count the finished attempts done by the $userid
 */
function geogebra_count_finished_attempts($geogebraid, $userid) {
    global $DB;

    return $DB->count_records('geogebra_attempts', array('userid' => $userid, 'geogebra' => $geogebraid, 'finished' => '1'));
}

/**
 * Return the unfinished attempt of a user. Only 1 attempt for each (user, geogebra) can be unfinished
 *
 * @param type $geogebra_id ID of an instance of this module
 * @param type $user_id ID of a user
 * @return mixed null/geogebra attempt object
 */
function geogebra_get_unfinished_attempt($geogebraid, $userid) {
    global $DB;

    return $DB->get_record('geogebra_attempts', array('userid' => $userid, 'geogebra' => $geogebraid, 'finished' => '0'));
}

/**
 * Returns a geogebra attempt
 *
 * @param int $attemptid ID of the attempt
 * @return object attempt
 */
function geogebra_get_attempt($attemptid) {
    global $DB;

    return $DB->get_record('geogebra_attempts', array('id' => $attemptid));
}

/**
 * Returns all attempts from specified user
 *
 * @param int $userid ID of the user
 * @return array object attempt
 */
function geogebra_get_user_attempts($geogebraid, $userid) {
    global $DB;

    return $DB->get_records('geogebra_attempts', array('geogebra' => $geogebraid, 'userid' => $userid), 'datestudent ASC');
}

/**
 * Creates a new geogebra attempt for specified user
 *
 * @param int $geogebraid ID of the GeoGebra activity
 * @param int $userid ID of user who has done the attempt
 * @param string $vars Attempt vars to be created
 * @param boolean $finished Attempt finished/unfinished
 * @return boolean Success/Fail
 */
function geogebra_add_attempt($geogebraid, $userid, $vars, $finished = 1) {
    global $DB;

    $attempt = new stdClass();
    $attempt->geogebra = $geogebraid;
    $attempt->userid = $userid;
    $attempt->vars = $vars;
    $attempt->finished = $finished;
    $attempt->datestudent = time();

    $DB->insert_record('geogebra_attempts', $attempt);
    $geogebra = $DB->get_record('geogebra', array('id' => $geogebraid));
    geogebra_update_grades($geogebra, $userid);

    return true;
}

/**
 * Updates an existing intance of a geogebra attempt
 * with the new data.
 *
 * @param int $attemptid ID of the attempt to be updated
 * @param string $vars Attempt vars to be updated
 * @param string $gradecomment Comment to the grade
 * @param boolean $finished Attempt finished/unfinished
 * @return boolean Success/Fail
 */
function geogebra_update_attempt($attemptid, $vars, $actionby, $gradecomment = null, $finished = 1) {
    global $DB;

    $attempt = new stdClass();
    $attempt->id = $attemptid;
    $attempt->vars = $vars;
    $attempt->gradecomment = $gradecomment;
    $attempt->finished = $finished;
    // Modified by student or teacher
    if ($actionby == GEOGEBRA_UPDATE_STUDENT) {
        $attempt->datestudent = time();
    } else if ($actionby == GEOGEBRA_UPDATE_TEACHER) {
        $attempt->dateteacher = time();
    }

    if ($DB->update_record('geogebra_attempts', $attempt) !== false) {
        $attempt = $DB->get_record('geogebra_attempts', array('id' => $attemptid));
        $geogebra = $DB->get_record('geogebra', array('id' => $attempt->geogebra));
        geogebra_update_grades($geogebra, $attempt->userid);
    } else {
        return false;
    }

    return true;
}

function geogebra_get_tabs($cm, $action = null, $cangrade = false) {
    global $CFG;

    if ($cangrade) {
        $tabs[] = new tabobject('view', $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id.'&action=preview', get_string('previewtab', 'geogebra'));
    } else {
        $tabs[] = new tabobject('view', $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id, get_string('viewtab', 'geogebra'));
    }

    $tabs[] = new tabobject('result', $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id.'&action=result', get_string('resultstab', 'geogebra'));

    if ($action == 'view') {
        $tabs[] = new tabobject('viewattempt', $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id.'&action=view', get_string('viewattempttab', 'geogebra'));
    }

    return array($tabs);
}

/**
 * Update geogebra object specified to include in the attributes field all the information
 *
 * @param type $geogebra
 */
function geogebra_update_attributes(&$geogebra) {
    $geogebra->attributes = http_build_query(array(
        'enableRightClick' => isset($geogebra->enableRightClick) && $geogebra->enableRightClick,
        'enableLabelDrags' => isset($geogebra->enableLabelDrags) && $geogebra->enableLabelDrags,
        'showResetIcon' => isset($geogebra->showResetIcon) && $geogebra->showResetIcon,
        'showMenuBar' => isset($geogebra->showMenuBar) && $geogebra->showMenuBar,
        'showToolBar' => isset($geogebra->showToolBar) && $geogebra->showToolBar,
        'showToolBarHelp' => isset($geogebra->showToolBarHelp) && $geogebra->showToolBarHelp,
        'showAlgebraInput' => isset($geogebra->showAlgebraInput) && $geogebra->showAlgebraInput,
        'useBrowserForJS' => isset($geogebra->useBrowserForJS) && $geogebra->useBrowserForJS,
        'language' => isset($geogebra->language) && $geogebra->language ? $geogebra->language : false
            ), '', '&');

    $geogebra->showsubmit = isset($geogebra->showsubmit);
}

/**
 * Calculates number of attempts, the average grade and average duration
 * of all attemps for a given user and geogebra
 *
 * @param int $geogebraid ID of an instance of this module
 * @param int $userid ID of a user
 * @return mixed boolean/object with userid, grade, rawgrade, grademax, attempts, duration and date
 */
function geogebra_get_nograding_grade($geogebraid, $userid) {
    global $DB;

    if ($attempts = $DB->get_records('geogebra_attempts', array('userid' => $userid, 'geogebra' => $geogebraid, 'finished' => 1))) {
        $count = 0;
        $durationsum = 0;
        foreach ($attempts as $attempt) {
            $count++;
            parse_str($attempt->vars, $parsedvars);
            $durationsum += $parsedvars['duration'];
        }

        $result->userid = $userid;
        $result->grade = 0;
        $result->rawgrade = 0;
        $result->attempts = $count; // TODO: Review (comment from Moodle 1.9)
        $result->duration = round($durationsum, 2);
        $result->dateteacher = '';
        $result->datestudent = '';

        return $result;
    } else {
        return false;
    }
}

/**
 * Calculates number of attempts, the average grade and average duration
 * of all attemps for a given user and geogebra
 *
 * @param int $geogebraid ID of an instance of this module
 * @param int $userid ID of a user
 * @return mixed boolean/object with userid, grade, rawgrade, grademax, attempts, duration and date
 */
function geogebra_get_average_grade($geogebraid, $userid) {
    global $DB;

    if ($attempts = $DB->get_records('geogebra_attempts', array('userid' => $userid, 'geogebra' => $geogebraid, 'finished' => 1))) {
        $result = new stdClass();
        $durationsum = 0;
        $gradessum = 0;
        $count = 0;
        foreach ($attempts as $attempt) {
            parse_str($attempt->vars, $parsedvars);
            if ($parsedvars['grade'] >= 0) { // Only attempt with valid grade
                $gradessum += $parsedvars['grade'];
            }
            $count++;
            $durationsum += $parsedvars['duration'];
        }
        if ($count > 0) {
            $result->userid = $userid;
            $result->grade = round($gradessum / $count, 2);
            $result->rawgrade = $result->grade;
            $result->attempts = count($attempts); // TODO: Review (comment from Moodle 1.9)
            $result->duration = round($durationsum / $count, 2);
            $result->dateteacher = '';
            $result->datestudent = '';

            return $result;
        } else {
            $result->userid = $userid;
            $result->grade = '';
            $result->rawgrade = '';
            $result->attempts = count($attempts);
            $result->duration = '';
            $result->dateteacher = '';
            $result->datestudent = '';

            return $result;
        }
    } else {
        return false;
    }
}

/**
 * Finds the last attempt for a given user and geogebra.
 *
 * @param int $geogebraid ID of an instance of this module
 * @param int $userid ID of a user
 * @return mixed boolean/object with userid, grade, rawgrade, grademax, attempts, duration and date
 */
function geogebra_get_last_attempt_grade($geogebraid, $userid) {
    global $DB;

    $sql = 'SELECT * '
          .'FROM {geogebra_attempts} '
          .'WHERE datestudent = (SELECT MAX(datestudent) FROM {geogebra_attempts} '
                               .'WHERE userid = ' . $userid . ' AND geogebra = ' . $geogebraid . ' AND finished = 1)';

    if ($attempt = $DB->get_record_sql($sql)) {
        if (empty($attempt)) {
            return false;
        }
        parse_str($attempt->vars, $parsedvars);
        if ($parsedvars['grade'] < 0) { // Last attempt not graded
            $result->userid = $userid;
            $result->grade = '';
            $result->gradecomment = '';
            $result->rawgrade = '';
            $result->attempts = geogebra_count_finished_attempts($geogebraid, $userid);
            $result->duration = '';
            $result->dateteacher = '';
            $result->datestudent = '';

            return $result;
        } else {
            $result->userid = $userid;
            $result->grade = $parsedvars['grade'];
            $result->gradecomment = $attempt->gradecomment;
            $result->rawgrade = $result->grade;
            $result->attempts = geogebra_count_finished_attempts($geogebraid, $userid);
            $result->duration = $parsedvars['duration'];
            $result->dateteacher = $attempt->dateteacher;
            $result->datestudent = $attempt->datestudent;

            return $result;
        }
    } else {
        return false;
    }
}

/**
 * Finds the firt attempt for a given user and geogebra.
 *
 * @param int $geogebraid ID of an instance of this module
 * @param int $userid ID of a user
 * @return mixed boolean/object with userid, grade, rawgrade, grademax, attempts, duration and date
 */
function geogebra_get_first_attempt_grade($geogebraid, $userid) {
    global $DB;

    $sql = 'SELECT * '
          .'FROM {geogebra_attempts} '
          .'WHERE datestudent = (SELECT MIN(datestudent) FROM {geogebra_attempts} '
                               .'WHERE userid = ' . $userid . ' AND geogebra = ' . $geogebraid . ' AND finished = 1)';

    if ($attempt = $DB->get_record_sql($sql)) {
        if (empty($attempt)) {
            return false;
        }
        parse_str($attempt->vars, $parsedvars);
        if ($parsedvars['grade'] < 0) { // First attempt not graded
            $result->userid = $userid;
            $result->grade = '';
            $result->gradecomment = '';
            $result->rawgrade = '';
            $result->attempts = geogebra_count_finished_attempts($geogebraid, $userid);
            $result->duration = '';
            $result->dateteacher = '';
            $result->datestudent = '';

            return $result;
        } else {

            $result->userid = $userid;
            $result->grade = $parsedvars['grade'];
            $result->gradecomment = $attempt->gradecomment;
            $result->rawgrade = $result->grade;
            $result->attempts = geogebra_count_finished_attempts($geogebraid, $userid);
            $result->duration = $parsedvars['duration'];
            $result->dateteacher = $attempt->dateteacher;
            $result->datestudent = $attempt->datestudent;

            return $result;
        }
    } else {
        return false;
    }
}

/**
 * Finds the attempt with the highest grade for a given user and geogebra.
 * If more than one attempt has the same grade, gets one with less duration.
 *
 * @param int $geogebraid ID of an instance of this module
 * @param int $userid ID of a user
 * @return mixed boolean/object with userid, grade, rawgrade, grademax, attempts, duration and date
 */
function geogebra_get_highest_attempt_grade($geogebraid, $userid) {
    global $DB;

    // 1. First get all attemps
    if ($attempts = $DB->get_records('geogebra_attempts', array('userid' => $userid, 'geogebra' => $geogebraid, 'finished' => 1))) {
        // 2. Get highest graded attempt
        $maxgrade = 0;
        $maxattempt = null;
        $mintime = PHP_INT_MAX;

        foreach ($attempts as $attempt) {

            parse_str($attempt->vars, $parsedvars);

            if ($parsedvars['grade'] >= 0) { // Only attempt with valid grade
                if ($parsedvars['grade'] > $maxgrade) { // Highter grade
                    $maxattempt = $attempt;
                    $maxgrade = $parsedvars['grade'];
                    $mintime = $parsedvars['duration'];
                } else if ($parsedvars['grade'] == $maxgrade) { // If same grade,
                    if ($parsedvars['duration'] < $mintime) { // get the faster attempt
                        $maxattempt = $attempt;
                        $maxgrade = $parsedvars['grade'];
                        $mintime = $parsedvars['duration'];
                    }
                }
            }
        }
        // 3. Prepare return values
        $result = new stdClass();
        if (isset($maxattempt)) {
            parse_str($maxattempt->vars, $parsedvars);
            $result->userid = $userid;
            $result->grade = $parsedvars['grade'];
            $result->rawgrade = $result->grade;
            $result->gradecomment = $maxattempt->gradecomment;
            $result->attempts = count($attempts);
            $result->duration = $parsedvars['duration'];
            $result->dateteacher = $maxattempt->dateteacher;
            $result->datestudent = $maxattempt->datestudent;

            return $result;
        } else {
            $result->userid = $userid;
            $result->grade = '';
            $result->rawgrade = '';
            $result->gradecomment = '';
            $result->attempts = count($attempts);
            $result->duration = '';
            $result->dateteacher = '';
            $result->datestudent = '';

            return $result;
        }
    } else {
        return false;
    }
}

/**
 * Finds the attempt with the lowest grade for a given user and geogebra.
 * If more than one attempt has the same grade, gets the one with more duration.
 *
 * @param int $geogebraid ID of an instance of this module
 * @param int $userid ID of a user
 * @return mixed boolean/object with userid, grade, rawgrade, grademax, attempts, duration and date
 */
function geogebra_get_lowest_attempt_grade($geogebraid, $userid) {
    global $DB;

    // 1. First get all attemps
    if ($attempts = $DB->get_records('geogebra_attempts', array('userid' => $userid, 'geogebra' => $geogebraid, 'finished' => 1))) {
        // 2. Get highest graded attempt
        $mingrade = PHP_INT_MAX;
        $minattempt = null;
        $maxtime = 0;

        foreach ($attempts as $attempt) {

            parse_str($attempt->vars, $parsedvars);
            if ($parsedvars['grade'] >= 0) { // Only attempt with valid grade
                if ($parsedvars['grade'] < $mingrade) { // Lowest grade
                    $minattempt = $attempt;
                    $mingrade = $parsedvars['grade'];
                    $maxtime = $parsedvars['duration'];
                } else if ($parsedvars['grade'] == $mingrade) { // If same grade,
                    if ($parsedvars['duration'] > $maxtime) { // get the faster attempt
                        $minattempt = $attempt;
                        $mingrade = $parsedvars['grade'];
                        $maxtime = $parsedvars['duration'];
                    }
                }
            }
        }

        // 3. Prepare return values
        if (isset($minattempt)) {
            parse_str($minattempt->vars, $parsedvars);

            $result->userid = $userid;
            $result->grade = $parsedvars['grade'];
            $result->rawgrade = $result->grade;
            $result->gradecomment = $minattempt->gradecomment;
            $result->attempts = count($attempts);
            $result->duration = $parsedvars['duration'];
            $result->dateteacher = $minattempt->dateteacher;
            $result->datestudent = $minattempt->datestudent;

            return $result;
        } else {
            $result->userid = $userid;
            $result->grade = '';
            $result->rawgrade = '';
            $result->gradecomment = '';
            $result->attempts = count($attempts);
            $result->duration = '';
            $result->dateteacher = '';
            $result->datestudent = '';

            return $result;
        }
    } else {
        return false;
    }
}
