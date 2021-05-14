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
 * Privacy provider tests.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_mootivated\privacy\provider;

global $CFG;

/**
 * Privacy provider testcase.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mootivated_privacy_provider_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_get_metadata() {
        $collection = provider::get_metadata(new collection('local_mootivated'));
        $this->assertCount(3, $collection->get_collection());
    }

    public function test_get_contexts_for_userid() {
        extract($this->set_test_data());

        $contextlist = provider::get_contexts_for_userid($u1->id);

        $this->assert_contextlist_equals($contextlist, [
            context_module::instance($cm1->cmid)->id,
            context_module::instance($cm3->cmid)->id,
            context_course::instance($c2->id)->id,
            context_module::instance($cm4->cmid)->id,
        ]);
    }

    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');

        extract($this->set_test_data());

        $eventbase = ['eventname' => 'core\\something', 'objectid' => 1, 'relateduserid' => 0, 'timecreated' => 1337];

        // Add a record for the course.
        $event = array_merge($eventbase, ['contextid' => context_course::instance($c1->id)->id]);
        $pg->create_log($school, $u1->id, $event);

        // Add a record for the same module for other user.
        $event = array_merge($eventbase, ['contextid' => context_module::instance($cm1->cmid)->id]);
        $pg->create_log($school, $u2->id, $event);

        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c1->id)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c2->id)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm1->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm2->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm3->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm4->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm5->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm6->cmid]));

        // Delete module context.
        provider::delete_data_for_all_users_in_context(context_module::instance($cm1->cmid));

        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c1->id)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c2->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm1->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm2->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm3->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm4->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm5->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm6->cmid]));

        // Delete module context (completion).
        provider::delete_data_for_all_users_in_context(context_module::instance($cm4->cmid));

        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c1->id)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c2->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm1->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm2->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm3->cmid)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_completion', ['cmid' => $cm4->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm5->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm6->cmid]));

        // Delete course context.
        provider::delete_data_for_all_users_in_context(context_course::instance($c2->id));

        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c1->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c2->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm1->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm2->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm3->cmid)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_completion', ['cmid' => $cm4->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm5->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm6->cmid]));

        // Do not delete children contexts because they are handled on their own.
        provider::delete_data_for_all_users_in_context(context_course::instance($c3->id));

        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c1->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c2->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm1->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm2->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm3->cmid)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_completion', ['cmid' => $cm4->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm5->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm6->cmid]));

        // Do not delete children contexts because they are handled on their own.
        provider::delete_data_for_all_users_in_context(context_course::instance($c4->id));

        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c1->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_course::instance($c2->id)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm1->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm2->cmid)->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => context_module::instance($cm3->cmid)->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_completion', ['cmid' => $cm4->cmid]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm5->cmid]));
        $this->assertFalse($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['cmid' => $cm6->cmid]));
    }

    public function test_delete_data_for_user() {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');
        $eventbase = ['eventname' => 'core\\something', 'objectid' => 1, 'relateduserid' => 0, 'timecreated' => 1337];

        extract($this->set_test_data());

        $c2ctx = context_course::instance($c2->id);
        $cm1ctx = context_module::instance($cm1->cmid);
        $cm3ctx = context_module::instance($cm3->cmid);

        // Add a similar record for other user in same course.
        $event = array_merge($eventbase, ['contextid' => $c2ctx->id]);
        $pg->create_log($school, $u2->id, $event);

        // Add a similar record for other user in same module.
        $event = array_merge($eventbase, ['contextid' => $cm1ctx->id]);
        $pg->create_log($school, $u2->id, $event);

        // Add related user id for other user.
        $event = array_merge($eventbase, ['contextid' => $cm3ctx->id, 'relateduserid' => $u2->id]);
        $pg->create_log($school, 2, $event);

        // Add a similar record for other user in same module.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u2->id,
            'courseid' => $c3->id,
            'cmid' => $cm4->cmid,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        // Add a similar record for our user in same course.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u1->id,
            'courseid' => $c4->id,
            'cmid' => 0,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $c2ctx->id, 'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $c2ctx->id, 'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $cm1ctx->id, 'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $cm1ctx->id, 'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $cm3ctx->id, 'relateduserid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $cm3ctx->id, 'relateduserid' => $u2->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0,
            'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0,
            'userid' => $u2->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c3->id, 'cmid' => $cm4->cmid,
            'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c3->id, 'cmid' => $cm4->cmid,
            'userid' => $u2->id]));

        $approvedcontexts = new approved_contextlist($u1, 'local_mootivated', [$c2ctx->id, $cm1ctx->id, $cm3ctx->id,
            context_course::instance($c4->id)->id]);
        provider::delete_data_for_user($approvedcontexts);

        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => $c2ctx->id, 'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $c2ctx->id, 'userid' => $u2->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => $cm1ctx->id, 'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $cm1ctx->id, 'userid' => $u2->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_log', ['contextid' => $cm3ctx->id, 'relateduserid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_log', ['contextid' => $cm3ctx->id, 'relateduserid' => $u2->id]));
        $this->assertFalse($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0,
            'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c4->id, 'cmid' => 0,
            'userid' => $u2->id]));
        // No deletion for completion of cmid yet.
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c3->id, 'cmid' => $cm4->cmid,
            'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c3->id, 'cmid' => $cm4->cmid,
            'userid' => $u2->id]));

        $approvedcontexts = new approved_contextlist($u1, 'local_mootivated', [context_module::instance($cm4->cmid)->id]);
        provider::delete_data_for_user($approvedcontexts);

        $this->assertFalse($DB->record_exists('local_mootivated_completion', ['courseid' => $c3->id, 'cmid' => $cm4->cmid,
            'userid' => $u1->id]));
        $this->assertTrue($DB->record_exists('local_mootivated_completion', ['courseid' => $c3->id, 'cmid' => $cm4->cmid,
            'userid' => $u2->id]));
    }

    public function test_extract_user_data() {
        global $DB;

        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm1 = $dg->create_module('assign', ['course' => $c1]);
        $cm2 = $dg->create_module('assign', ['course' => $c1]);

        $school = $pg->create_school();
        $eventbase = ['eventname' => 'core\\something', 'objectid' => 1, 'relateduserid' => 0, 'timecreated' => 1337];

        // Standard module.
        $pg->create_log($school, $u1->id, array_merge($eventbase, ['contextid' => context_module::instance($cm1->cmid)->id]));
        $pg->create_log($school, $u1->id, array_merge($eventbase, ['contextid' => context_module::instance($cm1->cmid)->id]));
        $pg->create_log($school, $u1->id, array_merge($eventbase, ['contextid' => context_module::instance($cm1->cmid)->id]));
        $pg->create_log($school, 2, array_merge($eventbase, ['contextid' => context_module::instance($cm1->cmid)->id,
            'relateduserid' => $u1->id]));
        $pg->create_log($school, $u2->id, array_merge($eventbase, ['contextid' => context_module::instance($cm1->cmid)->id]));

        // The other course.
        $pg->create_log($school, $u1->id, array_merge($eventbase, ['contextid' => context_course::instance($c2->id)->id]));
        $pg->create_log($school, $u1->id, array_merge($eventbase, ['contextid' => context_course::instance($c2->id)->id]));
        $pg->create_log($school, 2, array_merge($eventbase, ['contextid' => context_course::instance($c2->id)->id,
            'relateduserid' => $u1->id]));
        $pg->create_log($school, $u2->id, array_merge($eventbase, ['contextid' => context_course::instance($c2->id)->id]));

        // Add completion of a module.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u1->id,
            'courseid' => $c1->id,
            'cmid' => $cm1->cmid,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u2->id,
            'courseid' => $c1->id,
            'cmid' => $cm1->cmid,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        // Add completion of a course on its own.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u1->id,
            'courseid' => $c2->id,
            'cmid' => 0,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u2->id,
            'courseid' => $c2->id,
            'cmid' => 0,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        $approvedcontexts = new approved_contextlist($u1, 'local_mootivated', [
            context_module::instance($cm1->cmid)->id,
            context_course::instance($c2->id)->id,
        ]);

        provider::export_user_data($approvedcontexts);

        $writer = writer::with_context(context_course::instance($c2->id));
        $logs = $writer->get_data([get_string('pluginname', 'local_mootivated'),
            get_string('privacy:path:logs', 'local_mootivated')]);
        $completion = $writer->get_data([get_string('pluginname', 'local_mootivated'),
            get_string('privacy:path:completion', 'local_mootivated')]);

        $this->assertCount(3, $logs->data);
        $this->assertCount(1, $completion->data);

        $writer = writer::with_context(context_module::instance($cm1->cmid));
        $logs = $writer->get_data([get_string('pluginname', 'local_mootivated'),
            get_string('privacy:path:logs', 'local_mootivated')]);
        $completion = $writer->get_data([get_string('pluginname', 'local_mootivated'),
            get_string('privacy:path:completion', 'local_mootivated')]);

        $this->assertCount(4, $logs->data);
        $this->assertCount(1, $completion->data);
    }

    protected function set_test_data() {
        global $DB;

        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c3 = $dg->create_course();
        $c4 = $dg->create_course();
        $cm1 = $dg->create_module('assign', ['course' => $c1]);
        $cm2 = $dg->create_module('assign', ['course' => $c1]);
        $cm3 = $dg->create_module('assign', ['course' => $c1]);
        $cm4 = $dg->create_module('assign', ['course' => $c3]);
        $cm5 = $dg->create_module('assign', ['course' => $c3]);
        $cm6 = $dg->create_module('assign', ['course' => $c4]);

        $school = $pg->create_school();
        $eventbase = ['eventname' => 'core\\something', 'objectid' => 1, 'relateduserid' => 0, 'timecreated' => 1337];

        // Standard module.
        $event = array_merge($eventbase, ['contextid' => context_module::instance($cm1->cmid)->id]);
        $pg->create_log($school, $u1->id, $event);

        // Standard module for other user.
        $event = array_merge($eventbase, ['contextid' => context_module::instance($cm2->cmid)->id]);
        $pg->create_log($school, $u2->id, $event);

        // Standard module as related user.
        $event = array_merge($eventbase, ['contextid' => context_module::instance($cm3->cmid)->id, 'relateduserid' => $u1->id]);
        $pg->create_log($school, 2, $event);

        // The other course.
        $event = array_merge($eventbase, ['contextid' => context_course::instance($c2->id)->id]);
        $pg->create_log($school, $u1->id, $event);

        // Add completion of a module.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u1->id,
            'courseid' => $c3->id,
            'cmid' => $cm4->cmid,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        // Add completion of a module for another user.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u2->id,
            'courseid' => $c3->id,
            'cmid' => $cm5->cmid,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        // Add completion of a course on its own.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u2->id,
            'courseid' => $c4->id,
            'cmid' => 0,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        // Add completion of a module in that course.
        $DB->insert_record('local_mootivated_completion', (object) [
            'schoolid' => $school->get_id(),
            'userid' => $u2->id,
            'courseid' => $c4->id,
            'cmid' => $cm6->cmid,
            'state' => COMPLETION_COMPLETE,
            'timecreated' => 1337,
        ]);

        return compact('u1', 'u2', 'c1', 'c2', 'c3', 'c4', 'cm1', 'cm2', 'cm3', 'cm4', 'cm5', 'cm6', 'school');
    }

    protected function assert_contextlist_equals($contextlist, $expectedids) {
        $contextids = array_map('intval', $contextlist->get_contextids());
        sort($contextids);
        sort($expectedids);
        $this->assertEquals($expectedids, $contextids);
    }
}
