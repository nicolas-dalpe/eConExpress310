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
 * Notifier.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

use context_system;
use core_user;
use moodle_exception;
use stdClass;
use local_mootivated\helper;

/**
 * Notifier class.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier {

    protected $client;
    protected $usercache = ['username' => [], 'email' => []];
    protected $schoolresolver;

    /**
     * Constructor.
     *
     * @param client $client The client.
     * @param ischool_resolver $resolver The school resolver.
     */
    public function __construct(client $client, ischool_resolver $resolver) {
        $this->client = $client;
        $this->schoolresolver = $resolver;
    }

    /**
     * Fetch the notifications.
     *
     * @return array
     */
    public function fetch_notifications() {
        $schools = $this->get_active_schools();
        if (empty($schools)) {
            return [];
        }

        $args = [
            'school_pks' => array_values(array_map(function($school) {
                return $school->get_private_key();
            }, $schools))
        ];

        return $this->client->request('/school/list_notifications_by_pks', $args);
    }

    /**
     * Get active schools.
     *
     * TODO: We should be moving this method somewhere else!
     *
     * @return school[]
     */
    public function get_active_schools() {
        global $DB;

        if (!helper::uses_sections()) {
            $school = helper::get_global_school();
            if (!$school->is_setup() || !$school->get_send_username()) {
                return [];
            }
            return [$school];
        }

        // Find schools that have a cohort, members and a private key.
        $schools = [];
        $sql = "
            SELECT s.*
              FROM {local_mootivated_school} s
             WHERE s.privatekey != :privatekey
               AND s.cohortid != :nocohortid
               AND s.sendusername = :sendusername
               AND EXISTS (
                   SELECT 1
                     FROM {cohort_members} cm
                    WHERE cm.cohortid = s.cohortid
               )";
        $params = [
            'privatekey' => '',
            'nocohortid' => 0,
            'sendusername' => 1
        ];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $school = new school(0);
            $school->set_from_record($record);
            $schools[] = $school;
        }
        $recordset->close();

        return $schools;
    }

    /**
     * Report the result to the server.
     *
     * @param array $result The result as returned from send_notifications.
     */
    public function report_result(array $result) {
        return $this->client->request('/notification/results', array_merge($result, [
            'processor' => [
                'id' => 'local_mootivated'
            ]
        ]));
    }

    /**
     * Send a bunch of notifications.
     *
     * @param array $notifications The notifications.
     * @return array With errors and sent IDs.
     */
    public function send_notifications(array $notifications) {
        $sent = [];
        $errors = [];

        foreach ($notifications as $notification) {

            $usertype = $notification->user_doctype;
            if ($usertype == 'dashboard_user') {

                // Find the user by email.
                $user = $this->resolve_user_by_email($notification->user_email);
                if (!$user) {
                    $errors[] = ['id' => $notification->id, 'code' => 'email_not_found'];
                    continue;
                }

            } else if ($usertype == 'user') {
                if (empty($notification->user_username)) {
                    continue;
                }

                // Find the user.
                $user = $this->resolve_user_by_username($notification->user_username);
                if (!$user) {
                    $errors[] = ['id' => $notification->id, 'code' => 'username_not_found'];
                    continue;
                }

                // Confirm that they are who we are looking for.
                $school = $this->schoolresolver->get_by_member($user->id);
                if ($school->get_remote_user_id($user->id) != $notification->user_plugin_id) {
                    $errors[] = ['id' => $notification->id, 'code' => 'invalid_plugin_id'];
                    continue;
                }

            } else {
                continue;
            }


            // Ultimate check.
            if (!$user) {
                continue;
            }

            $message = new \core\message\message();
            $message->component = 'local_mootivated';
            $message->name = 'notification';
            $message->notification = 1;
            $message->userfrom = core_user::get_noreply_user();
            $message->userto = $user->id;
            $message->subject = $notification->subject;
            $message->fullmessage = $notification->message;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = format_text($notification->message, FORMAT_PLAIN, [
                'context' => context_system::instance()
            ]);
            $message->smallmessage = '';
            $result = message_send($message);

            if ($result !== false) {
                $sent[] = $notification->id;
            } else {
                $errors = ['id' => $notification->id, 'code' => 'message_send_failed'];
            }
        }

        return [
            'sent' => $sent,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve a user by email.
     *
     * @param string $email The email.
     * @return false|object
     */
    protected function resolve_user_by_email($email) {
        global $DB;
        if (!array_key_exists($email, $this->usercache['email'])) {
            $this->usercache['email'][$email] = $DB->get_record('user', ['email' => $email]);
        }
        return $this->usercache['email'][$email];
    }

    /**
     * Resolve a user by username.
     *
     * @param string $username The user name.
     * @return false|object
     */
    protected function resolve_user_by_username($username) {
        global $DB;
        if (!array_key_exists($username, $this->usercache['username'])) {
            $this->usercache['username'][$username] = $DB->get_record('user', ['username' => $username]);
        }
        return $this->usercache['username'][$username];
    }
}
