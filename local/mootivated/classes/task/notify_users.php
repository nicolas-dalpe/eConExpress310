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
 * Notify users.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated\task;
defined('MOODLE_INTERNAL') || die();

use local_mootivated\helper;
use local_mootivated\notifier;

/**
 * Notify users class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_users extends \core\task\scheduled_task {

    /**
     * Execute.
     *
     * @return void
     */
    public function execute() {
        $enabled = get_config('local_mootivated', 'enablelocalnotifications');
        if (!$enabled) {
            mtrace('Local notifications are disabled.');
            return;
        }

        $notifier = new notifier(helper::get_client(), helper::get_school_resolver());
        $notifications = $notifier->fetch_notifications();
        if (empty($notifications)) {
            mtrace('No notifications to send.');
            return;
        }

        $total = count($notifications);
        mtrace('Found ' . $total . ' notifications to process');
        $result = $notifier->send_notifications($notifications);

        $successcount = count($result['sent']);
        $errorcount = count($result['errors']);
        mtrace("Notifications sent successfully: {$successcount} / {$total}");
        mtrace("Notifications with errors: {$errorcount} / {$total}");

        mtrace('Reporting results to server');
        $notifier->report_result($result);
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasknotifyusers', 'local_mootivated');
    }
}
