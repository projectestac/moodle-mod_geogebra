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
 * Privacy Subsystem implementation for mod_geogebra.
 *
 * @package    mod_geogebra
 * @category   privacy
 * @copyright  2018 Salva Valdeoriola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_geogebra\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for geogebra activity module.
 *
 * @copyright  2018 Salva Valldeoriola
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        \core_privacy\local\request\core_userlist_provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table(
            'geogebra_attempts',
            [
                'geogebra' => 'privacy:metadata:geogebra_attempts:geogebra',
                'userid' => 'privacy:metadata:geogebra_attempts:userid',
                'vars' => 'privacy:metadata:geogebra_attempts:vars',
                'gradecomment' => 'privacy:metadata:geogebra_attempts:gradecomment',
                'finished' => 'privacy:metadata:geogebra_attempts:finished',
                'dateteacher' => 'privacy:metadata:geogebra_attempts:dateteacher',
                'datestudent' => 'privacy:metadata:geogebra_attempts:datestudent',
            ],
            'privacy:metadata:geogebra_attempts'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // Fetch all geogebra attempts.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {geogebra} g ON g.id = cm.instance
            INNER JOIN {geogebra_attempts} ga ON ga.geogebra = g.id
                 WHERE ga.userid = :userid";

        $params = [
                'modname'       => 'geogebra',
                'contextlevel'  => CONTEXT_MODULE,
                'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all attempts.
        $sql = "SELECT ga.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {geogebra} g ON g.id = cm.instance
                  JOIN {geogebra_attempts} ga ON g.id = ga.geogebra
                 WHERE cm.id = :cmid";

        $params = [
                'cmid'      => $context->instanceid,
                'modname'   => 'geogebra',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       ga.vars as attempt,
                       g.timemodified
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {geogebra} g ON g.id = cm.instance
            INNER JOIN {geogebra_attempts} ga ON ga.geogebra = g.id
                 WHERE c.id {$contextsql}
                       AND ga.userid = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'geogebra', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;

        // Reference to the geogebra activity seen in the last iteration of the loop. By comparing this with the current record, and
        // cause we know the results are ordered, we know when we've moved to the attempt for a new geogebra activity and therefore
        // when we can export the complete data for the last activity attempt.
        $lastcmid = null;

        $geogebraattempts = $DB->get_recordset_sql($sql, $params);

        foreach ($geogebraattempts as $geogebraattempt) {
            // If we've moved to a new geogebra, then write the last geogebra data and reinit the geogebra data array.
            if ($lastcmid != $geogebraattempt->cmid) {
                if (!empty($geogebradata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_geogebra_data_for_user($geogebradata, $context, $user);
                }
                $geogebradata = [
                        'attempt' => [],
                        'timemodified' => \core_privacy\local\request\transform::datetime($geogebraattempt->timemodified),
                ];
            }
            $geogebradata['attempt'][] = $geogebraattempt->attempt;
            $lastcmid = $geogebraattempt->cmid;
        }
        $geogebraattempts->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($geogebradata)) {
            $context = \context_module::instance($lastcmid);
            self::export_geogebra_data_for_user($geogebradata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single geogebra activity, along with any generic data or area files.
     *
     * @param array $geogebradata the personal data to export for the attempt.
     * @param \context_module $context the context of geogebra.
     * @param \stdClass $user the user record
     */
    protected static function export_geogebra_data_for_user(array $choicedata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for geogebra attempt.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with attempt data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $choicedata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('geogebra', $context->instanceid)) {
            $DB->delete_records('geogebra_attempts', ['geogebra' => $cm->instance]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);
            $DB->delete_records('geogebra_attempts', ['geogebra' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('geogebra', $context->instanceid);

        if (!$cm) {
            // Only geogebra module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "geogebra = :geogebra AND userid $usersql";
        $params = ['geogebra' => $cm->instance] + $userparams;
        $DB->delete_records_select('geogebra_attempts', $select, $params);
    }
}
