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
 * Entry point to go to the dasbhoard SSO-style.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_mootivated\helper;

require_once(__DIR__ . '/../../config.php');
require_login(0, false);

if (!helper::can_sso_to_dashboard()) {
    throw new moodle_exception('cannotssotodasbhoardpermission', 'local_mootivated');

} else if (!helper::is_sso_to_dashboard_enabled()) {
    throw new moodle_exception('ssotodashboarddisabled', 'local_mootivated');
}

// Generate the sso secret.
$secret = helper::generate_user_sso_secret($USER->id);

// Redirect the user.
$client = helper::get_client();
$client->redirect('/sso', ['secret' => $secret, 'provider' => 'moodle', 'host' => $CFG->wwwroot]);

# Moodle generates usersecret for user
# Moodle to send userid + useremail + usersecret to server

# Server validates useremail exists
# Server validates userid + usersecret by remote call
# Server responds with SSO key

# Client sends SSO key
# Server validates user SSO key
# Server authenticates the client.
