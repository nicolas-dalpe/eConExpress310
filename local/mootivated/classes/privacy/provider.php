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
 * Privacy provider.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated\privacy;
defined('MOODLE_INTERNAL') || die();

use context;
use context_course;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

require_once($CFG->libdir . '/completionlib.php');

/**
 * Privacy provider class.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    use \core_privacy\local\legacy_polyfill;


    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function _get_metadata(collection $collection) {

        $collection->add_database_table('local_mootivated_log', [
            'userid' => 'privacy:metadata:log:userid',
            'contextid' => 'privacy:metadata:log:contextid',
            'eventname' => 'privacy:metadata:log:eventname',
            'objectid' => 'privacy:metadata:log:objectid',
            'relateduserid' => 'privacy:metadata:log:relateduserid',
            'coins' => 'privacy:metadata:log:coins',
            'timecreated' => 'privacy:metadata:log:timecreated',
        ], 'privacy:metadata:log');

        $collection->add_database_table('local_mootivated_completion', [
            'userid' => 'privacy:metadata:completion:userid',
            'courseid' => 'privacy:metadata:completion:courseid',
            'cmid' => 'privacy:metadata:completion:cmid',
            'state' => 'privacy:metadata:completion:state',
            'timecreated' => 'privacy:metadata:completion:timecreated',
        ], 'privacy:metadata:completion');

        $collection->link_external_location('coinsgained', [
            'plugin_id' => 'privacy:metadata:coinsgained:pluginid',
            'username' => 'privacy:metadata:coinsgained:username',
            'firstname' => 'privacy:metadata:coinsgained:firstname',
            'lastname' => 'privacy:metadata:coinsgained:lastname',
            'coins' => 'privacy:metadata:coinsgained:coins',
            'reason' => 'privacy:metadata:coinsgained:reason',
        ], 'privacy:metadata:coinsgained');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function _get_contexts_for_userid($userid) {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT contextid FROM {local_mootivated_log} WHERE userid = :userid OR relateduserid = :relateduserid";
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'relateduserid' => $userid]);

        $sql = "
            SELECT ctx.id
              FROM {context} ctx
              JOIN {local_mootivated_completion} c
                -- Find the course context when there is no cmid.
                ON (ctx.instanceid = c.courseid AND ctx.contextlevel = :courselevel AND (c.cmid IS NULL OR c.cmid = 0))
                -- Else find the module context.
                OR (ctx.instanceid = c.cmid AND ctx.contextlevel = :cmlevel)
             WHERE c.userid = :userid";
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'courselevel' => CONTEXT_COURSE, 'cmlevel' => CONTEXT_MODULE]);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        // Export logs.
        $path = [get_string('pluginname', 'local_mootivated'), get_string('privacy:path:logs', 'local_mootivated')];
        $flushlogs = function($contextid, $logs) use ($path) {
            $context = context::instance_by_id($contextid);
            writer::with_context($context)->export_data($path, (object) ['data' => $logs]);
        };

        list($insql, $inparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "contextid $insql AND (userid = :userid OR relateduserid = :reluserid)";
        $params = ['userid' => $userid, 'reluserid' => $userid] + $inparams;
        $recordset = $DB->get_recordset_select('local_mootivated_log', $sql, $params, 'contextid ASC');
        $lastcontextid = null;
        $logs = [];
        foreach ($recordset as $record) {
            if ($lastcontextid && $lastcontextid != $record->contextid) {
                $flushlogs($lastcontextid, $logs);
                $logs = [];
            }

            $eventclass = $record->eventname;
            $eventname = get_string('unknownevent', 'local_mootivated', $eventclass);
            if (is_subclass_of($eventclass, '\core\event\base')) {
                $eventname = $eventclass::get_name();
            }

            $logs[] = (object) [
                'userid' => transform::user($record->userid),
                'relateduserid' => !empty($record->relateduserid) ? transform::user($record->relateduserid) : null,
                'eventname' => $eventname,
                'objectid' => $record->objectid,
                'coins' => $record->coins,
                'timecreated' => transform::date($record->timecreated)
            ];
            $lastcontextid = $record->contextid;
        }

        // Flush the last iteration.
        if ($lastcontextid) {
            $flushlogs($lastcontextid, $logs);
        }

        $recordset->close();

        // Export completion.
        $path = [get_string('pluginname', 'local_mootivated'), get_string('privacy:path:completion', 'local_mootivated')];
        $flushdata = function($contextid, $completion) use ($path) {
            $context = context::instance_by_id($contextid);
            writer::with_context($context)->export_data($path, (object) ['data' => $completion]);
        };

        $ids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry['cmids'][] = $context->instanceid;
            } else if ($context->contextlevel == CONTEXT_COURSE) {
                $carry['courseids'][] = $context->instanceid;
            }
            return $carry;
        }, ['cmids' => [], 'courseids' => []]);

        $incoursesql = '> 0';
        $incourseparams = [];
        $incmsql = '> 0';
        $incmparams = [];
        if (!empty($ids['courseids'])) {
            list($incoursesql, $incourseparams) = $DB->get_in_or_equal($ids['courseids'], SQL_PARAMS_NAMED);
        }
        if (!empty($ids['cmids'])) {
            list($incmsql, $incmparams) = $DB->get_in_or_equal($ids['cmids'], SQL_PARAMS_NAMED);
        }

        $sql = "((courseid $incoursesql AND (cmid IS NULL OR cmid = 0)) OR (cmid $incmsql)) AND userid = :userid";
        $params = ['userid' => $userid] + $incourseparams + $incmparams;
        $recordset = $DB->get_recordset_select('local_mootivated_completion', $sql, $params, 'courseid ASC, cmid ASC');
        $lastcontextid = null;
        $data = [];
        foreach ($recordset as $record) {
            $context = $record->cmid ? context_module::instance($record->cmid) : context_course::instance($record->courseid);

            if ($lastcontextid && $lastcontextid != $context->id) {
                $flushdata($lastcontextid, $data);
                $data = [];
            }

            $data[] = (object) [
                'userid' => transform::user($record->userid),
                'state' => static::transform_completion_state($record->state),
                'timecreated' => transform::date($record->timecreated)
            ];
            $lastcontextid = $context->id;
        }

        // Flush the last iteration.
        if ($lastcontextid) {
            $flushdata($lastcontextid, $data);
        }

        $recordset->close();

    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function _delete_data_for_all_users_in_context(context $context) {
        global $DB;

        $coursecontext = $context->get_course_context(false);
        $courseid = $coursecontext ? $coursecontext->instanceid : null;
        $cmid = $context->contextlevel == CONTEXT_MODULE ? $context->instanceid : null;

        // Delete logs.
        $DB->delete_records('local_mootivated_log', ['contextid' => $context->id]);

        // Delete completion data.
        if ($cmid) {
            $DB->delete_records_select('local_mootivated_completion', 'cmid = :cmid', ['cmid' => $cmid]);
        } else if ($courseid) {
            $sql = 'courseid = :courseid AND (cmid IS NULL or cmid = 0)';
            $DB->delete_records_select('local_mootivated_completion', $sql, ['courseid' => $courseid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist as $context) {
            $coursecontext = $context->get_course_context(false);
            $courseid = $coursecontext ? $coursecontext->instanceid : null;
            $cmid = $context->contextlevel == CONTEXT_MODULE ? $context->instanceid : null;

            // Delete logs, including the related user ones because we don't need them at this point.
            $sql = 'contextid = :contextid AND (userid = :userid OR relateduserid = :relateduserid)';
            $DB->delete_records_select('local_mootivated_log', $sql, [
                'contextid' => $context->id,
                'userid' => $userid,
                'relateduserid' => $userid
            ]);

            // Delete completion data.
            if ($cmid) {
                $DB->delete_records_select('local_mootivated_completion', 'cmid = :cmid AND userid = :userid', [
                    'cmid' => $cmid,
                    'userid' => $userid
                ]);
            } else if ($courseid) {
                $sql = 'courseid = :courseid AND (cmid IS NULL or cmid = 0) AND userid = :userid';
                $DB->delete_records_select('local_mootivated_completion', $sql, [
                    'courseid' => $courseid,
                    'userid' => $userid
                ]);
            }
        }
    }

    protected static function transform_completion_state($state) {
        switch ($state) {
            case COMPLETION_INCOMPLETE:
                $code = 'completion-n';
                break;
            case COMPLETION_COMPLETE:
                $code = 'completion-y';
                break;
            case COMPLETION_COMPLETE_PASS:
                $code = 'completion-pass';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $code = 'completion-fail';
                break;
            default:
                return '?';
                break;
        }
        return get_string($code, 'core_completion');
    }

}
