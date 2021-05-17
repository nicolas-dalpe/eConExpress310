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
 * Web service local plugin template external functions and service definitions.
 *
 * @package   local_mootivated
 * @copyright 2016 Mootivation Technologies Corp.
 * @author    Mootivation Technologies Corp.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = [
    'local_mootivated_award_coins' => [
        'classname'     => 'local_mootivated\\external',
        'methodname'    => 'award_coins',
        'description'   => 'Award coins to a user.',
        'type'          => 'write',
        'capabilities'  => 'local/mootivated:awardcoins',
    ],
    'local_mootivated_get_setup' => [
        'classname'     => 'local_mootivated\\external',
        'methodname'    => 'get_setup',
        'description'   => 'Get information about the setup.',
        'type'          => 'read',
        'capabilities'  => '',
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
            'local_mobile'
        ]
    ],
    'local_mootivated_login' => [
        'classname'     => 'local_mootivated\\external',
        'methodname'    => 'login',
        'description'   => 'Login to the remote server.',
        'type'          => 'write',
        'capabilities'  => 'local/mootivated:login',
        'services' => [ // We must be able to login from the Moodle Mobile app.
            MOODLE_OFFICIAL_MOBILE_SERVICE,
            'local_mobile'
        ]
    ],
    'local_mootivated_upload_avatar' => [
        'classname'     => 'local_mootivated\\external',
        'methodname'    => 'upload_avatar',
        'description'   => 'Upload an avatar.',
        'type'          => 'write',
        'capabilities'  => 'moodle/user:editownprofile',
    ],

    // Dashboard functions.
    'local_mootivated_get_activities' => [
        'classname'     => 'local_mootivated\\dashboard_external',
        'methodname'    => 'get_activities',
        'description'   => 'List the activities in the course',
        'type'          => 'read',
        'capabilities'  => 'moodle/course:update, moodle/course:viewhiddencourses'
    ],
    'local_mootivated_get_courses' => [
        'classname'     => 'local_mootivated\\dashboard_external',
        'methodname'    => 'get_courses',
        'description'   => 'List the courses available on the site',
        'type'          => 'read',
        'capabilities'  => 'moodle/course:view, moodle/course:update, moodle/course:viewhiddencourses'
    ],
    'local_mootivated_get_module_types' => [
        'classname'     => 'local_mootivated\\dashboard_external',
        'methodname'    => 'get_module_types',
        'description'   => 'List the module types available on the site',
        'type'          => 'read',
        'capabilities'  => ''
    ],
    'local_mootivated_identify_user_from_sso_secret' => [
        'classname'     => 'local_mootivated\\dashboard_external',
        'methodname'    => 'identify_user_from_sso_secret',
        'description'   => 'Identifies a user during SSO with the dashboard',
        'type'          => 'read',
        'capabilities'  => ''
    ],
    'local_mootivated_send_notification' => [
        'classname'     => 'local_mootivated\\dashboard_external',
        'methodname'    => 'send_notification',
        'description'   => 'Send a notification to one or more users',
        'type'          => 'write',
        'capabilities'  => ''
    ],
    'local_mootivated_update_global' => [
        'classname'     => 'local_mootivated\\dashboard_external',
        'methodname'    => 'update_global',
        'description'   => 'Update the global parameters',
        'type'          => 'write',
        'capabilities'  => 'moodle/site:config'
    ],
    'local_mootivated_update_school' => [
        'classname'     => 'local_mootivated\\dashboard_external',
        'methodname'    => 'update_school',
        'description'   => 'Update the parameters of a school',
        'type'          => 'write',
        'capabilities'  => 'moodle/site:config'
    ]
];

// We define the services to install as pre-build services.
// A pre-build service is not editable by administrator.
// This limits the webservices accessible to the user we generate a token for.
$services = [
    get_string('mootivated_web_services', 'local_mootivated') => array(
        'functions' => [
            'local_mootivated_login',
            'local_mootivated_upload_avatar'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_mootivated',
    ),

    get_string('dashboardservice', 'local_mootivated') => [
        'functions' => [
            'core_webservice_get_site_info',
            'local_mootivated_get_activities',
            'local_mootivated_get_courses',
            'local_mootivated_get_module_types',
            'local_mootivated_identify_user_from_sso_secret',
            'local_mootivated_send_notification',
            'local_mootivated_update_global',
            'local_mootivated_update_school',
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'local_mootivated_dashboard_access',
    ],
];
