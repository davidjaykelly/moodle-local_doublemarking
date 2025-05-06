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

namespace local_doublemarking\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the double marking plugin.
 *
 * @package    local_doublemarking
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'local_doublemarking_alloc',
            [
                'assignmentid' => 'privacy:metadata:local_doublemarking_alloc:assignmentid',
                'userid' => 'privacy:metadata:local_doublemarking_alloc:userid',
                'marker1' => 'privacy:metadata:local_doublemarking_alloc:marker1',
                'marker2' => 'privacy:metadata:local_doublemarking_alloc:marker2',
                'ratifier' => 'privacy:metadata:local_doublemarking_alloc:ratifier',
                'marker1grade' => 'privacy:metadata:local_doublemarking_alloc:marker1grade',
                'marker2grade' => 'privacy:metadata:local_doublemarking_alloc:marker2grade',
                'marker1feedback' => 'privacy:metadata:local_doublemarking_alloc:marker1feedback',
                'marker2feedback' => 'privacy:metadata:local_doublemarking_alloc:marker2feedback',
                'finalgrade' => 'privacy:metadata:local_doublemarking_alloc:finalgrade',
                'blindsetting' => 'privacy:metadata:local_doublemarking_alloc:blindsetting',
                'timecreated' => 'privacy:metadata:local_doublemarking_alloc:timecreated',
                'timemodified' => 'privacy:metadata:local_doublemarking_alloc:timemodified',
            ],
            'privacy:metadata:local_doublemarking_alloc'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {assign} a ON a.id = cm.instance
                  JOIN {local_doublemarking_alloc} dm ON dm.assignmentid = a.id
                 WHERE dm.userid = :userid
                    OR dm.marker1 = :marker1
                    OR dm.marker2 = :marker2
                    OR dm.ratifier = :ratifier";

        $params = [
            'modname'      => 'assign',
            'contextlevel' => CONTEXT_MODULE,
            'userid'       => $userid,
            'marker1'      => $userid,
            'marker2'      => $userid,
            'ratifier'     => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT dm.*, a.name as assignment_name, c.id as contextid
                  FROM {local_doublemarking_alloc} dm
                  JOIN {assign} a ON a.id = dm.assignmentid
                  JOIN {course_modules} cm ON cm.instance = a.id
                  JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = :contextlevel
                 WHERE c.id {$contextsql}
                   AND (dm.userid = :userid
                    OR dm.marker1 = :marker1
                    OR dm.marker2 = :marker2
                    OR dm.ratifier = :ratifier)";

        $params = array_merge($contextparams, [
            'contextlevel' => CONTEXT_MODULE,
            'userid'       => $userid,
            'marker1'      => $userid,
            'marker2'      => $userid,
            'ratifier'     => $userid,
        ]);

        $allocations = $DB->get_records_sql($sql, $params);

        foreach ($allocations as $allocation) {
            $context = \context::instance_by_id($allocation->contextid);
            
            $data = [
                'assignmentid' => $allocation->assignmentid,
                'assignment_name' => $allocation->assignment_name,
                'userid' => $allocation->userid,
                'marker1' => $allocation->marker1,
                'marker2' => $allocation->marker2,
                'ratifier' => $allocation->ratifier,
                'marker1grade' => $allocation->marker1grade,
                'marker2grade' => $allocation->marker2grade,
                'finalgrade' => $allocation->finalgrade,
                'blindsetting' => $allocation->blindsetting,
                'timecreated' => \core_privacy\local\request\transform::datetime($allocation->timecreated),
                'timemodified' => \core_privacy\local\request\transform::datetime($allocation->timemodified),
            ];

            if ($userid == $allocation->marker1) {
                $data['marker1feedback'] = $allocation->marker1feedback;
            }
            if ($userid == $allocation->marker2) {
                $data['marker2feedback'] = $allocation->marker2feedback;
            }

            writer::with_context($context)->export_data(['local_doublemarking'], $data);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('assign', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('local_doublemarking_alloc', ['assignmentid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('assign', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('local_doublemarking_alloc', [
                'assignmentid' => $cm->instance,
                'userid' => $userid
            ]);

            // Anonymize any marking data where this user was a marker
            $updatefields = new \stdClass();
            $updatefields->marker1feedback = '';
            $DB->set_field('local_doublemarking_alloc', 'marker1feedback', '', ['marker1' => $userid]);
            $DB->set_field('local_doublemarking_alloc', 'marker2feedback', '', ['marker2' => $userid]);
        }
    }
}
