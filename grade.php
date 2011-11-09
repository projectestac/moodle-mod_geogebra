<?php

function showDetailTable($attempt, $context, $navlinks, $geogebra, $cm, $course, $parsedVars, $attemptnumber) {
    $title = get_string('report', 'geogebra') . ' ' . $geogebra->name . ' ' . get_string('for', 'geogebra') . ' ' . $attempt->user_firstname . ' ' . $attempt->user_lastname;

    if ($geogebra->grademethod != GEOGEBRA_NO_GRADING) {
        $table->head = array(get_string('attempt', 'geogebra'), get_string('duration', 'geogebra'), get_string('grade', 'geogebra') . '/' . $geogebra->maxgrade, get_string('date'));
        $table->align = array('center', 'center', 'center', 'center');
    } else {
        $table->head = array(get_string('attempt', 'geogebra'), get_string('duration', 'geogebra'), get_string('date'));
        $table->align = array('center', 'center', 'center');
    }
    parse_str($attempt->vars, $parsedVars);
    $grademax = (isset($geogebra->grademax)) ? $geogebra->grademax : 100;

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

    if ($geogebra->grademethod != GEOGEBRA_NO_GRADING) {
        $table->data = array(array(
                $attemptnumber,
                htmlentities(geogebra_time2str($parsedVars['duration']), ENT_QUOTES, 'UTF-8'),
                $grade,
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
        echo '<br/>';
        $table_detailed->head = array(get_string('description', 'geogebra'), 'Grade/' . $geogebra->maxgrade, get_string('weight', 'geogebra'));
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

    parse_str($geogebra->url, $attributes);
    echo '<br/><div style="text-align: center">';
    echo '<form id="geogebra_form" method="POST" >';
    geogebra_show_applet($geogebra, $attributes, $parsedVars);
    echo '</form>';
    echo '</div>';
}

function geogebra_show_attempt($cm, $course, $context, $navlinks, $geogebra, $userid, $attemptid) {
    global $CFG;

    //We need to get all attempts in order to know de attempt number
    $select = 'SELECT ga.*, u.id AS user_id, u.firstname AS user_firstname, u.lastname AS user_lastname, u.picture, u.imagealt ';
    $from = ' FROM ' . $CFG->prefix . 'user u ' .
            'LEFT JOIN ' . $CFG->prefix . 'geogebra_attempts ga ON u.id = ga.userid ';
    $where = ' WHERE ga.userid = ' . $userid . ' AND ga.geogebra = ' . $geogebra->id;

    if ($attempts = get_records_sql($select . $from . $where)) {
        
    }
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
        $select = 'SELECT u.id AS user_id, u.firstname AS user_firstname, u.lastname AS user_lastname, u.picture, u.imagealt ';
        $sql = 'FROM ' . $CFG->prefix . 'user u ' .
                'LEFT JOIN ' . $CFG->prefix . 'geogebra_attempts ga ON u.id = ga.userid ' .
                'WHERE u.id IN (' . implode(',', $users) . ') ' .
                'ORDER BY user_lastname, user_firstname';

        $users = get_records_sql($select . $sql);
        $table->id = "mod-geogebra-grades";
        if ($geogebra->grademethod != GEOGEBRA_NO_GRADING) {
            $table->head = array(get_string('user'), get_string('attempts', 'geogebra') . ($geogebra->maxattempts > 0 ? ' / ' . $geogebra->maxattempts : ''), get_string('duration', 'geogebra'), get_string('grade', 'geogebra') . ' / ' . $geogebra->maxgrade, get_string('date'));
            $table->align = array('left', 'left', 'right', 'right', 'center');
        } else {
            $table->head = array(get_string('user'), get_string('attempts', 'geogebra') . ($geogebra->maxattempts > 0 ? ' / ' . $geogebra->maxattempts : ''), get_string('duration', 'geogebra'), get_string('date'));
            $table->align = array('left', 'left', 'right', 'center');
        }
        $table->data = array();

        foreach ($users as $user) {

            $any_unfinished = 0; //To show correct number of attempts when there is an unfinished one.
            if (geogebra_get_unfinished_attempt($geogebra->id, $user->user_id)) {
                $any_unfinished = 1;
            }

            $attemptsgrade = geogebra_get_user_grades($geogebra, $user->user_id);
            if ($attemptsgrade) {

                $grademax = (isset($geogebra->grademax)) ? $geogebra->grademax : 100;

                $grade = $attemptsgrade->grade;

                if ($grade > $grademax) {
                    $grade = $grademax;
                }

                if ($geogebra->grademethod != GEOGEBRA_NO_GRADING) {
                    array_push($table->data, array(
                        print_user_picture($user->user_id, $geogebra->course, NULL, 0, true) . ' ' . ' <a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->user_id . '">' . $user->user_firstname . ' ' . $user->user_lastname . '</a>',
                        htmlentities($attemptsgrade->attempts + $any_unfinished, ENT_QUOTES, 'UTF-8'),
                        htmlentities(geogebra_time2str($attemptsgrade->duration), ENT_QUOTES, 'UTF-8'),
                        $grade,
                        !empty($attemptsgrade->date) ? userdate($attemptsgrade->date) : ''
                    ));
                } else {
                    array_push($table->data, array(
                        print_user_picture($user->user_id, $geogebra->course, NULL, 0, true) . ' ' . '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->user_id . '">' . $user->user_firstname . ' ' . $user->user_lastname . '</a>',
                        htmlentities($attemptsgrade->attempts + $any_unfinished, ENT_QUOTES, 'UTF-8'),
                        htmlentities(geogebra_time2str($attemptsgrade->duration), ENT_QUOTES, 'UTF-8'),
                        !empty($attemptsgrade->date) ? userdate($attemptsgrade->date) : ''
                    ));
                }
                if ($student == $user->user_id) {
                    geogebra_show_user_attempts($geogebra, $user->user_id, $table, $id);
                }
            } else {
                // The user has not any finished attempt
                if ($geogebra->grademethod != GEOGEBRA_NO_GRADING) {
                    //Any unfinished??
                    if ($any_unfinished) {
                        array_push($table->data, array(
                            print_user_picture($user->user_id, $geogebra->course, NULL, 0, true) . ' ' . ' <a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->user_id . '">' . $user->user_firstname . ' ' . $user->user_lastname . '</a>',
                            htmlentities($any_unfinished, ENT_QUOTES, 'UTF-8'),
                            '',
                            '',
                            ''
                        ));
                        if ($student == $user->user_id) {
                            geogebra_show_user_attempts($geogebra, $user->user_id, $table, $id);
                        }
                    } else {
                        //Empty table
                        array_push($table->data, array(
                            print_user_picture($user->user_id, $geogebra->course, NULL, 0, true) . ' ' . $user->user_firstname . ' ' . $user->user_lastname, '', '', '', ''));
                    }
                } else {
                    if ($any_unfinished) {
                        array_push($table->data, array(
                            print_user_picture($user->user_id, $geogebra->course, NULL, 0, true) . ' ' . ' <a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $user->user_id . '">' . $user->user_firstname . ' ' . $user->user_lastname . '</a>',
                            htmlentities($any_unfinished, ENT_QUOTES, 'UTF-8'),
                            '',
                            ''
                        ));
                        if ($student == $user->user_id) {
                            geogebra_show_user_attempts($geogebra, $user->user_id, $table, $id);
                        }
                    } else {
                        array_push($table->data, array(
                            print_user_picture($user->user_id, $geogebra->course, NULL, 0, true) . ' ' . $user->user_firstname . ' ' . $user->user_lastname, '', '', ''));
                    }
                }
            }
        }

        print_table($table);
    } else {
        // The course has no users
        print_string('coursewithoutstudents', 'geogebra');
    }
}

function geogebra_show_user_attempts($geogebra, $userid, $table, $id) {
    global $CFG;

    $select = 'SELECT attempt.id AS attempt_id, user.id AS user_id, user.firstname AS user_firstname, user.lastname AS user_lastname, attempt.vars AS vars, attempt.date AS date , attempt.finished AS finished';
    $from = ' FROM ' . $CFG->prefix . 'user user, ' . $CFG->prefix . 'geogebra_attempts attempt';
    $where = ' WHERE attempt.userid = user.id AND attempt.geogebra = ' . $geogebra->id . ' AND user.id = ' . $userid;
    $orderBy = ' ORDER BY date ASC';

    if (($attempts = get_records_sql($select . $from . $where . $orderBy)) !== false) {

        $i = 1;
        foreach ($attempts as $attempt) {
            parse_str($attempt->vars, $parsedVars);
            $grademax = (isset($parsedVars['grademax'])) ? $parsedVars['grademax'] : 100;

            $grade = $parsedVars['grade'];

            if ($grade > $grademax) {
                $grade = $grademax;
            }

            $notice = '';
            if (!$attempt->finished) {
                $grade = $grade;
                $notice = ' (' . get_string('unfinished', 'geogebra') . ')';
            }

            if ($geogebra->grademethod != GEOGEBRA_NO_GRADING) {
                array_push($table->data, array(
                    '',
                    '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $userid . '&attempt=' . $attempt->attempt_id . '"> ' . $i . '</a>' . $notice,
                    htmlentities(geogebra_time2str($parsedVars['duration']), ENT_QUOTES, 'UTF-8'),
                    $grade,
                    userdate($attempt->date)
                ));
            } else {
                array_push($table->data, array(
                    '',
                    '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $userid . '&attempt=' . $attempt->attempt_id . '"> ' . $i . '</a>' . $notice,
                    htmlentities(geogebra_time2str($parsedVars['duration']), ENT_QUOTES, 'UTF-8'),
                    userdate($attempt->date)
                ));
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



if ($id) {
    if (($cm = get_coursemodule_from_id('geogebra', $id)) === false) {
        error('There is no coursemodule with id ' . $id);
    }

    if (($course = get_record('course', 'id', $cm->course)) === false) {
        error('Course is misconfigured');
    }

    if (($geogebra = get_record('geogebra', 'id', $cm->instance)) === false) {
        error('The geogebra with id ' . $cm->instance . ' corresponding to this coursemodule ' . $id . ' is missing');
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
    }
}

require_login($course, false, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

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


if (has_capability('mod/geogebra:viewreports', $context)) {
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

        $select = 'SELECT attempt.id, user.id AS user_id, user.firstname AS user_firstname, user.lastname AS user_lastname, attempt.vars AS vars, attempt.date AS date, attempt.finished AS finished';
        $from = ' FROM ' . $CFG->prefix . 'user user, ' . $CFG->prefix . 'geogebra_attempts attempt';
        $where = ' WHERE attempt.userid = user.id AND attempt.geogebra = ' . $geogebra->id . ' AND user.id = ' . $USER->id;
        $orderBy = ' ORDER BY date ASC';

        if (($attempts = get_records_sql($select . $from . $where . $orderBy)) !== false) {
            $title = get_string('report', 'geogebra') . ' ' . $geogebra->name;
            print_heading($title, 'center');

            if ($geogebra->grademethod != GEOGEBRA_NO_GRADING) {
                $table->head = array(get_string('attempt', 'geogebra') . ($geogebra->maxattempts > 0 ? ' / ' . $geogebra->maxattempts : ''), get_string('duration', 'geogebra'), get_string('grade', 'geogebra') . ' / ' . $geogebra->maxgrade, get_string('date'));
                $table->align = array('center', 'center', 'center', 'center');
                $table->data = array();

                $attemptnumber = 1;
                foreach ($attempts as $attempt) {
                    parse_str($attempt->vars, $parsedVars);
                    $grademax = (isset($parsedVars['grademax'])) ? $parsedVars['grademax'] : 100;
                    $grade = $parsedVars['grade'] / $grademax * 100;

                    $notice = '';
                    if ($attempt->finished == 0) { //Don't show grade if attempt is unfinished
                        $grade = '-';
                        $notice = ' (' . get_string('unfinished', 'geogebra') . ')';
                    } else if ($grade > 100) {
                        $grade = 100;
                    }

                    array_push($table->data, array(
                        '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $USER->id . '&attempt=' . $attempt->id . '"> ' . $attemptnumber . '</a>' . $notice,
                        htmlentities(geogebra_time2str($parsedVars['duration']), ENT_QUOTES, 'UTF-8'),
                        $grade,
                        //htmlentities($parsedVars['attempts'], ENT_QUOTES, 'UTF-8'),
                        userdate($attempt->date)
                    ));
                    $attemptnumber++;
                }
                print_table($table);
            } else {
                // No grading; only attempts
                $table->head = array(get_string('attempt', 'geogebra') . ($geogebra->maxattempts > 0 ? ' / ' . $geogebra->maxattempts : ''), get_string('duration', 'geogebra'), get_string('date'));
                $table->align = array('center', 'center', 'center');
                $table->data = array();

                $attemptnumber = 1;
                foreach ($attempts as $attempt) {
                    parse_str($attempt->vars, $parsedVars);
                    $duration = array_key_exists('duration', $parsedVars) ? $parsedVars['duration'] : 0;

                    $notice = '';
                    if ($attempt->finished == 0) { //Don't show grade if attempt is unfinished
                        $notice = ' (' . get_string('unfinished', 'geogebra') . ')';
                    }

                    array_push($table->data, array(
                        '<a href="' . $CFG->wwwroot . '/mod/geogebra/grade.php?id=' . $id . '&student=' . $attempt->user_id . '&attempt=' . $attempt->id . '"> ' . $attemptnumber . '</a>' . $notice,
                        htmlentities(geogebra_time2str($duration), ENT_QUOTES, 'UTF-8'),
                        userdate($attempt->date)
                    ));
                    $attemptnumber++;
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

geogebra_update_grades(null, 4);

// Print footer

print_footer($course);
?>

