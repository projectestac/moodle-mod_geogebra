<?php

/**
 * Prints the details of a single attempt, allows to comment and manually grade 
 * and show the applet in the state the student sent it.
 *
 * @param TODO
 * 
 */
function showDetailTable($attempt, $context, $navlinks, $geogebra, $cm, $course, $parsedVars, $attemptnumber) {

    $title = get_string('report', 'geogebra') . ' ' . $geogebra->name . ' ' . get_string('for', 'geogebra') . ' ' . $attempt->user_firstname . ' ' . $attempt->user_lastname;

    if ($geogebra->grade != GEOGEBRA_NO_GRADING) {
        if ($geogebra->grade > 0) {
            $max = '/' . $geogebra->grade;
        } else {
            $max = '';
        }
        $table->head = array(get_string('attempt', 'geogebra'), get_string('duration', 'geogebra'), get_string('grade', 'geogebra') . $max, get_string('date'));
        $table->align = array('center', 'center', 'center', 'center');
    } else {
        $table->head = array(get_string('attempt', 'geogebra'), get_string('duration', 'geogebra'), get_string('date'));
        $table->align = array('center', 'center', 'center');
    }
    parse_str($attempt->vars, $parsedVars);

    $grade = $parsedVars['grade'];

    if ($attempt->finished == 0) {
        if (!has_capability('mod/geogebra:viewreports', $context)) { //student: Don't show grade if attempt is unfinished
            $grade = '-';
        } else {
            $grade = $grade;
        }
        $attemptnumber = $attemptnumber . ' (' . get_string('unfinished', 'geogebra') . ')';
    } else if ($grade > 100) {
        $grade = 100;
    }

    if ($geogebra->grade != GEOGEBRA_NO_GRADING) {
        $table->data = array(array(
                $attemptnumber,
                htmlentities(geogebra_time2str($parsedVars['duration']), ENT_QUOTES, 'UTF-8'),
                ($grade == -1) ? get_string('ungraded', 'geogebra') : $grade,
                userdate($attempt->date)
                ));
    } else {
        $table->data = array(array(
                $attemptnumber,
                htmlentities(geogebra_time2str($parsedVars['duration']), ENT_QUOTES, 'UTF-8'),
                userdate($attempt->date)
                ));
    }

    print_heading($title);
    print_table($table);
    $questionsNumber = isset($parsedVars['grades']) ? count($parsedVars['grades']) : 0;

    if ($questionsNumber > 0) {
        echo '</br>';
        if ($geogebra->grade > 0) {
            $max = '/' . $geogebra->grade;
        } else {
            $max = '';
        }
        $table_detailed->head = array(get_string('description', 'geogebra'), get_string('grade', 'geogebra') . $max, get_string('weight', 'geogebra'));
        $table_detailed->align = array('center', 'center', 'center');
        $table_detailed->data = array();

        for ($i = 0; $i < $questionsNumber; ++$i) {
            array_push($table_detailed->data, array(
                htmlentities($parsedVars['descs'][$i], ENT_QUOTES, 'UTF-8'),
                ($parsedVars['grades'][$i] * 10) . '%',
                ($parsedVars['weights'][$i] * 100) . '%'
            ));
        }

        array_push($table_detailed->data, array(
            '<b>' . get_string('total', 'geogebra') . '</b>',
            '<b>' . $grade . '%</b>',
            ''
        ));

        print_table($table_detailed);
    }

    $usehtmleditor = false;
    if (has_capability('mod/geogebra:gradeactivity', $context) && $geogebra->grade != GEOGEBRA_NO_GRADING) {
        echo '<form id="grade_form" action="" method="POST" >';
        echo '<div class="gradecontent">';
        echo '<label for="menugrade">' . get_string('grade') . ' </label>';
        choose_from_menu(make_grades_menu($geogebra->grade), 'manualgrade', $parsedVars['grade'], get_string('nograde'), '', -1, false);
        echo '<br/>';

        echo '<div id="gradecommenthtmleditor" >';
        $usehtmleditor = can_use_html_editor();
        print_textarea($usehtmleditor, 14, 58, 0, 0, 'gradecomment', $attempt->gradecomment, $course->id);
        if ($usehtmleditor) {
            echo '<input type="hidden" name="format" value="' . FORMAT_HTML . '" />';
        }
        echo '</div>';
        echo '<input type="hidden" name="updated" value="1" />';
        echo '<input type="submit" value="' . get_string('save', 'geogebra') . '" />';
        echo ' </form>';
        echo '</div>';
    } else {
    //    echo '<br>';

        $table_comment->head = array(get_string('comment', 'geogebra'));
        $table_comment->align = array('left');
        $table_comment->data = array();
        $table_comment->width = '50%';
        $table_comment->class = 'commenttable generaltable';
        $table_detailed->tablealign = 'left';
        array_push($table_comment->data, array(
            format_text($attempt->gradecomment, FORMAT_HTML)
        ));
         print_table($table_comment);
    //    print_box(format_text($attempt->gradecomment, FORMAT_HTML), 'generalbox boxwidthwide boxaligncenter', 'online');
    }

    parse_str($geogebra->url, $attributes);
    echo '<div class="gradecontent">';
    echo '<form id="geogebra_form" method="POST" >';
    geogebra_show_applet($geogebra, $attributes, $parsedVars);
    echo '</form>';
    echo '</div>';

    if ($usehtmleditor) {
        use_html_editor();
    }
}

/**
 * Prints the header of a single attempt page.
 * 
 * NOTE: May be merged with showDetailTable function
 *
 * @param TODO
 * 
 */
function geogebra_show_attempt($cm, $course, $context, $navlinks, $geogebra, $userid, $attemptid) {
    global $CFG;

    //We need to get all attempts in order to know the attempt number
    $select = 'SELECT ga.*, u.id AS user_id, u.firstname AS user_firstname, u.lastname AS user_lastname, u.picture, u.imagealt ';
    $from = ' FROM ' . $CFG->prefix . 'user u ' .
            'LEFT JOIN ' . $CFG->prefix . 'geogebra_attempts ga ON u.id = ga.userid ';
    $where = ' WHERE ga.userid = ' . $userid . ' AND ga.geogebra = ' . $geogebra->id;

    $attempts = get_records_sql($select . $from . $where);
    $attemptnumber = 0;

    //Search the needed attempt
    foreach ($attempts as $attempt) {
        $attemptnumber++;
        if ($attempt->id == $attemptid) {
            break;
        }
    }

    array_push($navlinks, array(
        'name' => $attempt->user_firstname . ' ' . $attempt->user_lastname,
        'link' => '',
        'type' => 'activityinstance'
    ));

    $navigation = build_navigation($navlinks);

    print_header_simple(
            format_string($geogebra->name), '', $navigation, '', '', true, update_module_button($cm->id, $course->id, get_string('modulename', 'geogebra')), navmenu($course, $cm)
    );

    print_tabs(array(
        array(
            new tabobject('view', $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id, get_string('viewtab', 'geogebra')),
            new tabobject('grade', $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $cm->id, get_string('resultstab', 'geogebra'))
        )
            ), '', 'grade');
    $parsedVars = null;
    showDetailTable($attempt, $context, $navlinks, $geogebra, $cm, $course, $parsedVars, $attemptnumber);
}

/**
 * Prints all the attempts of all the students for a given activity.
 *
 * @param TODO
 * 
 */
function geogebra_show_all_attempts($cm, $course, $context, $navlinks, $geogebra, $id, $student) {
    global $CFG;
    $navigation = build_navigation($navlinks);

    print_header_simple(
            format_string($geogebra->name), '', $navigation, '', '', true, update_module_button($cm->id, $course->id, get_string('modulename', 'geogebra')), navmenu($course, $cm)
    );

    print_tabs(array(
        array(
            new tabobject('view', $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id, get_string('viewtab', 'geogebra')),
            new tabobject('grade', $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $cm->id, get_string('resultstab', 'geogebra'))
        )
            ), 'grade');

    print_heading($geogebra->name);
    echo '<div class="mod-geogebra-intro">' . $geogebra->intro . '</div>';

    // Check to see if groups are being used in this assignment
    // find out current groups mode
    $groupmode = groups_get_activity_groupmode($cm);
    $currentgroup = groups_get_activity_group($cm, true);
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $cm->id);
    if (!empty($CFG->gradebookroles)) {
        $gradebookroles = explode(",", $CFG->gradebookroles);
    } else {
        $gradebookroles = '';
    }
    $users = get_role_users($gradebookroles, $context, true, '', 'u.lastname ASC', true, $currentgroup);
    if ($users) {
        $users = array_keys($users);
        if (!empty($CFG->enablegroupings) and $cm->groupmembersonly) {
            $groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id', 'u.id');
            if ($groupingusers) {
                $users = array_intersect($users, array_keys($groupingusers));
            }
        }
    }

    if (!empty($users)) {
        $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, u.imagealt ';
        $sql = 'FROM ' . $CFG->prefix . 'user u ' .
                'LEFT JOIN ' . $CFG->prefix . 'geogebra_attempts ga ON u.id = ga.userid ' .
                'WHERE u.id IN (' . implode(',', $users) . ') ' .
                'ORDER BY lastname, firstname';

        $users = get_records_sql($select . $sql);

        require_once($CFG->libdir . '/tablelib.php');
        $table = new flexible_table('mod-geogebra');

        $table = geogebra_define_table($table, $geogebra);

/*        if ($geogebra->maxattempts == 0) {

            //recorrer $users fent crida a geogebra_get_user_grades amb la nova 
            //opció geogebra_get_unique_attempt_grade
        } else {
            //recorrer $users, per a cadascun cridant a geogebra_show_user_attempts
            // després d'haverla modificat mostrar, a més del cada attempt, el resum
            //dels attempts (tal com es fa just aquí sota)
        }
*/
        foreach ($users as $user) {

            $any_unfinished = 0; //To show correct number of attempts when there is an unfinished one.
            if (geogebra_get_unfinished_attempt($geogebra->id, $user->id)) {
                $any_unfinished = 1;
            }

            $picture = print_user_picture($user, $course->id, $user->picture, false, true);
            $userlink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $user->id . '&amp;course=' . $course->id . '">' . fullname($user, has_capability('moodle/site:viewfullnames', $context)) . '</a>';

            $attemptsgrade = geogebra_get_user_grades($geogebra, $user->id);
            //Something to show?
            if ($attemptsgrade) {
                if ($geogebra->grade != GEOGEBRA_NO_GRADING) {
                    if ($geogebra->grade < 0 && $attemptsgrade->grade != '') { //if scale, show grade correctly
                        $finalgrade = grade_get_grades($course->id, 'mod', 'geogebra', $geogebra->id, $user->id);
                        $attemptsgrade->grade = $finalgrade->items[0]->grades[$user->id]->str_grade;
                    }
                    $row = array($picture, $userlink,
                        $attemptsgrade->attempts + $any_unfinished . '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->id . '"> ' . '(' . get_string('viewattempts', 'geogebra') . ')' . '</a>',
                        !empty($attemptsgrade->duration) ? geogebra_time2str($attemptsgrade->duration) : '',
                        '',
                        $attemptsgrade->grade,
                        !empty($attemptsgrade->date) ? userdate($attemptsgrade->date) : '',
                        '');
                    $table->add_data($row);
                } else {
                    $row = array($picture, $userlink,
                        $attemptsgrade->attempts + $any_unfinished . '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->id . '"> ' . '(' . get_string('viewattempts', 'geogebra') . ')' . '</a>',
                        geogebra_time2str($attemptsgrade->duration),
                        !empty($attemptsgrade->date) ? userdate($attemptsgrade->date) : '',
                        '');
                    $table->add_data($row);
                }

                if ($student == $user->id) {
                    geogebra_show_user_attempts($course, $geogebra, $user->id, $table, $id);
                }
            } else {
                // The user has not any finished attempt
                if ($geogebra->grade != GEOGEBRA_NO_GRADING) {
                    //Any unfinished??
                    if ($any_unfinished) {
                        $row = array($picture, $userlink,
                            $any_unfinished . '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->id . '"> ' . '(' . get_string('viewattempts', 'geogebra') . ')' . '</a>',
                            '',
                            '',
                            '',
                            '',
                            '');
                        $table->add_data($row);
                        if ($student == $user->id) {
                            geogebra_show_user_attempts($course, $geogebra, $user->id, $table, $id);
                        }
                    } else {
                        //Empty table
                        $row = array($picture, $userlink,
                            '',
                            '',
                            '',
                            '',
                            '',
                            '');
                        $table->add_data($row);
                    }
                } else {
                    if ($any_unfinished) {
                        array_push($table->data, array(
                            print_user_picture($user->id, $geogebra->course, NULL, 0, true) . ' ' . ' <a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->id . '">' . $user->firstname . ' ' . $user->lastname . '</a>',
                            htmlentities($any_unfinished, ENT_QUOTES, 'UTF-8'),
                            '',
                            ''
                        ));
                        if ($student == $user->id) {
                            geogebra_show_user_attempts($course, $geogebra, $user->id, $table, $id);
                        }
                    } else {
                        $row = array($picture, $userlink,
                            '',
                            '',
                            '',
                            ''
                        );
                        $table->add_data($row);
                    }
                }
            }
        }

        $table->print_html();
        //  print_table($table);
    } else {
        // The course has no users
        print_string('coursewithoutstudents', 'geogebra');
    }
}

/**
 * Adds to the table one row for each attempt of the given user.
 *
 * @param int $attemptid ID of the attempt
 * @return object attempt
 */
function geogebra_show_user_attempts($course, $geogebra, $userid, $table, $id) {
    global $CFG;

    $select = 'SELECT attempt.id AS attempt_id, user.id AS user_id, user.firstname AS user_firstname, user.lastname AS user_lastname,
        attempt.vars AS vars, attempt.gradecomment AS gradecomment, attempt.date AS date , attempt.finished AS finished';
    $from = ' FROM ' . $CFG->prefix . 'user user, ' . $CFG->prefix . 'geogebra_attempts attempt';
    $where = ' WHERE attempt.userid = user.id AND attempt.geogebra = ' . $geogebra->id . ' AND user.id = ' . $userid;
    $orderBy = ' ORDER BY date ASC';

    if (($attempts = get_records_sql($select . $from . $where . $orderBy)) !== false) {

        $i = 1;
        foreach ($attempts as $attempt) {
            parse_str($attempt->vars, $parsedVars);

            $grade = $parsedVars['grade'];

            $notice = '';
            if ($attempt->finished == 0) { //Don't show grade if attempt is unfinished
                $grade = '-';
                $notice = ' (' . get_string('unfinished', 'geogebra') . ')';
            } else if ($grade == -1) {
                $grade = get_string('ungraded', 'geogebra');
            } else if ($geogebra->grade < 0) {
                $finalgrade = grade_get_grades($course->id, 'mod', 'geogebra', $geogebra->id, $userid);
                $grade = $finalgrade->items[0]->grades[$userid]->str_grade;
            }

            if ($geogebra->grade != GEOGEBRA_NO_GRADING) {

                $row = array('', '',
                    $parsedVars['attempts'] . $notice,
                    geogebra_time2str($parsedVars['duration']),
                    !empty($attempt->gradecomment) ? shorten_text(trim(strip_tags(format_text($attempt->gradecomment))), 25) : '',
                    $grade,
                    !empty($attempt->date) ? userdate($attempt->date) : '',
                    '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $userid . '&attempt=' . $attempt->attempt_id . '"> ' . $state = get_string('gradeit', 'geogebra') . '</a>',
                );
                $table->add_data($row);
            } else {
                $row = array('', '',
                    $parsedVars['attempts'] . $notice,
                    geogebra_time2str($parsedVars['duration']),
                    !empty($attempt->date) ? userdate($attempt->date) : '',
                    '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $userid . '&attempt=' . $attempt->attempt_id . '"> ' . get_string('view', 'geogebra') . '</a>');
                $table->add_data($row);
            }
            $i++;
        }
    }
}

// This script uses installed report plugins to print geogebra reports

require_once('../../config.php');
require_once('lib.php');

$id = optional_param('id', 0, PARAM_INT);      // Course Module ID, or
$q = optional_param('q', 0, PARAM_INT);       // geogebra ID
$student = optional_param('student', 0, PARAM_INT);    // student id
$attemptid = optional_param('attempt', 0, PARAM_INT);
$gradecomment = optional_param('gradecomment', NULL);
$manualgrade = optional_param('manualgrade', NULL);
$updated = optional_param('updated', 0, PARAM_INT);

if ($id) {
    if (($cm = get_coursemodule_from_id('geogebra', $id)) === false) {
        error('There is no coursemodule with id ' . $id);
    }

    if (($course = get_record('course', 'id', $cm->course)) === false) {
        error('Course is misconfigured');
    }

    if (($geogebra = get_record('geogebra', 'id', $cm->instance)) === false) {
        error('The geogebra with id ' . $cm->instance . ' corresponding to this coursemodule ' . $id . ' is missing');
    } else {
        $geogebra->cmidnumber = $cm->id;
    }
} else {
    if (($geogebra = get_record('geogebra', 'id', $q)) === false) {
        error('There is no geogebra with id ' . $q);
    }

    if (($course = get_record('course', 'id', $geogebra->course)) === false) {
        error('The course with id ' . $geogebra->course . ' that the geogebra with id ' . $q . ' belongs to is missing');
    }

    if (($cm = get_coursemodule_from_instance('geogebra', $geogebra->id, $course->id)) === false) {
        error('The course module for the geogebra with id ' . $q . ' is missing');
    } else {
        $geogebra->cmidnumber = $cm->id;
    }
}

require_login($course, false, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
if (!function_exists('grade_get_grades')) { //workaround for buggy PHP versions
    require_once($CFG->libdir . '/gradelib.php');
}

$navlinks = array(
    array(
        'name' => get_string('modulenameplural', 'geogebra'),
        'link' => $CFG->wwwroot . '/mod/geogebra/index.php?id=' . $course->id,
        'type' => 'activity'
    ),
    array(
        'name' => format_string($geogebra->name),
        'link' => $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id,
        'type' => 'activityinstance'
    )
);


if (has_capability('mod/geogebra:gradeactivity', $context)) {
    //Update grades and comments if requiered
    if (!empty($updated)) {
        // if (($gradecomment != NULL) && ($manualgrade != NULL)) {
        $attempt = geogebra_get_attempt($attemptid);
        parse_str($attempt->vars, $parsedVars);
        $parsedVars['grade'] = $manualgrade;
        $vars = http_build_query(array(
            'state' => $parsedVars['state'],
            'grade' => $parsedVars['grade'],
            'duration' => $parsedVars['duration'],
            'attempts' => $parsedVars['attempts']
                ), '', '&');
        geogebra_update_attempt_grade($attemptid, $vars, $gradecomment);
        geogebra_update_grades($geogebra, $student);
    }
    /*
     * Teacher view
     */
    if ($attemptid && $student) {
        // Detailed view of one attempt
        geogebra_show_attempt($cm, $course, $context, $navlinks, $geogebra, $student, $attemptid);
    } else {
        // General view - Summary for each student
        geogebra_show_all_attempts($cm, $course, $context, $navlinks, $geogebra, $id, $student);
    }
} else {
    /*
     *  Student view
     */
    if ($attemptid && $student && $student == $USER->id) {
        // Show only one attempt if attemptid is specified and userid is the current user (to avoid security problems)
        geogebra_show_attempt($cm, $course, $context, $navlinks, $geogebra, $student, $attemptid);
    } else {
        // Show all his/her attempts
        $navigation = build_navigation($navlinks);

        print_header_simple(
                format_string($geogebra->name), '', $navigation, '', '', true, update_module_button($cm->id, $course->id, get_string('modulename', 'geogebra')), navmenu($course, $cm)
        );

        print_tabs(array(
            array(
                new tabobject('view', $CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id, get_string('viewtab', 'geogebra')),
                new tabobject('grade', $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $cm->id, get_string('resultstab', 'geogebra'))
            )
                ), 'grade');

        $select = 'SELECT attempt.id, user.id AS user_id, user.firstname AS user_firstname, user.lastname AS user_lastname, attempt.vars AS vars, attempt.gradecomment AS gradecomment, attempt.date AS date, attempt.finished AS finished';
        $from = ' FROM ' . $CFG->prefix . 'user user, ' . $CFG->prefix . 'geogebra_attempts attempt';
        $where = ' WHERE attempt.userid = user.id AND attempt.geogebra = ' . $geogebra->id . ' AND user.id = ' . $USER->id;
        $orderBy = ' ORDER BY date ASC';

        if (($attempts = get_records_sql($select . $from . $where . $orderBy)) !== false) {
            $title = get_string('report', 'geogebra') . ' ' . $geogebra->name;
            print_heading($title, 'center');

            if ($geogebra->grade != GEOGEBRA_NO_GRADING) {
                if ($geogebra->grade > 0) {
                    $max = '/' . $geogebra->grade;
                } else {
                    $max = '';
                }
                $table->head = array(get_string('attempt', 'geogebra') . ($geogebra->maxattempts > 0 ? ' / ' . $geogebra->maxattempts : ''), get_string('duration', 'geogebra'), get_string('comment', 'geogebra'), get_string('grade', 'geogebra') . $max, get_string('date'));
                $table->align = array('center', 'center', 'center', 'center', 'center');
                $table->data = array();

                foreach ($attempts as $attempt) {
                    parse_str($attempt->vars, $parsedVars);
                    $grade = $parsedVars['grade'];

                    $notice = '';
                    if ($attempt->finished == 0) { //Don't show grade if attempt is unfinished
                        $grade = '-';
                        $notice = ' (' . get_string('unfinished', 'geogebra') . ')';
                    } else if ($grade == -1) {
                        $grade = get_string('ungraded', 'geogebra');
                    } else if ($geogebra->grade < 0) {
                        $finalgrade = grade_get_grades($course->id, 'mod', 'geogebra', $geogebra->id, $USER->id);
                        $grade = $finalgrade->items[0]->grades[$USER->id]->str_grade;
                    }

                    array_push($table->data, array(
                        '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $USER->id . '&attempt=' . $attempt->id . '"> ' . $parsedVars['attempts'] . '</a>' . $notice,
                        htmlentities(geogebra_time2str($parsedVars['duration']), ENT_QUOTES, 'UTF-8'),
                        !empty($attempt->gradecomment) ? shorten_text(trim(strip_tags(format_text($attempt->gradecomment))), 25) : '',
                        $grade,
                        userdate($attempt->date)
                    ));
                }
                print_table($table);
            } else {
                // No grading; only attempts
                $table->head = array(get_string('attempt', 'geogebra') . ($geogebra->maxattempts > 0 ? ' / ' . $geogebra->maxattempts : ''), get_string('duration', 'geogebra'), get_string('date'));
                $table->align = array('center', 'center', 'center');
                $table->data = array();

                foreach ($attempts as $attempt) {
                    parse_str($attempt->vars, $parsedVars);
                    $duration = array_key_exists('duration', $parsedVars) ? $parsedVars['duration'] : 0;

                    $notice = '';
                    if ($attempt->finished == 0) { //Don't show grade if attempt is unfinished
                        $notice = ' (' . get_string('unfinished', 'geogebra') . ')';
                    }

                    array_push($table->data, array(
                        '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $attempt->user_id . '&attempt=' . $attempt->id . '"> ' . $parsedVars['attempts'] . '</a>' . $notice,
                        htmlentities(geogebra_time2str($duration), ENT_QUOTES, 'UTF-8'),
                        userdate($attempt->date)
                    ));
                }
                print_table($table);
            }
        } else {
            print_heading('No results.');
        }
        echo '<br>';
        print_continue($CFG->wwwroot . '/course/view.php?id=' . $course->id);
    }
}

// Print footer
print_footer($course);
?>

