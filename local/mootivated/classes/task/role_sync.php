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
 * Role sync.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated\task;
defined('MOODLE_INTERNAL') || die();

use local_mootivated\helper;
use local_mootivated\role_syncer;

/**
 * Role sync class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_sync extends \core\task\scheduled_task {

    /**
     * Execute.
     *
     * @return void
     */
    public function execute() {
        if (!helper::mootivated_role_exists()) {
            mtrace('Mootivated User role does not exist.');
            return;
        }

        $syncer = new role_syncer();
        if (!helper::uses_sections()) {
            $count = $syncer->sync_all_users();
            mtrace(sprintf('Role given to %d user(s).', $count));
        } else {
            $result = $syncer->sync_sections_users();
            mtrace(sprintf('Role given to %d user(s).', $result['added']));
            mtrace(sprintf('Role removed from %d user(s).', $result['removed']));
        }

        set_config('lastrolesync', time(), 'local_mootivated');
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskrolesync', 'local_mootivated');
    }
}
