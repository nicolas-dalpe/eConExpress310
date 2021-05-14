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
 * List add-ons.
 *
 * @package   local_mootivated
 * @copyright 2019 Mootivation Technologies Corp.
 * @author    Mootivation Technologies Corp.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . ' /../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('managemootivatedaddons');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mootivatedaddons', 'local_mootivated'));

$addons = core_plugin_manager::instance()->get_plugins_of_type('mootivatedaddon');
if (empty($addons)) {
    echo $OUTPUT->notification(get_string('noaddoninstalled', 'local_mootivated'), 'nofityinfo');
    echo $OUTPUT->footer();
    die();
}

$table = new flexible_table('mootivatedaddons_administration_table');
$table->define_columns(['name', 'enabled', 'settings']);
$table->define_headers([
    get_string('subplugintype_mootivatedaddon', 'local_mootivated'),
    get_string('subpluginstate', 'local_mootivated'),
    '',
]);
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'localplugins');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

$plugins = [];
foreach ($addons as $plugin) {
    $plugins[$plugin->displayname] = $plugin;
}
core_collator::ksort($plugins);

foreach ($plugins as $name => $plugin) {

    $settingslink = '';
    $settingsurl = $plugin->get_settings_url();
    if (!empty($settingsurl)) {
        $settingslink = html_writer::link($settingsurl, get_string('setup', 'local_mootivated'));
    }

    $enabledstr = $plugin->is_enabled() ? get_string('enabled', 'local_mootivated') : get_string('disabled', 'local_mootivated');
    $summary = html_writer::div($name) . html_writer::div( html_writer::tag('small',
        get_string('plugindescription', $plugin->component)), 'mt-1 text-muted'
    );

    $table->add_data([$summary, $enabledstr, $settingslink]);
}

$table->print_html();

echo $OUTPUT->footer();
