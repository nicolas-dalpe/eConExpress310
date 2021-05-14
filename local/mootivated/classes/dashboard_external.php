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
 * External API for dashboard use.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use completion_info;
use context_course;
use context_module;
use context_system;
use course_modinfo;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use moodle_exception;
use core_user;
use local_mootivated\helper;

/**
 * External API for dashboard use.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_external extends external_api {

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function get_activities_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course ID')
        ]);
    }

    /**
     * Get all activities.
     *
     * @return array
     */
    public static function get_activities($courseid) {
        global $CFG;
        require_once($CFG->dirroot . '/course/externallib.php');
        require_once($CFG->libdir . '/completionlib.php');

        $params = self::validate_parameters(self::get_activities_parameters(), ['courseid' => $courseid]);
        $courseid = $params['courseid'];

        $context = context_course::instance($courseid);
        self::validate_context($context);

        $course = get_course($courseid);
        $modinfo = course_modinfo::instance($courseid);
        $completion = new completion_info($course);

        return array_reduce(\core_course_external::get_course_contents($courseid, [
            [
                'name' => 'excludecontents',
                'value' => true,
            ]
        ]), function($carry, $section) use ($completion, $modinfo) {
            return array_reduce($section['modules'], function($carry, $module) use ($completion, $modinfo) {
                $cmid = $module['id'];
                $cminfo = $modinfo->get_cm($cmid);
                $carry[] = [
                    'cmid' => $cmid,
                    'contextid' => context_module::instance($cmid)->id,
                    'name' => $module['name'],
                    'module' => $module['modname'],
                    'completionenabled' => (bool) $completion->is_enabled($cminfo),
                ];
                return $carry;
            }, $carry);
        }, []);
    }

    /**
     * External function return definition.
     *
     * @return external_single_structure
     */
    public static function get_activities_returns() {
        return new external_multiple_structure(new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'The course module ID'),
            'contextid' => new external_value(PARAM_INT, 'The module context ID'),
            'name' => new external_value(PARAM_TEXT, 'The name'),
            'module' => new external_value(PARAM_TEXT, 'The module type (assign, forum, ...)'),
            'completionenabled' => new external_value(PARAM_BOOL, 'Whether completion is enabled'),
        ]));
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function get_courses_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get all courses.
     *
     * The use calling this should have the permission to access all courses, and to update them
     * which allows them to list the information about all the courses.
     *
     * @return array
     */
    public static function get_courses() {
        global $CFG;
        require_once($CFG->dirroot . '/course/externallib.php');

        $params = self::validate_parameters(self::get_courses_parameters(), []);
        $context = context_system::instance();
        self::validate_context($context);

        return array_reduce(\core_course_external::get_courses([]), function($carry, $course) {
            $carry[] = [
                'id' => $course['id'],
                'contextid' => context_course::instance($course['id'])->id,
                'shortname' => $course['shortname'],
                'fullname' => $course['fullname'],
                'enablecompletion' => !empty($course['enablecompletion']),
            ];
            return $carry;
        }, []);
    }

    /**
     * External function return definition.
     *
     * @return external_single_structure
     */
    public static function get_courses_returns() {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'The course ID'),
            'contextid' => new external_value(PARAM_INT, 'The course context ID'),
            'shortname' => new external_value(PARAM_TEXT, 'The short name'),
            'fullname' => new external_value(PARAM_TEXT, 'The full name'),
            'enablecompletion' => new external_value(PARAM_BOOL, 'Whether completion is enabled'),
        ]));
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function get_module_types_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get module types.
     *
     * @return array
     */
    public static function get_module_types() {
        global $CFG;
        require_once($CFG->dirroot .'/course/lib.php');

        $params = self::validate_parameters(self::get_module_types_parameters(), []);
        $context = context_system::instance();
        self::validate_context($context);

        $data = [];

        $mods = get_module_types_names();
        foreach ($mods as $mod => $modname) {
            $data[] = [
                'module' => (string) $mod,
                'name' => (string) $modname
            ];
        }

        return $data;
    }

    /**
     * External function return definition.
     *
     * @return external_single_structure
     */
    public static function get_module_types_returns() {
        return new external_multiple_structure(new external_single_structure([
            'module' => new external_value(PARAM_ALPHANUMEXT, 'The module\'s internal name (assign, forum, ...)'),
            'name' => new external_value(PARAM_RAW, 'The module\'s human readable name (Assignment, Forum, ...)'),
        ]));
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function identify_user_from_sso_secret_parameters() {
        return new external_function_parameters([
            'secret' => new external_value(PARAM_ALPHANUM, 'secret'),
        ]);
    }

    /**
     * Identify a user from the SSO secret.
     *
     * @param string $secret The secret.
     * @return array
     */
    public static function identify_user_from_sso_secret($secret) {
        $params = self::validate_parameters(self::identify_user_from_sso_secret_parameters(), ['secret' => $secret]);
        $secret = $params['secret'];

        $context = context_system::instance();
        self::validate_context($context);

        // Is SSO enabled.
        if (!helper::is_sso_to_dashboard_enabled()) {
            throw new moodle_exception('ssotodashboarddisabled', 'local_mootivated');
        }

        // Can we identify a user from that secret.
        $user = helper::get_user_from_sso_secret($secret, true);
        if (!$user) {
            throw new moodle_exception('invalidssosecret', 'local_mootivated');
        }

        // Can the user SSO to the dashboard?
        if (!helper::can_sso_to_dashboard($user)) {
            throw new moodle_exception('cannotssotodasbhoardpermission', 'local_mootivated');
        }

        return [
            'email' => $user->email
        ];
    }

    /**
     * External function return definition.
     *
     * @return external_single_structure
     */
    public static function identify_user_from_sso_secret_returns() {
        return new external_single_structure([
            'email' => new external_value(PARAM_EMAIL, 'The user\'s email address')
        ]);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function send_notification_parameters() {
        return new external_function_parameters([
            'subject' => new external_value(PARAM_RAW, 'The notification subject'),
            'message' => new external_value(PARAM_RAW, 'The notification message'),
            'recipients' => new external_multiple_structure(
                // We leave room for delivering based on pluginid (hash), email, etc... if need be later on.
                new external_single_structure([
                    'usertype' => new external_value(PARAM_ALPHANUMEXT, 'The doctype: user or dashboard_user'),
                    'username' => new external_value(PARAM_USERNAME, 'The user\'s username')
                ])
            )
        ]);
    }

    /**
     * Identify a user from the SSO secret.
     *
     * @param string $subject The subject.
     * @param string $message The message.
     * @param array $recipients The recipients.
     */
    public static function send_notification($subject, $message, $recipients) {
        global $DB;

        $params = self::validate_parameters(self::send_notification_parameters(), [
            'subject' => $subject,
            'message' => $message,
            'recipients' => $recipients,
        ]);
        $subject = $params['subject'];
        $message = $params['message'];
        $recipients = $params['recipients'];

        $context = context_system::instance();
        self::validate_context($context);

        // Format content.
        $subject = format_string($subject, true, ['context' => $context]);
        $messageformatted = format_text($message, FORMAT_PLAIN, ['context' => $context]);

        // Prepare SQL.
        $parami = 0;
        $fragments = array_filter(array_map(function($recipient) use (&$parami) {
            if ($recipient['usertype'] !== 'user') {
                return null;
            }
            $paramname = 'userparam' . $parami++;
            return [
                "username = :{$paramname}",
                [$paramname => $recipient['username']]
            ];
        }, $recipients));

        // Reorganise the log.
        list($sqlfragments, $sqlparams) = array_reduce($fragments, function($carry, $item) {
            $carry[0][] = $item[0];
            $carry[1] = array_merge($carry[1], $item[1]);
            return $carry;
        }, [[], []]);

        // Query!
        $sql = implode(' OR ', $sqlfragments);
        $params = $sqlparams;
        $userids = $DB->get_fieldset_select('user', 'id', $sql, $params);

        // Send to all users.
        $delivered = 0;
        $failed = 0;
        foreach ($userids as $userid) {
            $notif = new \core\message\message();
            $notif->component = 'local_mootivated';
            $notif->name = 'notification';
            $notif->notification = 1;
            $notif->userfrom = core_user::get_noreply_user();
            $notif->userto = $userid;
            $notif->subject = $subject;
            $notif->fullmessage = $message;
            $notif->fullmessageformat = FORMAT_PLAIN;
            $notif->fullmessagehtml = $messageformatted;
            $notif->smallmessage = '';
            $result = message_send($notif);

            if ($result !== false) {
                $delivered++;
            } else {
                $failed++;
            }
        }

        return ['delivered' => $delivered, 'failed' => $failed];
    }

    /**
     * External function return definition.
     *
     * @return external_single_structure
     */
    public static function send_notification_returns() {
        return new external_single_structure([
            'delivered' => new external_value(PARAM_INT, 'The number of messages delivered'),
            'failed' => new external_value(PARAM_INT, 'The number of messages delivered')
        ]);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function update_global_parameters() {
        return new external_function_parameters([
            'options' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_ALPHANUM, 'The option\'s key'),
                    'value' => new external_value(PARAM_RAW, 'The option\'s value'),
                ])
            )
        ]);
    }

    /**
     * Update global parameters.
     *
     * @param array $options The options.
     * @return array
     */
    public static function update_global($options) {
        $params = self::validate_parameters(self::update_global_parameters(), ['options' => $options]);
        $options = $params['options'];

        $context = context_system::instance();
        self::validate_context($context);

        // Updating anything requires full access, for now.
        require_capability('moodle/site:config', $context);

        // Validate the parameters.
        $finaloptions = [];
        foreach ($options as $option) {
            $name = $option['name'];
            $value = $option['value'];
            $finalvalue = null;

            if ($name == 'svsleaderboardenabled') {
                $finalvalue = clean_param($value, PARAM_BOOL);

            } else if ($name == 'pointsdecimalplaces') {
                $finalvalue = max(0, clean_param($value, PARAM_INT));

            } else if ($name == 'pointsimageurl') {
                $finalvalue = clean_param($value, PARAM_URL);
                $finalvalue = !$finalvalue ? null : $finalvalue;

            } else if ($name == 'pointsterm') {
                $finalvalue = clean_param($value, PARAM_NOTAGS);
                $finalvalue = $finalvalue === '' ? null : $finalvalue;

            } else {
                continue;
            }

            $finaloptions[$name] = $finalvalue;
        }

        // Update.
        foreach ($finaloptions as $name => $value) {
            if ($value === null) {
                unset_config($name, 'local_mootivated');
            } else {
                set_config($name, $value, 'local_mootivated');
            }
        }

        return [
            'success' => true
        ];
    }

    /**
     * External function return definition.
     *
     * @return external_single_structure
     */
    public static function update_global_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL)
        ]);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function update_school_parameters() {
        return new external_function_parameters([
            'private_key' => new external_value(PARAM_ALPHANUMEXT, 'The school\'s private key'),
            'options' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_ALPHANUM, 'The option\'s key'),
                    'value' => new external_value(PARAM_RAW, 'The option\'s value'),
                ])
            )
        ]);
    }

    /**
     * Update a school.
     *
     * @param string $privatekey The private key.
     * @param array $options The options.
     * @return array
     */
    public static function update_school($privatekey, $options) {
        $params = self::validate_parameters(self::update_school_parameters(),
            ['private_key' => $privatekey, 'options' => $options]);
        $privatekey = $params['private_key'];
        $options = $params['options'];

        $context = context_system::instance();
        self::validate_context($context);

        // Updating anything requires full access, for now.
        require_capability('moodle/site:config', $context);

        // Find the school.
        $school = helper::get_school_by_private_key($privatekey);
        if (!$school) {
            throw new moodle_exception('unknownschool', 'local_mootivated');
        }

        // Validate the parameters.
        $finaloptions = [];
        foreach ($options as $option) {
            // Warning! Check the form saving behaviour when adding values here to ensure consistency.
            if ($option['name'] == 'leaderboardenabled') {
                $finaloptions['leaderboardenabled'] = clean_param($option['value'], PARAM_BOOL);
            }
        }


        // Update.
        $school->set_from_record((object) $finaloptions);
        $school->save();

        return [
            'success' => true
        ];
    }

    /**
     * External function return definition.
     *
     * @return external_single_structure
     */
    public static function update_school_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL)
        ]);
    }
}
