<?php


define('CLI_SCRIPT', 1);
require_once('../../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

$folder = 'a69397438a5ddc56d2ae59b2fbebc3d6'; // home_remote=a69397438a5ddc56d2ae59b2fbebc3d6 :: home_local=3ed2f0b34cf0528466a614c79a4c3619
$userid = 2;
$idexec = 3;  // Increase before every execution
$fullname = 'GeoGebra restore '.$idexec;
$shortname = 'geogebra_restore_'.$idexec;
$categoryid = 3; // Restore testing category

// Transaction
$transaction = $DB->start_delegated_transaction();

// Create new course
$courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);

// Restore backup into course
$controller = new restore_controller($folder, $courseid, 
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $userid,
        backup::TARGET_NEW_COURSE);
$controller->execute_precheck();
$controller->execute_plan();

// Commit
$transaction->allow_commit();
