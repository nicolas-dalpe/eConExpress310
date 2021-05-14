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
 * School.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/filelib.php');

use cache;
use context_module;
use context_system;
use curl;
use moodle_exception;
use stdClass;
use local_mootivated\helper;
use local_mootivated\local\calculator\mod_points_calculator;
use local_mootivated\local\calculator\mod_points_calculator_stack;

/**
 * School class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class school {

    /** Reward method using events. */
    const METHOD_EVENT = 1;
    /** Reward method using completion, else events. */
    const METHOD_COMPLETION_ELSE_EVENT = 2;
    /** Reward method using completion. */
    const METHOD_COMPLETION = 4;
    /** Reward method using dashboard rules. */
    const METHOD_DASHBOARD_RULES = 8;

    /** @var array The default settings. */
    protected static $defaultsettings = [
        'cohortid' => 0,
        'privatekey' => '',
        'sendusername' => true,
        'maxactions' => 10,
        'timeframeformaxactions' => 60,
        'timebetweensameactions' => 3600,
        'rewardmethod' => self::METHOD_EVENT,
        'modcompletionrules' => '',
        'coursecompletionreward' => 30,
        'leaderboardenabled' => true,
    ];

    /** @var int The school internal ID. */
    protected $id;
    /** @var int The cohort ID. */
    protected $cohortid;
    /** @var string The private key. */
    protected $privatekey;
    /** @var bool Whether to send the username. */
    protected $sendusername;
    /** @var int Max actions. */
    protected $maxactions;
    /** @var int Time frame for max actions in seconds. */
    protected $timeframeformaxactions;
    /** @var int Time between same actions in seconds. */
    protected $timebetweensameactions;
    /** @var int Reward method. */
    protected $rewardmethod;
    /** @var string Mod completion rules. */
    protected $modcompletionrules;
    /** @var string Mod completion rules. */
    protected $coursecompletionreward;
    /** @var boolean Whether the leaderboard is enabled. */
    protected $leaderboardenabled;

    /** @var client The client to communicate with the server. */
    protected $client;

    /** @var string Mod completion rules. */
    protected $modcompletioncalculator;

    /** @var cache The login info cache. */
    protected $logininfocache;

    /**
     * Constructor.
     *
     * @param int|null $id The ID of the school.
     * @param array $settings Settings to set, or override with.
     */
    public function __construct($id, array $settings = array()) {
        $this->id = $id;
        $this->client = helper::get_client();

        if ($this->id) {
            $this->load();
        } else {
            $settings = array_merge(static::get_default_settings(), $settings);
        }

        $settings = array_intersect_key($settings, static::get_default_settings());
        foreach ($settings as $setting => $value) {
            $this->{$setting} = $value;
        }
    }

    /**
     * Add coins.
     *
     * This is a low level method to award coins to a user without any prechecks
     * whatsoever. In general, prefer {@link self::capture_event} when the coins
     * are awarded as part of the normal Moodle behaviour.
     *
     * You will typically only use this to force awards.
     *
     * @param int $userid The user ID.
     * @param int $coins The number of coins.
     * @param event|null $eventname The event triggering this, if any.
     * @return bool Whether we were successful.
     */
    public function add_coins($userid, $coins, \core\event\base $event = null) {
        global $CFG;

        $username = '';
        $firstname = '';
        $lastname = '';
        $email = '';

        if ($coins <= 0) {
            throw new \coding_exception('Invalid amount of coins to add');
        }

        if ($this->get_send_username()) {
            $user = \core_user::get_user($userid, 'username, firstname, lastname, email');

            $username = !empty($user->username) ? $user->username : '';
            $firstname = !empty($user->firstname) ? $user->firstname : '';
            $lastname = !empty($user->lastname) ? $user->lastname : '';
            $email = !empty($user->email) ? $user->email : '';
        }

        // Send to server.
        $data = array(
            'plugin_id' => $this->get_remote_user_id($userid),
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'num_coins' => $coins,
            'private_key' => $this->get_private_key(),
            'reason' => $event ? $event->eventname : ''
        );

        try {
            $this->request('/coins/add', $data, false);

        } catch (moodle_exception $e) {
            $debugdata = $data;
            $debugdata['private_key'] = substr($debugdata['private_key'], 0, 4) . '...' . substr($debugdata['private_key'], -4);
            debugging(sprintf('Server error calling /coins/add. Request: %s. Response: %s',
                json_encode($debugdata),
                $e->getMessage() . ' ' . ($CFG->debugdeveloper ? $e->debuginfo : '')
            ), DEBUG_DEVELOPER);

            return false;
        }

        // Record event.
        $gainedevent = \local_mootivated\event\coins_earned::create([
            'context' => $event ? $event->get_context() : context_system::instance(),
            'relateduserid' => $userid,
            'other' => [
                'amount' => $coins,
            ]
        ]);
        $gainedevent->trigger();

        return true;
    }

    /**
     * Capture an event.
     *
     * This method has to be called once a user has been validated and should
     * be rewarded for the event triggered. No validation is done at this stage.
     *
     * @param int $userid The user ID.
     * @param \core\event\base $event The event.
     * @param int $coins The number of coins.
     * @return void
     */
    public function capture_event($userid, \core\event\base $event, $coins) {
        global $CFG;

        // Log the event.
        $this->log_event($userid, $event, $coins);

        // Add the coins.
        $this->add_coins($userid, $coins, $event);
    }

    /**
     * Delete the school and associated data.
     *
     * @return void
     */
    public function delete() {
        global $DB;
        if (!$this->id) {
            return;
        }
        $DB->delete_records('local_mootivated_log', ['schoolid' => $this->id]);
        $DB->delete_records('local_mootivated_completion', ['schoolid' => $this->id]);
        $DB->delete_records('local_mootivated_school', ['id' => $this->id]);
        $this->id = null;
    }

    /**
     * Count the number of actions since epoch.
     *
     * @param int $userid User ID.
     * @param int $since Epoch.
     * @return int
     */
    public function get_action_count_since($userid, $since) {
        global $DB;
        $sql = 'schoolid = :id AND userid = :userid AND timecreated >= :since';
        return $DB->count_records_select('local_mootivated_log', $sql, ['id' => $this->id, 'userid' => $userid,
            'since' => $since]);
    }

    /**
     * Get a login token for the dashboard.
     *
     * This creates fetch a login token that can be used to authenticate
     * a user with the dashboard. We don't call the ->login() method in this
     * case because it returns too much information that we could contain
     * in QR code. Instead, we fetch the token, and will give it to the
     * Mobile app. The latter can then validate the token with the dashboard
     * and get the user information.
     *
     * We will also instruct the dashboard to attach some Moodle-specific
     * details to the auth token, so that we can transmit that information.
     *
     * Note that this implementation is very specific to our plugin and our
     * mobile apps, and we should not be used in different contexts.
     *
     * @return string
     */
    public function get_login_token() {
        global $USER;

        $user = $USER;
        $username = '';
        $firstname = '';
        $lastname = '';
        $email = '';

        if ($this->get_send_username()) {
            $username = !empty($user->username) ? $user->username : '';
            $firstname = !empty($user->firstname) ? $user->firstname : '';
            $lastname = !empty($user->lastname) ? $user->lastname : '';
            $email = !empty($user->email) ? $user->email : '';
        }

        $data = array(
            'plugin_id' => $this->get_remote_user_id($user->id),
            'token' => helper::get_mootivated_token(),
            'private_key' => $this->get_private_key(),
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'can_redeem_items' => helper::can_redeem_store_items($user)
        );

        $res = $this->request('/user/get_login_token', $data, false);
        return $res;
    }

    /**
     * Get the cohort ID.
     *
     * @return int
     */
    public function get_cohort_id() {
        return (int) $this->cohortid;
    }

    /**
     * The object computing points for completing an activity.
     *
     * @return mod_points_calculator
     */
    public function get_completion_points_calculator_by_mod() {
        if (!$this->modcompletioncalculator) {
            $rules = null;
            $customcalculator = null;
            if (!empty($this->modcompletionrules)) {
                $rules = json_decode($this->modcompletionrules);
                if (is_array($rules)) {
                    $customcalculator = new mod_points_calculator($rules, null);
                }
            }

            $calculator = self::get_default_completion_points_calculator_by_mod();
            if (!empty($customcalculator)) {
                $calculator = new mod_points_calculator_stack([$customcalculator, $calculator]);
            }

            $this->modcompletioncalculator = $calculator;
        }

        return $this->modcompletioncalculator;
    }

    /**
     * Get the course completion reward.
     *
     * @return int|null
     */
    public function get_course_completion_reward() {
        return $this->coursecompletionreward;
    }

    /**
     * Get the host.
     *
     * @return string
     */
    public function get_host() {
        return $this->client->get_host();
    }

    /**
     * Get the current ID, or 0 when unset.
     *
     * @return int
     */
    public function get_id() {
        return (int) $this->id;
    }

    /**
     * Get the login info.
     *
     * @param object $user The user.
     * @return object
     */
    public function get_login_info($user = null) {
        global $USER;
        $user = $user ? $user : $USER;

        $cache = $this->get_login_info_cache();
        $info = $cache->get($user->id);

        if ($info === false || $info->api_key_expires < (time() - 60)) {
            $info = $this->login($user, helper::get_mootivated_token(), current_language());
            $cache->set($user->id, $info);
        }

        return (object) $info;
    }

    /**
     * Get the login info cache.
     *
     * The cache is indexed by user ID.
     *
     * @return cache
     */
    protected function get_login_info_cache() {
        if (!$this->logininfocache) {
            $this->logininfocache = cache::make('local_mootivated', 'logininfo');
        }
        return $this->logininfocache;
    }

    /**
     * Get max actions.
     *
     * @return int
     */
    public function get_max_actions() {
        return (int) $this->maxactions;
    }

    /**
     * Get private key.
     *
     * @return string
     */
    public function get_private_key() {
        return $this->privatekey;
    }

    /**
     * Get the database-like record.
     *
     * @return stdClass
     */
    public function get_record() {
        $record = new stdClass();
        foreach (static::get_default_settings() as $setting => $value) {
            $record->{$setting} = $this->{$setting};
        }
        if ($this->id) {
            $record->id = $this->id;
        }
        return $record;
    }

    /**
     * Get the remote user ID hash.
     *
     * This is the ID we send to the server for uniquely identifying the user.
     *
     * @param int $userid The user ID.
     * @return string
     */
    public function get_remote_user_id($userid) {
        return md5(get_site_identifier() . '_' . $userid);
    }

    /**
     * Get the reward method.
     *
     * @return int
     */
    public function get_reward_method() {
        return (int) $this->rewardmethod;
    }

    /**
     * Get whether to send the user name.
     *
     * @return bool
     */
    public function get_send_username() {
        return (bool) $this->sendusername;
    }

    /**
     * Get the server user ID.
     *
     * This is the ID that is used on the server.
     *
     * @param stdClass $user The user.
     * @return string
     */
    public function get_server_user_id($user) {
        return $this->get_login_info($user)->user_id;
    }

    /**
     * Get time between same actions.
     *
     * @return int
     */
    public function get_time_between_same_actions() {
        return (int) $this->timebetweensameactions;
    }

    /**
     * Get time frame for max actions.
     *
     * @return int
     */
    public function get_time_frame_for_max_actions() {
        return (int) $this->timeframeformaxactions;
    }

    /**
     * Retrieve the amount of coins a user has from the server.
     *
     * @param int $userid The user ID.
     * @return int
     */
    public function get_user_coins($userid) {
        $data = [
            'plugin_id' => $this->get_remote_user_id($userid),
            'private_key' => $this->get_private_key()
        ];

        try {
            $json = $this->request('/coins/get', $data);
        } catch (moodle_exception $e) {
            debugging('Error while fetching coins: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        if ($json === null || !isset($json->coins)) {
            // Whoops, there was a problem...
            return 0;
        }

        return $json->coins;
    }

    /**
     * Check whether an action was done since epoch.
     *
     * @param int $userid The user ID.
     * @param \core\event\base $event The event.
     * @param int $since Epoch.
     * @return bool
     */
    public function has_done_action_since($userid, \core\event\base $event, $since) {
        global $DB;

        $sql = 'schoolid = :id
            AND userid = :userid
            AND contextid = :contextid
            AND eventname = :eventname
            AND timecreated >= :since';

        $params = [
            'id' => $this->id,
            'userid' => $userid,
            'contextid' => $event->contextid,
            'eventname' => $event->eventname,
            'since' => $since
        ];

        if ($event->objectid === null) {
            $sql .= ' AND objectid IS NULL';
        } else {
            $sql .= ' AND objectid = :objectid';
            $params['objectid'] = $event->objectid;
        }

        if ($event->relateduserid === null) {
            $sql .= ' AND relateduserid IS NULL';
        } else {
            $sql .= ' AND relateduserid = :relateduserid';
            $params['relateduserid'] = $event->relateduserid;
        }

        return $DB->count_records_select('local_mootivated_log', $sql, $params) > 0;
    }

    /**
     * Check whether a user has exceeded the threshold.
     *
     * @param int $userid The user ID.
     * @param \core\event\base $event The event.
     * @return bool
     */
    public function has_exceeded_threshold($userid, \core\event\base $event) {
        $now = time();
        $maxactions = $this->get_max_actions();

        $actionsdone = $this->get_action_count_since($userid, $now - $this->get_time_frame_for_max_actions());
        if ($actionsdone >= $maxactions) {
            return true;
        }

        return $this->has_done_action_since($userid, $event, $now - $this->get_time_between_same_actions());
    }

    /**
     * Check whether the school contains the user.
     *
     * @param int $userid The user ID.
     * @return bool
     */
    public function has_member($userid) {
        return $this->get_cohort_id() && cohort_is_member($this->get_cohort_id(), $userid);
    }

    /**
     * Whether course completion rewards are enabled.
     *
     * @return bool
     */
    public function is_course_completion_reward_enabled() {
        return $this->coursecompletionreward > 0;
    }

    /**
     * Whether the leaderboard is enabled.
     *
     * @return bool
     */
    public function is_leaderboard_enabled() {
        return (bool) $this->leaderboardenabled;
    }

    /**
     * Whether the reward method is completion.
     *
     * @return bool
     */
    public function is_reward_method_completion() {
        return $this->get_reward_method() === self::METHOD_COMPLETION;
    }

    /**
     * Whether the reward method is completion, else event.
     *
     * @return bool
     */
    public function is_reward_method_completion_else_event() {
        return $this->get_reward_method() === self::METHOD_COMPLETION_ELSE_EVENT;
    }

    /**
     * Whether the reward method is dashboard rules.
     *
     * @return bool
     */
    public function is_reward_method_dashboard_rules() {
        return $this->get_reward_method() === self::METHOD_DASHBOARD_RULES;
    }

    /**
     * Whether the reward method is completion, else event.
     *
     * @return bool
     */
    public function is_reward_method_event() {
        return $this->get_reward_method() === self::METHOD_EVENT;
    }

    /**
     * Whether completion are allowed for rewards.
     *
     * @return bool
     */
    public function is_reward_method_completion_permitted() {
        $method = $this->get_reward_method();
        return $method != self::METHOD_EVENT && $method != self::METHOD_DASHBOARD_RULES;
    }

    /**
     * Whether events are allowed for rewards.
     *
     * @return bool
     */
    public function is_reward_method_event_permitted() {
        $method = $this->get_reward_method();
        return $method == self::METHOD_EVENT || $method == self::METHOD_COMPLETION_ELSE_EVENT;
    }

    /**
     * Whether the school is set-up for capturing events.
     *
     * @return bool
     */
    public function is_setup() {
        return $this->get_host() && $this->get_private_key();
    }

    /**
     * Load school from database.
     *
     * @return void
     */
    public function load() {
        global $DB;
        if ($this->id) {
            $record = $DB->get_record('local_mootivated_school', ['id' => $this->id]);
            if (!$record) {
                return;
            }
            $this->set_from_record($record);
        }
    }

    /**
     * Log an event.
     *
     * @param int $userid The user.
     * @param \core\event\base $event The event.
     * @param int $coins The number of coins given.
     * @return void
     */
    public function log_event($userid, \core\event\base $event, $coins) {
        global $DB;
        $data = $event->get_data();
        $data['schoolid'] = $this->id;
        $data['userid'] = $userid;
        $data['coins'] = $coins;
        $DB->insert_record('local_mootivated_log', $data);
    }

    /**
     * Log that a user was rewarded for completion.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @param int $cmid The CM ID, or 0.
     * @param int $state The completion state.
     * @return void
     */
    public function log_user_was_rewarded_for_completion($userid, $courseid, $cmid, $state) {
        global $DB;
        $data = [
            'schoolid' => $this->get_id(),
            'userid' => $userid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'state' => $state,
            'timecreated' => time()
        ];
        $DB->insert_record('local_mootivated_completion', $data);
    }

    /**
     * Logs a remote user id.
     *
     * This method ensures that the user is registered remotely.
     *
     * @param stdClass $user The user.
     * @param string $token The token.
     * @param string $langcode The language code.
     * @return string
     */
    public function login(stdClass $user, $token, $langcode = '') {
        $username = '';
        $firstname = '';
        $lastname = '';
        $email = '';

        if ($this->get_send_username()) {
            $username = !empty($user->username) ? $user->username : '';
            $firstname = !empty($user->firstname) ? $user->firstname : '';
            $lastname = !empty($user->lastname) ? $user->lastname : '';
            $email = !empty($user->email) ? $user->email : '';
        }

        $data = array(
            'token' => $token,
            'plugin_id' => $this->get_remote_user_id($user->id),
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'private_key' => $this->get_private_key(),
            'language_code' => $langcode
        );

        $res = $this->request('/user/logged_in', $data, false);
        return $res;
    }

    /**
     * Sends a request to the server.
     *
     * @param string $uri The URI.
     * @param array $data The data.
     * @param bool $catch Whether to catch the exceptions.
     * @return result
     */
    protected function request($uri, $data, $catch = true) {
        try {
            $response = $this->client->request($uri, $data);
        } catch (moodle_exception $e) {
            if (!$catch) {
                throw $e;
            }
            $info = json_decode($e->debuginfo);
            debugging($info->error, DEBUG_DEVELOPER);
            $response = $info;
        }
        return $response;
    }

    /**
     * Create, or update, the school.
     *
     * @return void
     */
    public function save() {
        global $DB;
        $record = $this->get_record();
        if (!$this->id) {
            $this->id = $DB->insert_record('local_mootivated_school', $record);
        } else {
            $DB->update_record('local_mootivated_school', $record);
        }
    }

    /**
     * Send an event.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public function send_event(\core\event\base $event) {
        $courseid = $event->courseid;
        $postsend = function() {};

        if ($event instanceof \core\event\course_completed) {
            $userid = $event->relateduserid;
            if ($this->was_user_rewarded_for_completion($userid, $event->courseid, 0)) {
                return;
            }
            $postsend = function() use ($userid, $event) {
                $this->log_user_was_rewarded_for_completion($userid, $event->courseid, 0, COMPLETION_COMPLETE);
            };

        } else if ($event instanceof \core\event\course_module_completion_updated) {
            $userid = $event->relateduserid;
            $completiondata = $event->get_record_snapshot('course_modules_completion', $event->objectid);

            if ($completiondata->completionstate != COMPLETION_COMPLETE
                    && $completiondata->completionstate != COMPLETION_COMPLETE_PASS) {
                return;
            }

            $cmid = $event->get_context()->instanceid;
            if ($this->was_user_rewarded_for_completion($userid, $courseid, $cmid)) {
                return;
            }

            $postsend = function() use ($userid, $event, $completiondata, $cmid) {
                $this->log_user_was_rewarded_for_completion(
                    $userid,
                    $event->courseid,
                    $cmid,
                    $completiondata->completionstate
                );
            };

        } else {
            debugging('Invalid type of event received in send_event.');
            return;
        }

        $username = '';
        $firstname = '';
        $lastname = '';
        $email = '';

        if ($this->get_send_username()) {
            $user = \core_user::get_user($userid, 'username, firstname, lastname, email');
            $username = !empty($user->username) ? $user->username : '';
            $firstname = !empty($user->firstname) ? $user->firstname : '';
            $lastname = !empty($user->lastname) ? $user->lastname : '';
            $email = !empty($user->email) ? $user->email : '';
        }

        // Prepare action data.
        $eventcontext = $event->get_context();
        $actiondata = [
            'eventname' => $event->eventname,
            'courseid' => (int) $event->courseid,
            'contextid' => (int) $event->contextid,
            'contextlevel' => (int) $eventcontext->contextlevel
        ];

        // Enhance with module stuff.
        if ($eventcontext instanceof context_module) {
            $actiondata['cmid'] = (int) $eventcontext->instanceid;
            $actiondata['modulename'] = helper::get_module_name_from_context($eventcontext);
        }

        // Send to server.
        $payload = array(
            'private_key' => $this->get_private_key(),
            'plugin_id' => $this->get_remote_user_id($userid),
            'username' => $username,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'actions' => [[
                'type' => 'moodle_event',
                'data' => $actiondata,
            ]]
        );
        $response = $this->request('/user/actions/capture', $payload);

        // Execute the post send command.
        $postsend();

        // Throw event as capture_event does.
        if (!empty($response->coins_earned)) {
            $event = \local_mootivated\event\coins_earned::create([
                'context' => $event->get_context(),
                'relateduserid' => $userid,
                'other' => [
                    'amount' => $response->coins_earned,
                ]
            ]);
            $event->trigger();
        }
    }

    /**
     * Populates the school from a database-like record.
     *
     * @param stdClass $data The data.
     */
    public function set_from_record(stdClass $data) {
        $settings = static::get_default_settings();
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }
            $this->{$key} = $value;
        }
    }

    /**
     * Was the user already rewarded for a completion?
     *
     * We don't really need to add the school ID in here, but it doesn't hurt.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @param int $cmid The CM ID.
     * @return bool
     */
    public function was_user_rewarded_for_completion($userid, $courseid, $cmid = 0) {
        global $DB;
        return $DB->record_exists('local_mootivated_completion', [
            'schoolid' => $this->get_id(),
            'userid' => $userid,
            'courseid' => $courseid,
            'cmid' => $cmid
        ]);
    }

    /**
     * Get the default settings.
     *
     * @return array
     */
    public static function get_default_settings() {
        return static::$defaultsettings;
    }

    /**
     * Get the default completion points calculator.
     *
     * @return array
     */
    protected static function get_default_completion_points_calculator_by_mod() {
        return new mod_points_calculator([
            (object) ['mod' => 'quiz', 'points' => 15],
            (object) ['mod' => 'lesson', 'points' => 15],
            (object) ['mod' => 'scorm', 'points' => 15],
            (object) ['mod' => 'assign', 'points' => 15],
            (object) ['mod' => 'forum', 'points' => 15],

            (object) ['mod' => 'feedback', 'points' => 10],
            (object) ['mod' => 'questionnaire', 'points' => 10],
            (object) ['mod' => 'workshop', 'points' => 10],
            (object) ['mod' => 'glossary', 'points' => 10],
            (object) ['mod' => 'database', 'points' => 10],
            (object) ['mod' => 'journal', 'points' => 10],
            (object) ['mod' => 'hotpot', 'points' => 10],

            (object) ['mod' => 'book', 'points' => 2],
            (object) ['mod' => 'resource', 'points' => 2],
            (object) ['mod' => 'folder', 'points' => 2],
            (object) ['mod' => 'imscp', 'points' => 2],
            (object) ['mod' => 'label', 'points' => 2],
            (object) ['mod' => 'page', 'points' => 2],
            (object) ['mod' => 'url', 'points' => 2]
        ], 5);
    }

    /**
     * Get a menu of schools.
     *
     * @return array Keys are IDs, values are names.
     */
    public static function get_menu() {
        global $DB;

        // When we don't use section, we return a global section.
        if (!helper::uses_sections()) {
            $school = helper::get_global_school();
            $result = [];
            $result[$school->get_id()] = get_string('collectionsettings', 'local_mootivated');
            return $result;
        }

        $sql = 'SELECT s.id, c.name
                  FROM {local_mootivated_school} s
             LEFT JOIN {cohort} c
                    ON s.cohortid = c.id
              ORDER BY c.name';
        $records = $DB->get_records_sql($sql);
        return array_combine(array_keys($records), array_map(function($record) {
            return !empty($record->name) ? format_string($record->name) : get_string('schooln', 'local_mootivated', $record->id);
        }, $records));
    }

    /**
     * Load a school from a user ID.
     *
     * This returns the first school the user is part of.
     *
     * @param int $userid The user ID.
     * @return school
     */
    public static function load_from_member($userid) {
        global $DB;
        $sql = 'SELECT s.id
                  FROM {cohort_members} cm
                  JOIN {local_mootivated_school} s
                    ON s.cohortid = cm.cohortid
                 WHERE cm.userid = :userid
              ORDER BY cm.cohortid ASC';
        $id = $DB->get_field_sql($sql, ['userid' => $userid], IGNORE_MULTIPLE);
        return $id ? new static($id) : null;
    }

    /**
     * Load a school from a private key.
     *
     * @param string $key The private key.
     * @return school
     */
    public static function load_from_private_key($key) {
        global $DB;
        $id = $DB->get_field('local_mootivated_school', 'id', ['privatekey' => $key], IGNORE_MULTIPLE);
        return $id ? new static($id) : null;
    }
}
