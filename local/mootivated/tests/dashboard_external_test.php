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
 * Dashboard external tests.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/mootivated/tests/fixtures/events.php');

use local_mootivated\dashboard_external;

/**
 * Dashboard external tests class.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mootivated_dashboard_external_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test get module types.
     */
    public function test_get_module_types() {
        global $DB;

        $this->setAdminUser();

        $moduletypes = dashboard_external::get_module_types();
        $mods = get_module_types_names();
        $diskmodules = core_component::get_plugin_list('mod');

        $this->assertEquals(count($mods), count($moduletypes));
        $this->assertTrue(count($moduletypes) >= 20);   // The expected minumum number of modules.

        // Check that each module is found in the list.
        foreach ($mods as $mod => $modname) {
            $this->assertTrue(in_array(['module' => $mod, 'name' => $modname], $moduletypes));
        }

    }

    /**
     * Test get courses.
     */
    public function test_get_courses() {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course();
        $c2 = $dg->create_course(['shortname' => 'awesome']);
        $c3 = $dg->create_course(['enablecompletion' => true]);

        $this->setAdminUser();
        $courses = dashboard_external::get_courses();

        // It includes the frontpage too.
        $this->assertCount(4, $courses);

        // Find the awesome course.
        $this->assertCount(1, array_filter($courses, function($course) {
            return $course['shortname'] == 'awesome';
        }));

        // Find the course with completion enabled.
        $this->assertCount(1, array_filter($courses, function($course) {
            return $course['enablecompletion'];
        }));
    }

    /**
     * Test get activities.
     */
    public function test_get_activities() {
        $dg = $this->getDataGenerator();

        $c1 = $dg->create_course();
        $c2 = $dg->create_course(['enablecompletion' => true]);
        $c3 = $dg->create_course(['enablecompletion' => true]);

        $dg->create_module('page', ['course' => $c1]);
        $dg->create_module('url', ['course' => $c1]);
        $dg->create_module('assign', ['course' => $c1]);

        $dg->create_module('forum', ['course' => $c2, 'completion' => COMPLETION_TRACKING_NONE]);
        $dg->create_module('chat', ['course' => $c2, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $dg->create_module('quiz', ['course' => $c2, 'completion' => COMPLETION_TRACKING_AUTOMATIC]);

        $cm3a = $dg->create_module('choice', ['course' => $c3, 'completion' => COMPLETION_TRACKING_NONE]);
        $cm3b = $dg->create_module('glossary', ['course' => $c3, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $cm3c = $dg->create_module('page', ['course' => $c3, 'completion' => COMPLETION_TRACKING_AUTOMATIC]);

        $this->setAdminUser();

        // Test course 1.
        $activities = dashboard_external::get_activities($c1->id);
        $this->assertCount(3, $activities);
        $this->assertEquals(false, $activities[0]['completionenabled']);
        $this->assertEquals('page', $activities[0]['module']);
        $this->assertEquals(false, $activities[1]['completionenabled']);
        $this->assertEquals('url', $activities[1]['module']);
        $this->assertEquals(false, $activities[2]['completionenabled']);
        $this->assertEquals('assign', $activities[2]['module']);

        // Test course 2.
        $activities = dashboard_external::get_activities($c2->id);
        $this->assertCount(3, $activities);
        $this->assertEquals(false, $activities[0]['completionenabled']);
        $this->assertEquals('forum', $activities[0]['module']);
        $this->assertEquals(true, $activities[1]['completionenabled']);
        $this->assertEquals('chat', $activities[1]['module']);
        $this->assertEquals(true, $activities[2]['completionenabled']);
        $this->assertEquals('quiz', $activities[2]['module']);

        // Test course 3.
        $activities = dashboard_external::get_activities($c3->id);
        $this->assertCount(3, $activities);
        $this->assertEquals(false, $activities[0]['completionenabled']);
        $this->assertEquals($cm3a->cmid, $activities[0]['cmid']);
        $this->assertEquals(context_module::instance($cm3a->cmid)->id, $activities[0]['contextid']);
        $this->assertEquals($cm3a->name, $activities[0]['name']);
        $this->assertEquals('choice', $activities[0]['module']);
        $this->assertEquals(true, $activities[1]['completionenabled']);
        $this->assertEquals($cm3b->cmid, $activities[1]['cmid']);
        $this->assertEquals(context_module::instance($cm3b->cmid)->id, $activities[1]['contextid']);
        $this->assertEquals($cm3b->name, $activities[1]['name']);
        $this->assertEquals('glossary', $activities[1]['module']);
        $this->assertEquals(true, $activities[2]['completionenabled']);
        $this->assertEquals($cm3c->cmid, $activities[2]['cmid']);
        $this->assertEquals(context_module::instance($cm3c->cmid)->id, $activities[2]['contextid']);
        $this->assertEquals($cm3c->name, $activities[2]['name']);
        $this->assertEquals('page', $activities[2]['module']);

    }
}

