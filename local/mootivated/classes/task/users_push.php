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
 * Users push.
 *
 * @package    local_mootivated
 * @copyright  2019 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated\task;
defined('MOODLE_INTERNAL') || die();

use local_mootivated\helper;
use local_mootivated\notifier;

/**
 * Users push class.
 *
 * @package    local_mootivated
 * @copyright  2019 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_push extends \core\task\scheduled_task {

    /**
     * Execute.
     *
     * @return void
     */
    public function execute() {
        if (!helper::mootivated_role_exists()) {
            mtrace('Disabled, Moodtivated User does not exist.');
            return;
        }

        $userpusher = helper::get_user_pusher();
        if (!$userpusher->has_queue()) {
            mtrace('Push users queue is empty.');
            return;
        }

        mtrace('Pushing chunk of users to Motrain/Mootivated dashboard.');
        $userpusher->push_chunk();
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskpushusers', 'local_mootivated');
    }
}
