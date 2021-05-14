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
 * @package   local_mootivated
 * @copyright 2016 Mootivation Technologies Corp.
 * @author    Mootivation Technologies Corp.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

// Conditionally include completion lib.
if (!empty($CFG->enablecompletion)) {
    require_once($CFG->libdir . '/completionlib.php');
}

use completion_info;
use context;
use context_course;
use context_module;
use context_system;
use context_user;
use course_modinfo;
use moodle_exception;
use stdClass;

/**
 * Mootivated helper class.
 *
 * @package    local_mootivated
 * @copyright  2016 Mootivation Technologies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** Role name. */
    const ROLE_SHORTNAME = 'mootivateduser';

    /** @var ischool_resolver Used to resolve a school. */
    protected static $schoolrevolver = null;

    /** @var user_pusher Used to push users. */
    protected static $userpusher = null;

    /**
     * Returns whether or not admins can earn points.
     *
     * @return bool
     */
    public static function admins_can_earn() {
        $value = get_config('local_mootivated', 'adminscanearn');
        return !empty($value);
    }

    /**
     * Whether automatically assigning the Mootivated User role is allowed.
     *
     * @return bool
     */
    public static function allow_automatic_role_assignment() {
        $autoassign = get_config('local_mootivated', 'disableautoroleassign');
        return empty($autoassign);
    }

    /**
     * Can the user login with the server.
     *
     * @param stdClass $user The user.
     * @return bool
     */
    public static function can_login(stdClass $user) {
        return has_capability('local/mootivated:login', context_system::instance(), $user);
    }

    /**
     * Can redeem store items.
     *
     * @param stdClass $user The user.
     * @return bool.
     */
    public static function can_redeem_store_items(stdClass $user) {
        $cap = 'local/mootivated:redeem_store_items';
        $sysctx = context_system::instance();
        $userctx = context_user::instance($user->id);

        // This checks whether the capability is given at user or system context. For legacy
        // reason we check the user context, but it should not be possible to assign it there.
        if (has_capability($cap, $userctx, $user)) {
            return true;
        }

        // Now we need to check if the user has the capability in any course. Yes, it looks
        // terribly inefficient, but I suggest you look at various functions in enrollib...
        $courses = enrol_get_all_users_courses($user->id, true, 'id');
        foreach ($courses as $course) {
            if (has_capability($cap, context_course::instance($course->id), $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Can the user SSO to the dasboard?
     *
     * @param stdClass $user The user.
     * @return bool
     */
    public static function can_sso_to_dashboard(stdClass $user = null) {
        global $USER;
        $user = $user ? $user : $USER;
        return has_capability('local/mootivated:ssodashboard', context_system::instance(), $user);
    }

    /**
     * Can the user earn.
     *
     * Note that this is unlikely to return true when the context is not a course context
     * because students are given the capability to earn coins, not the mootivated user role.
     *
     * Therefore, we may need to improve this method to return true when the user is
     * assigned the mootivated user role in the system context, if the context passed
     * is the system context. That is what some add-ons do, however this may lead to awarding
     * points to non-student users that use the Mobile app, etc.
     *
     * @param int $userid The user ID.
     * @param context $context The context.
     * @return bool
     */
    public static function can_user_earn_coins($userid, context $context) {

        // If non-logged in users, guests or admin, deny.
        if (!$userid || isguestuser($userid) || (!static::admins_can_earn() && is_siteadmin($userid))) {
            return false;
        }

        // Check has capability in context.
        if (!has_capability('local/mootivated:earncoins', $context, $userid)) {
            return false;
        }

        return true;
    }

    /**
     * Create the mootivated role.
     *
     * The role is required in order to allow user to create WS token for the Mootivated service.
     * Such service grants access to the few external functions needed for the system to work.
     *
     * Additionally, this role also contain the capability to determine whether they can login or
     * not. It's unlikely that this would be turned off, but it gives flexibility to the admin. For
     * instance if users have the capability to create tokens and use rest, but shouldn't be able
     * to login to Mootivated.
     *
     * @return void
     */
    public static function create_mootivated_role() {
        global $DB;

        $contextid = context_system::instance()->id;
        $roleid = create_role(get_string('mootivatedrole', 'local_mootivated'), static::ROLE_SHORTNAME,
            get_string('mootivatedroledesc', 'local_mootivated'));

        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        assign_capability('webservice/rest:use', CAP_ALLOW, $roleid, $contextid, true);
        assign_capability('moodle/webservice:createtoken', CAP_ALLOW, $roleid, $contextid, true);
        assign_capability('local/mootivated:login', CAP_ALLOW, $roleid, $contextid, true);

        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], IGNORE_MISSING);
        if ($managerroleid) {
            if (function_exists('core_role_set_override_allowed')) {
                core_role_set_override_allowed($managerroleid, $roleid);
            } else {
                allow_override($managerroleid, $roleid);
            }

            if (function_exists('core_role_set_assign_allowed')) {
                core_role_set_assign_allowed($managerroleid, $roleid);
            } else {
                allow_assign($managerroleid, $roleid);
            }

            if (function_exists('core_role_set_switch_allowed')) {
                core_role_set_switch_allowed($managerroleid, $roleid);
            } else {
                allow_switch($managerroleid, $roleid);
            }
        }
    }

    /**
     * Return whether the mootivated role exists.
     *
     * @return bool
     */
    public static function mootivated_role_exists() {
        global $DB;
        return $DB->record_exists('role', array('shortname' => static::ROLE_SHORTNAME));
    }

    /**
     * Get the mootivated role.
     *
     * @return stdClass
     */
    public static function get_mootivated_role() {
        global $DB;
        return $DB->get_record('role', ['shortname' => static::ROLE_SHORTNAME], '*', MUST_EXIST);
    }

    /**
     * Is section vs. section leaderblard enabled.
     *
     * @return bool
     */
    public static function is_section_vs_section_leaderboard_enabled() {
        $setting = get_config('local_mootivated', 'svsleaderboardenabled');
        return !empty($setting);
    }

    /**
     * Is SSO enabled?
     *
     * @return bool
     */
    public static function is_sso_to_dashboard_enabled() {
        // Maybe we want a toggle later on.
        $host = get_config('local_mootivated', 'server_ip');
        return !empty($host);
    }

    /**
     * Last sync time.
     *
     * @return int
     */
    public static function mootivated_role_last_synced() {
        return (int) get_config('local_mootivated', 'lastrolesync');
    }

    /**
     * Did we ever sync the role?
     *
     * @return bool
     */
    public static function mootivated_role_was_ever_synced() {
        return (bool) get_config('local_mootivated', 'lastrolesync');
    }

    /**
     * Is syncing scheduled?
     *
     * @return bool
     */
    public static function adhoc_role_sync_scheduled() {
        // Value 1 means running or scheduled, 0 means neither.
        return (bool) get_config('local_mootivated', 'adhocrolesync');
    }

    /**
     * Schedule the adhoc role sync.
     *
     * @return void
     */
    public static function schedule_mootivated_role_sync() {
        set_config('adhocrolesync', 1, 'local_mootivated');
        $task = new task\adhoc_role_sync();
        $task->set_component('local_mootivated');
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Is queue users scheduled?
     *
     * @return bool
     */
    public static function adhoc_queue_users_for_push_scheduled() {
        // Value 1 means running or scheduled, 0 means neither.
        return (bool) get_config('local_mootivated', 'adhocqueueuserspush');
    }

    /**
     * Schedule the adhoc queue users.
     *
     * @return void
     */
    public static function schedule_queue_user_for_push() {
        set_config('adhocqueueuserspush', 1, 'local_mootivated');
        $task = new task\adhoc_queue_users_for_push();
        $task->set_component('local_mootivated');
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Last queue time.
     *
     * @return int
     */
    public static function when_users_for_push_were_queued() {
        return (int) get_config('local_mootivated', 'lastqueueuserspush');
    }

    /**
     * Return whether webservices are enabled.
     *
     * @return bool
     */
    public static function webservices_enabled() {
        global $CFG;
        return !empty($CFG->enablewebservices);
    }

    /**
     * Enable webservices.
     *
     * @return void
     */
    public static function enable_webservices() {
        set_config('enablewebservices', 1);
    }

    /**
     * Return whether REST is enabled.
     *
     * @return bool
     */
    public static function rest_enabled() {
        global $CFG;
        $protocols = !empty($CFG->webserviceprotocols) ? explode(',', $CFG->webserviceprotocols) : [];
        return in_array('rest', $protocols);
    }

    /**
     * Enable the REST protocol.
     *
     * @return void
     */
    public static function enable_rest() {
        global $CFG;
        $protocols = !empty($CFG->webserviceprotocols) ? explode(',', $CFG->webserviceprotocols) : [];
        $protocols[] = 'rest';
        $protocols = array_unique($protocols);
        set_config('webserviceprotocols', implode(',', $protocols));
    }

    /**
     * Get a Mootivated token for the current user.

     * @return string
     */
    public static function get_mootivated_token() {
        global $CFG, $DB;

        if (array_key_exists('totara', \core_component::get_plugin_types())) {
            // At this point, we do not generate tokens for Totara. The API below is not available at this
            // stage so we will simply fake the token for now.
            return 'notokentotara';

        } else if (is_siteadmin()) {
            // We cannot create a token for an admin user, so we return a fake one here.
            return 'notokenadmin';
        }

        require_once($CFG->libdir . '/externallib.php');
        $service = $DB->get_record('external_services', ['shortname' => 'local_mootivated', 'enabled' => 1], '*', MUST_EXIST);
        if (!function_exists('external_generate_token_for_current_user')) {
            throw new moodle_exception('cannotcreatetoken', 'webservice', '', $service->shortname);
        }

        $token = external_generate_token_for_current_user($service);
        external_log_token_request($token);

        return $token->token;
    }

    /**
     * Quick set-up.
     *
     * Enables webservices, rest and creates the mootivated role.
     *
     * @return void
     */
    public static function quick_setup() {
        if (!static::webservices_enabled()) {
            static::enable_webservices();
        }
        if (!static::rest_enabled()) {
            static::enable_rest();
        }
        if (!static::mootivated_role_exists()) {
            static::create_mootivated_role();
        }
        if (!static::mootivated_role_was_ever_synced()) {
            static::schedule_mootivated_role_sync();
        }
    }

    /**
     * Delete old log entries.
     *
     * @param int $epoch Delete everything before that timestamp.
     * @return void
     */
    public static function delete_logs_older_than($epoch) {
        global $DB;
        $DB->delete_records_select('local_mootivated_log', 'timecreated < :timecreated', ['timecreated' => $epoch]);
    }

    /**
     * Observe the events, and dispatch them if necessary.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function observer(\core\event\base $event) {
        global $CFG;

        static $allowedcontexts = array(CONTEXT_COURSE, CONTEXT_MODULE);

        if ($event->component === 'local_mootivated') {
            // Skip own events.
            return;
        } else if ($event->anonymous) {
            // Skip all the events marked as anonymous.
            return;
        } else if (!in_array($event->contextlevel, $allowedcontexts)) {
            // Ignore events that are not in the right context.
            return;
        } else if ($event->is_restored()) {
            // Ignore events that are restored.
            return;
        } else if (!$event->get_context()) {
            // Sometimes the context does not exist, not sure when...
            return;
        }

        if ($event->edulevel !== \core\event\base::LEVEL_PARTICIPATING
                && !($event instanceof \core\event\course_completed)) {

            // Ignore events that are not participating, or course completion.
            return;
        }

        // Check target.
        $userid = static::get_event_target_user($event);

        // Check if can earn coins.
        if (!static::can_user_earn_coins($userid, $event->get_context())) {
            return;
        }

        // Keep the event, and proceed.
        static::handle_event($event);
    }

    /**
     * Handle an event.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    protected static function handle_event(\core\event\base $event) {
        global $CFG;

        $userid = static::get_event_target_user($event);

        // Don't use completion_info::is_enabled_for_site() because we only include the library when completion is enabled.
        $completionenabled = !empty($CFG->enablecompletion);
        $iscoursecompleted = $completionenabled && $event instanceof \core\event\course_completed;

        // Check if we could fall in the category of a dashboard rules setup. Because at the
        // moment we only target those events so we can assume that we may be using this.
        if ($iscoursecompleted || $event instanceof \core\event\course_module_completion_updated) {
            $school = self::get_school_resolver()->get_by_member($userid);

            // No school, no chocolate.
            if (!$school || !$school->is_setup()) {
                return;
            }

            // Ok, we can proceed.
            if ($school->is_reward_method_dashboard_rules()) {
                $school->send_event($event);
                return;
            }

            // If not, let's just continue...
        }

        // Early catch the course completed event.
        if ($iscoursecompleted) {
            static::handle_course_completed_event($event);
            return;
        }

        // Just making sure we're not letting unexpected events through.
        if ($event->edulevel !== \core\event\base::LEVEL_PARTICIPATING) {
            return;
        }

        // We also skip all non-module events as we current are only being conditional on activities.
        if ($completionenabled && $event->contextlevel == CONTEXT_MODULE) {

            // Check their school.
            $school = self::get_school_resolver()->get_by_member($userid);
            if (!$school || !$school->is_setup()) {
                // No school, no chocolate.
                return;
            }

            // Is that allowed to reward for completion?
            if ($school->is_reward_method_completion_permitted()) {
                $rewardforcompletion = true;

                // When the reward method is completion, then event, check if completion is enabled in module.
                if ($school->is_reward_method_completion_else_event()) {
                    $courseinfo = course_modinfo::instance($event->courseid);
                    $cminfo = $courseinfo->get_cm($event->get_context()->instanceid);
                    $completioninfo = new completion_info($courseinfo->get_course());
                    $rewardforcompletion = $completioninfo->is_enabled($cminfo);
                }

                // Reward for completion and leave.
                if ($rewardforcompletion) {
                    static::reward_for_completion($event);
                    return;
                }
            }
        }

        static::reward_for_event($event);
    }

    /**
     * Handle course completed event.
     *
     * @param \core\event\course_completed $event The event.
     * @return void
     */
    protected static function handle_course_completed_event(\core\event\course_completed $event) {
        $userid = static::get_event_target_user($event);

        // Check their school.
        $school = self::get_school_resolver()->get_by_member($userid);
        if (!$school || !$school->is_setup()) {
            // No school, no chocolate.
            return;
        }

        if (!$school->is_course_completion_reward_enabled()) {
            // Sorry mate, no pocket money for you.
            return;
        }

        if ($school->was_user_rewarded_for_completion($userid, $event->courseid, 0)) {
            // The course completion state must have been reset. If we do not ignore this
            // then we will have issue when logging the event due to unique indexes.
            return;
        }

        // Ok, here you can have some coins.
        $school->capture_event($userid, $event, (int) $school->get_course_completion_reward());
        $school->log_user_was_rewarded_for_completion($userid, $event->courseid, 0, COMPLETION_COMPLETE);
    }

    /**
     * Reward a user for completion.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    protected static function reward_for_completion(\core\event\base $event) {
        // We only care about one event at this point.
        if ($event instanceof \core\event\course_module_completion_updated) {
            $data = $event->get_record_snapshot('course_modules_completion', $event->objectid);
            if ($data->completionstate == COMPLETION_COMPLETE
                    || $data->completionstate == COMPLETION_COMPLETE_PASS) {

                $userid = static::get_event_target_user($event);
                $courseid = $event->courseid;
                $cmid = $event->get_context()->instanceid;

                $school = self::get_school_resolver()->get_by_member($userid);
                if ($school->was_user_rewarded_for_completion($userid, $courseid, $cmid)) {
                    return;
                }

                $modinfo = course_modinfo::instance($courseid);
                $cminfo = $modinfo->get_cm($cmid);
                $calculator = $school->get_completion_points_calculator_by_mod();
                $coins = (int) $calculator->get_for_module($cminfo->modname);

                $school->capture_event($userid, $event, $coins);
                $school->log_user_was_rewarded_for_completion($userid, $courseid, $cmid, $data->completionstate);
            }
        }
    }

    /**
     * Reward a user by event.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    protected static function reward_for_event(\core\event\base $event) {
        $coins = 0;
        $userid = static::get_event_target_user($event);

        static $ignored = [
            '\\core\\event\\competency_user_competency_review_request_cancelled' => true,
            '\\core\\event\\courses_searched' => true,
            '\\core\\event\\course_viewed' => true,
            '\\mod_glossary\\event\\entry_disapproved' => true,
            '\\mod_lesson\\event\\lesson_restarted' => true,
            '\\mod_lesson\\event\\lesson_resumed' => true,
            '\\mod_quiz\\event\\attempt_abandoned' => true,
            '\\mod_quiz\\event\\attempt_becameoverdue' => true,

            // Redudant events.
            '\\mod_book\\event\\course_module_viewed' => true,
            '\\mod_forum\\event\\discussion_subscription_created' => true,
            '\\mod_forum\\event\\subscription_created' => true,
        ];

        if ($event->crud === 'd') {
            $coins = 0;

        } else if (array_key_exists($event->eventname, $ignored)) {
            $coins = 0;

        } else if (strpos($event->eventname, 'assessable_submitted') !== false
                || strpos($event->eventname, 'assessable_uploaded') !== false) {
            // Loose redundancy check.
            $coins = 0;

        } else if ($event->crud === 'c') {
            $coins = 3;

        } else if ($event->crud === 'r') {
            $coins = 1;

        } else if ($event->crud === 'u') {
            $coins = 1;
        }

        if ($coins > 0) {
            static::add_coins_for_event($event->userid, $coins, $event);
        }
    }

    /**
     * Add coins for an event.
     *
     * @param int $userid The user ID.
     * @param int $coins The number of coins.
     * @param \core\event\base $event The event.
     */
    private static function add_coins_for_event($userid, $coins, \core\event\base $event) {
        $school = self::get_school_resolver()->get_by_member($userid);
        if (!$school) {
            // The user is not part of any school.
            return;
        }

        if (!$school->is_setup()) {
            // The school is not yet set-up.
            return;
        }

        if (!$school->is_reward_method_event_permitted()) {
            // The school does not allow event-based rewards.
            return;
        }

        if ($school->has_exceeded_threshold($userid, $event)) {
            // The user has exceeded the threshold, no coins for them!
            return;
        }

        $school->capture_event($userid, $event, $coins);
    }

    /**
     * Convert the language config to the Accept-Language header format.
     *
     * This handles special scenario where Moodle's inheritance from different
     * langauge packs is not expected nor desired for the purpose of the
     * Accept-Language header.
     *
     * It also attempts to best guess which fallback languages should be used.
     *
     * @param stdClass $langconfig The language config from {@link self::get_current_language_config}
     * @return string|null
     */
    public static function convert_language_config_to_accept_language_header(stdClass $langconfig) {
        $lang = $langconfig->lang;
        $cldr = !empty($langconfig->localecldr) ? $langconfig->localecldr : null;
        $iso = !empty($langconfig->iso6391) ? $langconfig->iso6391 : null;

        // There are multiple English packs but at present we only consider en_us* as non Australian English.
        $isen = strpos($lang, 'en') === 0;
        $isenau = $isen && strpos($lang, 'en_us') === false;

        // Firstly, we will try to convert the Moodle language code into a sensible value
        // because it appears that we cannot rely on the logic below to yield the right
        // language as those have not been defined properly. Although we only do this when
        // the language matches something like es_mx, or en_us because we'll add the first
        // part of the code at the end of this function. Also, we skip when the 2nd part
        // does not appear to be valid language code.
        $parts = explode('_', $lang);
        if (count($parts) > 1) {
            if (strlen($parts[1]) <= 3 && !in_array($parts[1], ['wp', 'k12', 'uni'])) {
                $candidates[] = implode('-', [$parts[0], strtoupper($parts[1])]);
            }
        }

        // So, Moodle now defines the localecldr in language packs, however most packs
        // do not yet include the string and all inherit from the default language pack
        // by default (defined as en-AU). In the case where we are falling back and the
        // language is not the default English language, we attempt to convert the locale.
        // This is important for any non en-AU language, including en-US.
        if ($cldr && ($cldr === 'en-AU' && !$isenau)) {
            if ($langconfig->locale) {
                // Attempt to convert locale (en_AU.UTF-8, en_IE@euro) to en-AU, en-IE.
                // We need this first because Moodle's en_US has their localecldr set to en_AU.
                $cldr = str_replace('_', '-', preg_replace('/[^a-zA-Z0-9_].*$/', '', $langconfig->locale));
            }
        }

        // If we've found a CLDR, we add it. And when the CLDR contains a hyphen, we
        // extract the first part, which is typically the language code. We do this because
        // unfortunately, the iso6391 value is not always up to date in packs which can lead
        // to the following: he => he-IL,en. In that case, the server could fallback on English
        // instead of Hebrew if it did not define the he-IL pack. That problem is caused by
        // as in the latter example thei iso6391 is either incorrect, or wrongly inherited.
        // Note that in our opinion, the server must not try to fallback on languages that
        // were not defined in the Accept-Language header.
        if ($cldr) {
            $candidates[] = $cldr;
            if (strpos($cldr, '-')) {
                $parts = explode('-', $cldr);
                $candidates[] = $parts[0];
            }
        }

        // This is our final fallback, we hope that the 2-letter language code will be enough
        // for our server to identify the language to use. However, again the language packs
        // are showing major inconsistencies... So we only include this when it's not 'en'
        // or we are in the English pack.
        if ($iso && ($iso !== 'en' || $isen)) {
            $candidates[] = $langconfig->iso6391;
        }

        // Finally, we will include the code we got from the language pack itself.
        // It may not be ideal, but that's the best fallback we can come up with.
        // This also helps for a language like Norwegian which we have as 'no',
        // but the iso code from Moodle is 'nb'.
        $parts = explode('_', $lang);
        $candidates[] = $parts[0];

        return !empty($candidates) ? implode(',', array_unique($candidates)) : null;
    }

    /**
     * Generate a user SSO secret.
     *
     * These are short lived and should be validated using {@link self::validate_user_sso_secret}.
     *
     * @param int $userid The user ID.
     * @return string The secret.
     */
    public static function generate_user_sso_secret($userid) {
        return create_user_key('local_mootivated_sso', $userid, 0, null, time() + 60);
    }

    /**
     * Get the user SSO secret.
     *
     * This is based on {@link validate_user_key} which isn't available on older versions
     * and uses print_error rather than returning a boolean.
     *
     * @param string $secret The secret.
     * @param bool $discard Discard the secret when found and valid.
     * @return stdClass|false
     */
    public static function get_user_from_sso_secret($secret, $discard = false) {
        global $DB;

        // Find the key. Note that we do not use instance.
        $params = ['script' => 'local_mootivated_sso', 'value' => $secret, 'instance' => 0];
        if (!$key = $DB->get_record('user_private_key', $params)) {
            return false;
        }

        // We MUST have a validuntil date.
        if (empty($key->validuntil) || $key->validuntil < time()) {
            return false;
        }

        // We might apply IP restrictions, but it's not expected at this stage.
        if ($key->iprestriction) {
            $remoteaddr = getremoteaddr(null);
            if (empty($remoteaddr) or !address_in_subnet($remoteaddr, $key->iprestriction)) {
                return false;
            }
        }

        // Discard the token now that we've identified the user.
        if ($discard) {
            $DB->delete_records('user_private_key', ['id' => $key->id]);
        }

        return \core_user::get_user($key->userid, '*', MUST_EXIST);
    }

    /**
     * Get the client to communicate with the server.
     *
     * @return client
     */
    public static function get_client() {
        return new client(get_config('local_mootivated', 'server_ip'));
    }

    /**
     * Get the client to communicate with the server as a user.
     *
     * Typically this is retrieved when we know what school the user is in.
     *
     * @param object $logininfo The login info from Motrain.
     * @param object $langconfig Moodle language config containing (iso6391, locale, localecldr).
     * @return client
     */
    public static function get_client_user(stdClass $logininfo, $langconfig = null) {
        $lang = $langconfig ? self::convert_language_config_to_accept_language_header($langconfig) : null;
        return new client_user(get_config('local_mootivated', 'server_ip'), $logininfo, $lang);
    }

    /**
     * Get the current language config.
     *
     * Note that due to the language inheritance, it is possible that some of these values
     * are not correct for a given language because they might inherit from tne core English
     * language page.
     *
     * @return Returns an object containing lang, and possibly non-null iso6391, locale, localecldr.
     *         The lang key is Moodle's language code, the rest comes from langconfig.
     */
    public static function get_current_language_config() {
        $strman = get_string_manager();
        $keys = ['iso6391', 'localecldr', 'locale'];
        return array_reduce($keys, function($carry, $key) use ($strman) {
            $carry->{$key} = $strman->string_exists($key, 'langconfig') ? get_string($key, 'langconfig') : null;
            return $carry;
        }, (object) ['lang' => current_language()]);
    }

    /**
     * Get the module name from its context.
     *
     * @param context_module $context The context.
     * @return string
     */
    public static function get_module_name_from_context(context_module $context) {
        $courseid = $context->get_course_context()->instanceid;
        $modinfo = course_modinfo::instance($courseid);
        $cminfo = $modinfo->get_cm($context->instanceid);
        return $cminfo->modname;
    }

    /**
     * Get the target of an event.
     *
     * @param \core\base\event $event The event.
     * @return int The user ID.
     */
    protected static function get_event_target_user(\core\event\base $event) {
        $userid = $event->userid;
        if ($event instanceof \core\event\course_completed || $event instanceof \core\event\course_module_completion_updated) {
            $userid = $event->relateduserid;
        }
        return $userid;
    }

    /**
     * Find the global school.
     *
     * It is always the first school we find, in case the site switched from and to
     * using sections. Also, this ensures that the global school is kept even after
     * using sections has been turned on.
     *
     * @return stdClass|null
     */
    public static function get_global_school() {
        global $DB;
        $candidates = $DB->get_records('local_mootivated_school', [], 'id ASC', 'id');
        if (!empty($candidates)) {
            $candidate = reset($candidates);
            return new \local_mootivated\global_school($candidate->id);
        }
        return new \local_mootivated\global_school(0);
    }

    /**
     * Get points decimal places.
     *
     * @return int
     */
    public static function get_points_decimal_places() {
        $decimalplaces = get_config('local_mootivated', 'pointsdecimalplaces');
        return $decimalplaces ? intval($decimalplaces) : 0;
    }

    /**
     * Get the points image.
     *
     * @return client
     */
    public static function get_points_image_url() {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_mootivated');
        $pointsimageurl = get_config('local_mootivated', 'pointsimageurl');
        return $pointsimageurl ? $pointsimageurl : $renderer->pix_url('coins', 'local_mootivated')->out(false);
    }

    /**
     * Get the points term.
     *
     * @return client
     */
    public static function get_points_term() {
        $pointsterm = get_config('local_mootivated', 'pointsterm');
        return $pointsterm ? $pointsterm : get_string('coins', 'local_mootivated');
    }

    /**
     * Find a school by private key.
     *
     * @param string $key The private key.
     * @return school|null
     */
    public static function get_school_by_private_key($key) {
        return \local_mootivated\school::load_from_private_key($key);
    }

    /**
     * Get the school resolver.
     *
     * @return ischool_resolver
     */
    public static function get_school_resolver() {
        if (!self::$schoolrevolver) {
            if (!self::uses_sections()) {
                $resolver = new global_school_resolver();
            } else {
                $resolver = new school_resolver();
            }
            self::$schoolrevolver = $resolver;
        }
        return self::$schoolrevolver;
    }

    /**
     * Set the school resolver.
     *
     * @param ischool_resolver $resolver The resolver.
     */
    public static function set_school_resolver(ischool_resolver $resolver) {
        self::$schoolrevolver = $resolver;
    }

    /**
     * Get the user pusher.
     *
     * @return user_pusher
     */
    public static function get_user_pusher() {
        if (!self::$userpusher) {
            $resolver = new user_pusher(self::get_school_resolver(), self::get_client(), self::get_mootivated_role());
            self::$userpusher = $resolver;
        }
        return self::$userpusher;
    }

    /**
     * Set whether the section vs. section leaderboard is enabled.
     *
     * @param bool $value The value.
     */
    public static function set_section_vs_section_leaderboard_enabled($value) {
        set_config('svsleaderboardenabled', !empty($value), 'local_mootivated');
    }

    /**
     * Whether we're using sections.
     *
     * @return bool
     */
    public static function uses_sections() {
        $usesections = get_config('local_mootivated', 'usesections');
        return !empty($usesections);
    }

}
