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
 * Queue all users for push.
 *
 * @package    local_mootivated
 * @copyright  2019 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated\task;
defined('MOODLE_INTERNAL') || die();

use local_mootivated\helper;

/**
 * Adhoc role sync class.
 *
 * @package    local_mootivated
 * @copyright  2019 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhoc_queue_users_for_push extends \core\task\adhoc_task {

    /**
     * Execute.
     *
     * Queues all relevant users to being pushed.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        if (!helper::mootivated_role_exists()) {
            mtrace('Mootivated User role does not exist.');
            return;
        }

        set_config('adhocqueueuserspush', 1, 'local_mootivated');

        $pusher = helper::get_user_pusher();
        if (helper::uses_sections()) {
            $cohortids = $DB->get_fieldset_select('local_mootivated_school', 'cohortid', '1=1');
            foreach ($cohortids as $cohortid) {
                mtrace(sprintf('Queuing users from cohort %d.', $cohortid));
                $pusher->queue_cohort($cohortid);
            }
        } else {
            mtrace('Queuing all users.');
            $pusher->queue_everyone();
        }

        set_config('adhocqueueuserspush', 0, 'local_mootivated');
        set_config('lastqueueuserspush', time(), 'local_mootivated');
    }

}
