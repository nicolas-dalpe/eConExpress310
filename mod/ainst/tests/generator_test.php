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
 * PHPUnit data generator tests.
 *
 * @package    mod_ainst
 * @category   phpunit
 * @copyright  2020 Knowledge One Inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * PHPUnit data generator testcase.
 *
 * @package    mod_ainst
 * @category   phpunit
 * @copyright  2020 Knowledge One Inc. {@link http://knowledgeone.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ainst_generator_testcase extends advanced_testcase {

    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest(true);

        // Generate a user and log the user in.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Make sure we have no assignment in the test database.
        $this->assertEquals(0, $DB->count_records('ainst'));

        /** @var mod_ainst_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_ainst');
        $this->assertInstanceOf('mod_ainst_generator', $generator);
        $this->assertEquals('ainst', $generator->get_modulename());

        // Generate 3 instances and make sure they exist.
        $generator->create_instance(array('course'=>$SITE->id));
        $generator->create_instance(array('course'=>$SITE->id));
        $page = $generator->create_instance(array('course'=>$SITE->id));
        $this->assertEquals(3, $DB->count_records('ainst'));

        $cm = get_coursemodule_from_instance('ainst', $page->id);
        $this->assertEquals($page->id, $cm->instance);
        $this->assertEquals('ainst', $cm->modname);
        $this->assertEquals($SITE->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($page->cmid, $context->instanceid);
    }
}
