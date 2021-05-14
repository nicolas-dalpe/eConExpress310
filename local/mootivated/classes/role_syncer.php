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
 * Role synchroniser.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

use context_system;

/**
 * Role synchroniser class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_syncer {

    protected $role;

    /**
     * Constructor.
     */
    public function __construct() {
        if (!helper::mootivated_role_exists()) {
            throw new coding_exception('The Mootivated User role does not exist.');
        }
        $this->role = helper::get_mootivated_role();
    }

    /**
     * Sync all users.
     *
     * @return void
     */
    public function sync_all_users() {
        global $DB;

        $added = 0;
        $role = helper::get_mootivated_role();
        $context = context_system::instance();

        $recordset = $DB->get_recordset_sql("
               SELECT u.id
                 FROM {user} u
            LEFT JOIN {role_assignments} ra
                   ON ra.userid = u.id
                  AND ra.roleid = :roleid
                  AND ra.contextid = :contextid
                WHERE ra.id IS NULL",
        [
            'roleid' => $role->id,
            'contextid' => $context->id
        ]);

        foreach ($recordset as $record) {
            if (isguestuser($record->id) || is_siteadmin($record->id)) {
                continue;
            }

            $added++;
            role_assign($role->id, $record->id, $context->id);
        }

        $recordset->close();

        return $added;
    }

    /**
     * Sync cohort users.
     *
     * Note that this does not remove anybody. It only ensures that all cohort
     * members are assigned the role.
     *
     * @param int $cohortid The cohort ID.
     * @return void
     */
    public function sync_cohort_users($cohortid) {
        global $DB;

        $added = 0;
        $removed = 0;
        $role = helper::get_mootivated_role();
        $context = context_system::instance();

        // Assign role to users in cohorts that are used for sections who are missing the role.
        $recordset = $DB->get_recordset_sql("
               SELECT u.id
                 FROM {cohort_members} cm
                 JOIN {user} u
                   ON cm.userid = u.id
            LEFT JOIN {role_assignments} ra
                   ON ra.userid = u.id
                  AND ra.roleid = :roleid
                  AND ra.contextid = :contextid
                WHERE cm.cohortid = :cohortid
                  AND ra.id IS NULL",
        [
            'roleid' => $role->id,
            'contextid' => $context->id,
            'cohortid' => $cohortid
        ]);

        foreach ($recordset as $record) {
            if (isguestuser($record->id) || is_siteadmin($record->id)) {
                continue;
            }

            $added++;
            role_assign($role->id, $record->id, $context->id);
        }
        $recordset->close();

        return [
            'added' => $added,
            'removed' => $removed
        ];
    }

    /**
     * Sync sections users.
     *
     * @return void
     */
    public function sync_sections_users() {
        global $DB;

        $added = 0;
        $removed = 0;
        $role = helper::get_mootivated_role();
        $context = context_system::instance();

        // Assign role to users in cohorts that are used for sections who are missing the role.
        $recordset = $DB->get_recordset_sql("
               SELECT u.id
                 FROM {cohort_members} cm
                 JOIN {cohort} c
                   ON c.id = cm.cohortid
                 JOIN {local_mootivated_school} s
                   ON s.cohortid = c.id
                 JOIN {user} u
                   ON cm.userid = u.id
            LEFT JOIN {role_assignments} ra
                   ON ra.userid = u.id
                  AND ra.roleid = :roleid
                  AND ra.contextid = :contextid
                WHERE ra.id IS NULL",
        [
            'roleid' => $role->id,
            'contextid' => $context->id
        ]);

        foreach ($recordset as $record) {
            if (isguestuser($record->id) || is_siteadmin($record->id)) {
                continue;
            }

            $added++;
            role_assign($role->id, $record->id, $context->id);
        }
        $recordset->close();

        // Unassign role to users that are not part of any school.
        $recordset = $DB->get_recordset_sql("
               SELECT u.id
                 FROM {role_assignments} ra
                 JOIN {user} u
                   ON ra.userid = u.id
            LEFT JOIN {cohort_members} cm
                   ON cm.userid = u.id
            LEFT JOIN {cohort} c
                   ON c.id = cm.cohortid
            LEFT JOIN {local_mootivated_school} s
                   ON s.cohortid = c.id
                WHERE ra.roleid = :roleid
                  AND ra.contextid = :contextid
             GROUP BY u.id, ra.id
               HAVING MAX(COALESCE(s.id, 0)) < 1",
        [
            'roleid' => $role->id,
            'contextid' => $context->id
        ]);

        foreach ($recordset as $record) {
            if (isguestuser($record->id) || is_siteadmin($record->id)) {
                continue;
            }

            $removed++;
            role_unassign($role->id, $record->id, $context->id);
        }
        $recordset->close();

        return [
            'added' => $added,
            'removed' => $removed
        ];
    }

}
