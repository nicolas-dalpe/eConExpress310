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
 * Helper tests.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/mootivated/tests/fixtures/events.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_mootivated\helper;

/**
 * Helper tests class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mootivated_observer_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test user created observer assigns role when sections aren't used.
     */
    public function test_user_created_assigns_role_when_sections_are_not_used() {
        $dg = $this->getDataGenerator();
        $u = user_create_user(['username' => 'u']);
        $role = helper::get_mootivated_role();
        $this->assertFalse(helper::uses_sections());
        $this->assertTrue(user_has_role_assignment($u, $role->id));
    }

    /**
     * Test user created observer does not assign role when sections are used.
     */
    public function test_user_created_assigns_role_when_sections_used() {
        set_config('usesections', 1, 'local_mootivated');
        $dg = $this->getDataGenerator();
        $u = user_create_user(['username' => 'u']);
        $role = helper::get_mootivated_role();
        $this->assertTrue(helper::uses_sections());
        $this->assertFalse(user_has_role_assignment($u, $role->id));
    }

    /**
     * Test user created observer when automatic assignation is not permitted.
     */
    public function test_user_created_assigns_role_when_not_permitted() {
        set_config('disableautoroleassign', 1, 'local_mootivated');
        $dg = $this->getDataGenerator();
        $u = user_create_user(['username' => 'u']);
        $role = helper::get_mootivated_role();
        $this->assertFalse(helper::allow_automatic_role_assignment());
        $this->assertFalse(helper::uses_sections());
        $this->assertFalse(user_has_role_assignment($u, $role->id));
    }

    /**
     * Test cohort member added observer when sections are used.
     */
    public function test_cohort_member_added_assigns_role_when_sections_used() {
        set_config('usesections', 1, 'local_mootivated');
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');
        $role = helper::get_mootivated_role();

        $u1 = $dg->create_user()->id;
        $u2 = $dg->create_user()->id;
        $c1 = $dg->create_cohort();
        $c2 = $dg->create_cohort();
        $s = $pg->create_school(['cohortid' => $c1->id]);

        cohort_add_member($c1->id, $u1);
        cohort_add_member($c2->id, $u2);

        $this->assertTrue(helper::uses_sections());
        $this->assertTrue(user_has_role_assignment($u1, $role->id));
        $this->assertFalse(user_has_role_assignment($u2, $role->id));
    }

    /**
     * Test cohort member added observer when sections are not used.
     */
    public function test_cohort_member_added_assigns_role_when_sections_are_not_used() {
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');
        $role = helper::get_mootivated_role();

        // Cannot use user_create_user here or it assigns the role lol.
        $u1 = $dg->create_user()->id;
        $c1 = $dg->create_cohort();
        $s = $pg->create_school(['cohortid' => $c1->id]);

        cohort_add_member($c1->id, $u1);

        $this->assertFalse(helper::uses_sections());
        $this->assertFalse(user_has_role_assignment($u1, $role->id));
    }

    /**
     * Test cohort member added observer when not permitted.
     */
    public function test_cohort_member_added_assigns_role_when_not_permitted() {
        set_config('usesections', 1, 'local_mootivated');
        set_config('disableautoroleassign', 1, 'local_mootivated');
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');
        $role = helper::get_mootivated_role();

        $u1 = $dg->create_user()->id;
        $c1 = $dg->create_cohort();
        $s = $pg->create_school(['cohortid' => $c1->id]);

        cohort_add_member($c1->id, $u1);

        $dg = $this->getDataGenerator();
        $this->assertTrue(helper::uses_sections());
        $this->assertFalse(user_has_role_assignment($u1, $role->id));
    }

    /**
     * Removing a cohort member unassigns the role when sections are used.
     */
    public function test_cohort_member_removed_unassigns_role_when_sections_used() {
        set_config('usesections', 1, 'local_mootivated');
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');
        $role = helper::get_mootivated_role();

        $u1 = $dg->create_user()->id;
        $c1 = $dg->create_cohort();
        $s = $pg->create_school(['cohortid' => $c1->id]);
        cohort_add_member($c1->id, $u1);
        $this->assertTrue(helper::uses_sections());
        $this->assertTrue(user_has_role_assignment($u1, $role->id));
        cohort_remove_member($c1->id, $u1);
        $this->assertFalse(user_has_role_assignment($u1, $role->id));
    }

    /**
     * Removing a cohort member does not unassign the role when sections are not used.
     */
    public function test_cohort_member_removed_unassigns_role_when_sections_are_not_used() {
        set_config('usesections', 0, 'local_mootivated');
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');
        $role = helper::get_mootivated_role();

        $u1 = user_create_user(['username' => 'u1']);   // Use this give the user the role.
        $c1 = $dg->create_cohort();
        $s = $pg->create_school(['cohortid' => $c1->id]);
        cohort_add_member($c1->id, $u1);
        $this->assertFalse(helper::uses_sections());
        $this->assertTrue(user_has_role_assignment($u1, $role->id));
        cohort_remove_member($c1->id, $u1);
        $this->assertTrue(user_has_role_assignment($u1, $role->id));
    }

    /**
     * Removing a cohort member does not unassign the role when not permitted.
     */
    public function test_cohort_member_removed_unassigns_role_when_not_permitted() {
        set_config('usesections', 1, 'local_mootivated');
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');
        $role = helper::get_mootivated_role();

        $u1 = $dg->create_user()->id;
        $c1 = $dg->create_cohort();
        $s = $pg->create_school(['cohortid' => $c1->id]);
        cohort_add_member($c1->id, $u1);

        set_config('disableautoroleassign', 1, 'local_mootivated');
        $this->assertFalse(helper::allow_automatic_role_assignment());
        $this->assertTrue(helper::uses_sections());
        $this->assertTrue(user_has_role_assignment($u1, $role->id));
        cohort_remove_member($c1->id, $u1);
        $this->assertTrue(user_has_role_assignment($u1, $role->id));
    }
}
