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
 * User pusher.
 *
 * @package    local_mootivated
 * @copyright  2019 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_exception;

/**
 * User pusher class.
 *
 * This is responsible for pushing the users to the dashboard.
 *
 * @package    local_mootivated
 * @copyright  2019 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_pusher {

    /** @var int Push chunk size. */
    protected $chunksize = 100;
    /** @var client The client. */
    protected $client;
    /** @var object The Mootivated User role. */
    protected $role;
    /** @var ischool_resolver The school resolver. */
    protected $schoolresolver;

    /**
     * Constructor.
     *
     * @param ischool_resolver $schoolresolver The school resolver.
     * @param client $client The client.
     * @param stdClass $role The Mootivated User role.
     */
    public function __construct($schoolresolver, $client, $role) {
        $this->schoolresolver = $schoolresolver;
        $this->client = $client;
        $this->role = $role;
    }

    /**
     * Whether we've got users to push.
     *
     * @return bool
     */
    public function count_queue() {
        global $DB;
        return $DB->count_records_select('local_mootivated_userspush', '', null, 'COUNT(DISTINCT userid)');
    }

    /**
     * Whether we've got users to push.
     *
     * @return bool
     */
    public function has_queue() {
        global $DB;
        return $DB->record_exists('local_mootivated_userspush', []);
    }

    /**
     * Push a chunk of users.
     *
     * @return void
     */
    public function push_chunk() {
        global $DB;

        $sql = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email, ra.id as roleassignmentid
                  FROM {local_mootivated_userspush} up
                  JOIN {user} u
                    ON u.id = up.userid
             LEFT JOIN {role_assignments} ra
                    ON ra.userid = u.id
                   AND ra.roleid = :roleid
                   AND ra.contextid = :contextid
              ORDER BY u.id";

        $params = [
            'roleid' => $this->role->id,
            'contextid' => context_system::instance()->id
        ];

        $userids = [];      // All the user IDs.
        $users = [];        // The users to push.

        // Loop over each user and queue them for pushing if need be. Note that we purposely
        // do not fetch the school efficiently, we use the resolver as it guarantees that we
        // have the right school, and access to all the information needed.
        $records = $DB->get_records_sql($sql, $params, 0, $this->chunksize);
        foreach ($records as $user) {
            $userids[] = $user->id;

            // Check whether the user has the right role.
            if (empty($user->roleassignmentid)) {
                continue;
            }

            // Does the user belong to a school.
            $school = $this->schoolresolver->get_by_member($user->id);
            if (!$school || !$school->is_setup()) {
                continue;
            }

            $data = [
                'plugin_id' => $school->get_remote_user_id($user->id),
                'private_key' => $school->get_private_key(),
                'firstname' => '',
                'lastname' => '',
                'username' => '',
                'email' => '',
            ];

            if ($school->get_send_username()) {
                $data['username'] = !empty($user->username) ? $user->username : '';
                $data['firstname'] = !empty($user->firstname) ? $user->firstname : '';
                $data['lastname'] = !empty($user->lastname) ? $user->lastname : '';
                $data['email'] = !empty($user->email) ? $user->email : '';
            }

            $users[] = $data;
        }

        try {
            // Push the users.
            if (!empty($users)) {
                $this->client->request('/users/create', $users);
            }

            // Delete the entries.
            $DB->delete_records_list('local_mootivated_userspush', 'userid', $userids);

        } catch (moodle_exception $e) {
            debugging('An error occurred while pushing users: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Queue a user needing a push.
     *
     * @param int $userid The user ID.
     * @return void
     */
    public function queue($userid) {
        global $DB;
        $DB->insert_record('local_mootivated_userspush', (object) ['userid' => $userid]);
    }

    /**
     * Queue a whole cohort.
     *
     * @param int $cohortid The whole cohort ID.
     * @return void
     */
    public function queue_cohort($cohortid) {
        global $DB;
        $sql = "INSERT INTO {local_mootivated_userspush} (userid)
                SELECT cm.userid
                  FROM {cohort_members} cm
                 WHERE cm.cohortid = :cohortid";
        $DB->execute($sql, ['cohortid' => $cohortid]);
    }

    /**
     * Queue everyone.
     *
     * This is only intended to be used when sections are not used.
     * It will add all users that have the Mootivated User role.
     *
     * @return void
     */
    public function queue_everyone() {
        global $DB;
        $userssql = "SELECT u.id
                      FROM {user} u
                 LEFT JOIN {role_assignments} ra
                        ON ra.userid = u.id
                       AND ra.roleid = :roleid
                       AND ra.contextid = :contextid
                     WHERE ra.id IS NOT NULL
                       AND u.deleted = 0
                       AND u.confirmed = 1
                       AND u.suspended = 0";
        $params = [
            'roleid' => $this->role->id,
            'contextid' => context_system::instance()->id
        ];
        $sql = "INSERT INTO {local_mootivated_userspush} (userid) $userssql";
        $DB->execute($sql, $params);
    }

}
