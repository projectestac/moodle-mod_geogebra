<?php
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/view-extended.php');
function tempfolderggbs(){
    global $COURSE;
    return "tmp".$COURSE->shortname;
}
//----------- revised version of geogebra_print_content
function geogebra_dump_content($geogebra, $context,$ggbfilename,$ggb64=false) {
    // this function is the only place where we build and load the GGB applet.
    // Other interaction takes place thru the code in geogebra_view
    // the parameter $geogebra holds in $geogebra->attributes the limitations
    // for students.
    // Presently the student has the option to store an attempt and to restart
    // his/her work where he/she left. It must be possible to purge all the attempts
    // and graded tests but the highest note.
    // If the code is instrumented for revision then just one attempt is needed.
    // See attempt.php for the page servicing at the end of the student's activity
    global $CFG,$COURSE,$dumped;
    
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
    
    if ($ggb64)  {/* $attribs['ggbBase64'] = rawurlencode($ggb64);*/}// 'unescape("'.$ggb64.'")';/*.base64_encode($ggb64);*/  
      else 
        if (isset($content) && !empty($content)) {
        $attribs['ggbBase64'] =  base64_encode($content);
        // $attribs['ggbBase64'] = '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';
    } else if (isset($materialid) && !empty($materialid)) {
        $attribs['material_id'] = $materialid;
    } else {
        return false;
    }
    
    // Add loading of fflate
    //echo "import ". get_config('geogebra', 'fflate') ;
    // Check if the activity has a custom URL for deploy ggb
    $deployggburl = !empty($geogebra->urlggb) ? $geogebra->urlggb : get_config('geogebra', 'deployggb');
    $urls=explode("|", $deployggburl );
    if(substr(trim($urls[0]),0,2) === "//") $urls[0] = "http:".trim($urls[0]);
    if(count($urls)>1 && substr(trim($urls[1]),0,2) === "//") $urls[1] = "http:".trim($urls[1]);
    // Add loading of GeoGebra
    echo '<script type="text/javascript" src="' . $urls[0] . '"></script>';
    
    //echo "import ". $urls[0] ;// Get activity width
    $width = $geogebra->width === 0 ? '100%' : $geogebra->width . 'px';
    //echo ' window.onload = function() {

    echo '<script>'.
        //'alert("hello0")'.
    
        'window.onload = function() {';
        //.'alert("hello4");debugger;';//foo="'.$ggb64.'";';
        if ($ggb64)  {
            echo 'code=(unescape("'.$ggb64.'"));'//.
               // 'boot=\'js:RT_dumpsteps("'.$ggbfilename.'")\';'
        ;}
    echo
        'var applet = new GGBApplet({';
    foreach ($attribnames as $name) {
        echo $name.': '.geogebra_get_script_param($name, $attributes).',';
    }
    foreach ($attribs as $name => $value) {
        echo $name.': "'.$value.'",';
    }
    if ($ggb64)  {echo 'ggbBase64: code'
    //.',appletOnLoad : function(){RT_dumpsteps("'.$ggbfilename.'"); }'
        ;}
    echo '}, true);'.
        (count($urls)==1 ?
            (substr(trim($urls[0]),0,4) === "http"?
                '' :
                'applet.setHTML5Codebase("'.str_replace("deployggb.js","",$deployggburl).'HTML5/5.0/web3d/");'
                ):
            'applet.setHTML5Codebase("'.$urls[1].'");'
            ).
            
    'applet.inject("applet_container", "preferHTML5");'.
    //'alert(unescape("'.$ggb64.'"));'.
            //'alert("press f12");debugger;applet.setBase64(unescape("'.$ggb64.'"));'.
            
            //'applet.inject(document.body,"preferHTML5");'.
            'api = applet.getAppletObject();'.
 
            '}'.
            '</script>';
            // Include also javascript code from GGB file
            geogebra_dump_js_from_geogebra($context, $geogebra,$ggbfilename);

}

//----------- revised version of geogebra_get_js_from_geogebra

/**
 * Execute Javascript that is embedded in the geogebra file, if it exists
 * File must be named geogebra_javascript.js
 * @param  object $context  Of the activity to get the files
 * @param  object $geogebra object with the activity info
**/
function geogebra_dump_js_from_geogebra($context, $geogebra,$ggbfilename) {
    global $CFG;
    
    $content = false;
    
    if (geogebra_is_valid_external_url($geogebra->url)) {
        require_once("$CFG->libdir/filestorage/zip_packer.php");
        // Prepare tmp dir (create if not exists, download ggb file...)
        $dirname = 'mod_geogebra_'.time();
        $tmpdir = make_temp_directory($dirname);
        if (!$tmpdir) {
            debugging("Cannot create temp directory $dirname");
            return;
        }
        
        $materialid = geogebra_get_id($geogebra->url);
        if ($materialid) {
            $ggbfile = "http://tube.geogebra.org/material/download/format/file/id/$materialid";
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
    global $PAGE;
    $exturl = new moodle_url($CFG->wwwroot .'/grade/export/extended/extended_view.js');
    echo '<script type="text/javascript" src="' .$exturl. '"></script>';
    echo '<script type="text/javascript">';
    echo 'if (typeof ggbApplet == "undefined") {ggbApplet = document.ggbApplet;} ' . $content ;
    echo 'function RT_initHook(){RT_dumpsteps("'.$ggbfilename.'");RT_popwins()}';    
    echo  '</script>';
}


//----------- revised version of geogebra_view_applet
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
function geogebra_dump_applet($geogebra, $cm, $context, $attempt = null, $ispreview = false,$ggbfilename) {
    global $OUTPUT, $PAGE, $CFG, $USER,$dumped;
    
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
            //geogebra_view_userid_results($geogebra, $userid, $cm, $context, $viewmode, $attempt);
        } else if (!$ispreview) {
            //echo $OUTPUT->box_start('generalbox');
            if ($geogebra->maxattempts < 0) {
            //    echo get_string('unlimitedattempts', 'geogebra').'<br/>';
            } else if ($attempts == $geogebra->maxattempts - 1) {
            //    echo get_string('lastattemptremaining', 'geogebra').'<br/>';
            } else {
            //    echo get_string('attemptsremaining', 'geogebra').($geogebra->maxattempts - $attempts).'<br/>';
            }
            
            // If there is some unfinished attempt, show it
            $attempt = geogebra_get_unfinished_attempt($geogebra->id, $userid);
            if (!empty($attempt)) {
            //    echo '('.get_string('resumeattempt', 'geogebra').')';
            }
            //echo $OUTPUT->box_end();
        }
        // start building the dumper page
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
        ob_start();
        echo "<html>\n";
        echo " <head>\n";
        echo " <title>".$ggbfilename."</title>\n";
        // for some reasons all + are turned into blank this is due to getting the stored base 64 string via GET instead of 
        // POST here is a simple patch.
        $ggbcode = str_replace(" ", "+", $parsedvars['state']);        
        geogebra_dump_content($geogebra, $context,$ggbfilename,$ggbcode );
        echo " </head>\n"."<body>\n";
        $width = $geogebra->width === 0 ? '100%' : $geogebra->width . 'px';
        echo '<div id="applet_container" style="width: ' . $width .'; height: ' . $geogebra->height . 'px;"></div>';
    } else {
        echo $OUTPUT->box(get_string('msg_noattempts', 'geogebra'), 'generalbox boxaligncenter');
    }
    
    echo    "</body>\n"."</html>\n";
    $s = ob_get_contents();
    make_temp_directory(tempfolderggbs());
    $tempfilename = "$CFG->tempdir/".tempfolderggbs()."/$ggbfilename.html";
    if (!$handle = fopen($tempfilename, 'w+b')) {
        print_error('cannotcreatetempdir');
    }
    $dumped++;
    fwrite($handle,  $s);
    fclose($handle);
    ob_end_clean();
    return $s;
    // ends builder page
    //geogebra_view_dates($geogebra, $context, $timenow);
}
//----------- revised version of geogebra_get_attempt_row for dumping all
function geogebra_dump_attempt_row($geogebra, $attempt, $user, $cm = null, $context = null, $row = null) {
    global $CFG, $USER,$COURSE;
    
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
        $textlink = "Local ".get_string('viewattempt', 'geogebra');
        if (is_siteadmin() || has_capability('moodle/grade:viewall', $context, $USER->id, false)) {
            if ($attempt->dateteacher < $attempt->datestudent ) {
                $textlink = '<span class="pendinggrade" >'. "Local ". get_string('grade'). '</span>';
            } else {
                $textlink = "Local ". get_string('update');
            }
        }
        $ggbfilename="ggb-".$user->firstname."-".$user->lastname."-".$attempt->id."-".userdate($attempt->datestudent)."-".$cm->name;
        $ggbfilename=preg_replace( '/[^a-z0-9]+/', '-', strtolower( $ggbfilename ) );
        //$status = '<a href="' . $CFG->wwwroot ."/grade/export/extended/ggb_dumper.php?id=".$COURSE->id."&fname=".$ggbfilename.".html".
        $status = '<a href="./'.$ggbfilename.".html".
            //$CFG->wwwroot . '/mod/geogebra/view.php?id=' . $cm->id . '&student=' . $user->id .'&attemptid='.$attempt->id.'
        '"> ' . $textlink . '</a>';
        array_push($row, $status);
        geogebra_dump_view_extended($cm->id, $attempt->id,$ggbfilename);    }
    return $row;
}
//----------- revised version of geogebra_view_results for dumping all
function geogebra_dump_results($geogebra, $context, $cm, $course, $action) {
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
                        $row = geogebra_dump_attempt_row($geogebra, $attempt, $auser, $cm, $context, $row);
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


//-----------
