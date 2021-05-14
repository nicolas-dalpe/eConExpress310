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
 * Role syncer tests.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use local_mootivated\helper;
use local_mootivated\role_syncer;

/**
 * Role syncer tests class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mootivated_role_syncer_testcase extends advanced_testcase {

    /** @var stdClass The Mootivated User role. */
    protected $role;

    public function setUp() {
        $this->resetAfterTest();
        $this->role = helper::get_mootivated_role();

        // Disable the auto assign or it'll mess up with our expectations.
        set_config('disableautoroleassign', 1, 'local_mootivated');
    }

    /**
     * Assert that the user has the Mootivated User role.
     *
     * @param stdClass $user The user.
     */
    protected function assert_has_role($user) {
        $this->assertTrue(user_has_role_assignment($user->id, $this->role->id, context_system::instance()->id));
    }

    /**
     * Assert that the user does not have the Mootivated User role.
     *
     * @param stdClass $user The user.
     */
    protected function assert_not_has_role($user) {
        $this->assertFalse(user_has_role_assignment($user->id, $this->role->id, context_system::instance()->id));
    }

    /**
     * Assign the Mootivated User role.
     *
     * @param stdClass $user The user.
     */
    protected function assign_role($user) {
        role_assign($this->role->id, $user->id, context_system::instance()->id);
    }

    /**
     * Sync all users.
     */
    public function test_sync_all_users() {
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();

        $this->assign_role($u2);
        $this->assert_has_role($u2);

        $syncer = new role_syncer();
        $this->assertEquals(2, $syncer->sync_all_users());
        $this->assert_has_role($u1);
        $this->assert_has_role($u2);
        $this->assert_has_role($u3);
    }

    /**
     * Sync sections users.
     */
    public function test_sync_sections_users() {
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();
        $u4 = $dg->create_user();

        $c1 = $dg->create_cohort();
        $c2 = $dg->create_cohort();

        $s1 = $pg->create_school(['cohortid' => $c1->id]);

        // User 1 is all set.
        cohort_add_member($c1->id, $u1->id);
        $this->assign_role($u1);
        $this->assert_has_role($u1);

        // User 2 is missing the role.
        cohort_add_member($c1->id, $u2->id);
        $this->assert_not_has_role($u2);

        // User 3 is in a cohort without school, and doesn't have the role. Nothing will happen.
        cohort_add_member($c2->id, $u3->id);
        $this->assert_not_has_role($u3);

        // User 3 is in a cohort without school, and has the role. The role will be taken away.
        cohort_add_member($c2->id, $u4->id);
        $this->assign_role($u4);
        $this->assert_has_role($u4);

        $syncer = new role_syncer();
        $this->assertEquals(['added' => 1, 'removed' => 1], $syncer->sync_sections_users());
        $this->assert_has_role($u1);
        $this->assert_has_role($u2);
        $this->assert_not_has_role($u3);
        $this->assert_not_has_role($u4);
    }

}
