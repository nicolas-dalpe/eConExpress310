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
 * Settings.
 *
 * @package   local_mootivated
 * @copyright 2016 Mootivation Technologies Corp.
 * @author    Mootivation Technologies Corp.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mootivated\helper;
use local_mootivated\local\admin\setting_static;

defined('MOODLE_INTERNAL') || die;

// Ensure the configurations for this site are set.
if ($hassiteconfig) {

    // Create the new settings page.
    $settings = new \local_mootivated\global_settings_page();
    $settings->add(new \local_mootivated\status_admin_setting());
    $settings->add(new admin_setting_configtext('local_mootivated/server_ip',
        get_string('serverip', 'local_mootivated'), get_string('serverip_desc', 'local_mootivated'), '', PARAM_HOST));
    $settings->add(new admin_setting_configcheckbox('local_mootivated/usesections',
        get_string('usesections', 'local_mootivated'), get_string('usesections_desc', 'local_mootivated'), false));
    $settings->add(new local_mootivated\local\admin\setting_configcheckbox_static('local_mootivated/svsleaderboardenabled',
        get_string('svsleaderboard', 'local_mootivated'), get_string('svsleaderboard_desc', 'local_mootivated'), false));
    $settings->add(new setting_static('local_mootivated/pointsterm',
        get_string('pointsterm', 'local_mootivated'),
        get_string('pointsterm_help', 'local_mootivated'),
        html_writer::tag('p', helper::get_points_term())));
    $settings->add(new setting_static('local_mootivated/pointsimageurl',
        get_string('pointsimageurl', 'local_mootivated'),
        get_string('pointsimageurl_help', 'local_mootivated'),
        html_writer::tag('p', html_writer::empty_tag('img', [
            'src' => helper::get_points_image_url(),
            'style' => 'height: 30px'
        ]))));
    $settings->add(new admin_setting_configcheckbox('local_mootivated/adminscanearn',
        get_string('adminscanearn', 'local_mootivated'), get_string('adminscanearn_desc', 'local_mootivated'), false));
    $settings->add(new admin_setting_configcheckbox('local_mootivated/enablelocalnotifications',
        get_string('enablelocalnotifications', 'local_mootivated'),
        get_string('enablelocalnotifications_desc', 'local_mootivated'), false));
    $settings->add(new \local_mootivated\queue_users_push_admin_setting());

    $ADMIN->add('localplugins', $settings);

    // Add the add-ons settings.
    $settings = new admin_category('mootivatedaddons', new lang_string('mootivatedaddons', 'local_mootivated'));
    $ADMIN->add('localplugins', $settings);
    $settings->add('mootivatedaddons', new admin_externalpage('managemootivatedaddons', new lang_string('managemootivatedaddons',
        'local_mootivated'), new moodle_url('/local/mootivated/addons.php')));
    foreach (core_plugin_manager::instance()->get_plugins_of_type('mootivatedaddon') as $plugin) {
        $plugin->load_settings($ADMIN, 'root', $hassiteconfig);
    }

    // Create the hidden page holding the schools.
    $temp = new admin_externalpage('local_mootivated_school', get_string('mootivatedsettings', 'local_mootivated'),
        new moodle_url('/admin/settings.php', array('section' => 'local_mootivated')), 'moodle/site:config', true);
    $ADMIN->add('localplugins', $temp);
}
