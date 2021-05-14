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
 * Notifier tests.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/mootivated/tests/fixtures/client.php');

use local_mootivated\helper;
use local_mootivated\notifier;
use local_mootivated\school_resolver;
use local_mootivated\global_school_resolver;

/**
 * Notifier tests class.
 *
 * @package    local_mootivated
 * @copyright  2018 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mootivated_notified_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_send_notifications_username() {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');

        $school1 = $pg->create_school();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $notifications = [
            // Nothing wrong here.
            (object) [
                'id' => 1,
                'user_doctype' => 'user',
                'user_username' => $u1->username,
                'user_plugin_id' => md5(get_site_identifier() . '_' . $u1->id),
                'subject' => 'Hello world!',
                'message' => 'You have a message to read!',
            ],
            // The username does not exist.
            (object) [
                'id' => 2,
                'user_doctype' => 'user',
                'user_username' => 'doesnotexistok',
                'user_plugin_id' => md5(get_site_identifier() . '_' . $u1->id),
                'subject' => 'Hello world! 2',
                'message' => 'You have a message to read! 2',
            ],
            // The plugin_id is not valid.
            (object) [
                'id' => 3,
                'user_doctype' => 'user',
                'user_username' => $u2->username,
                'user_plugin_id' => 'notvalid',
                'subject' => 'Hello world! 3',
                'message' => 'You have a message to read! 3',
            ],
        ];

        $client = new local_mootivated_test_client(function() {});
        $resolver = new global_school_resolver();
        $notifier = new notifier($client, $resolver);

        $sink = $this->redirectMessages();

        $result = (object) $notifier->send_notifications($notifications);

        // Assert we sent the first notification.
        $this->assertCount(1, $result->sent);
        $this->assertTrue(in_array(1, $result->sent));

        // Assert the message sent.
        $this->assertEquals(1, $sink->count());
        $messages = $sink->get_messages();
        $this->assertEquals('Hello world!', $messages[0]->subject);
        $this->assertEquals('You have a message to read!', $messages[0]->fullmessage);
        $this->assertEquals($u1->id, $messages[0]->useridto);

        // Assert the errors.
        $this->assertCount(2, $result->errors);

        $error = array_shift($result->errors);
        $this->assertEquals(2, $error['id']);
        $this->assertEquals('username_not_found', $error['code']);

        $error = array_shift($result->errors);
        $this->assertEquals(3, $error['id']);
        $this->assertEquals('invalid_plugin_id', $error['code']);
    }

    public function test_send_notifications_dashboard_user() {
        global $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('local_mootivated');

        $school1 = $pg->create_school();
        $u1 = $dg->create_user();

        $notifications = [
            // Nothing wrong here.
            (object) [
                'id' => 1,
                'user_doctype' => 'dashboard_user',
                'user_email' => $u1->email,
                'subject' => 'Hello world!',
                'message' => 'You have a message to read!',
            ],
            // The email does not exist.
            (object) [
                'id' => 2,
                'user_doctype' => 'dashboard_user',
                'user_email' => 'none',
                'subject' => 'Hello world! 2',
                'message' => 'You have a message to read! 2',
            ],
        ];

        $client = new local_mootivated_test_client(function() {});
        $resolver = new global_school_resolver();
        $notifier = new notifier($client, $resolver);

        $sink = $this->redirectMessages();

        $result = (object) $notifier->send_notifications($notifications);

        // Assert we sent the first notification.
        $this->assertCount(1, $result->sent);
        $this->assertTrue(in_array(1, $result->sent));

        // Assert the message sent.
        $this->assertEquals(1, $sink->count());
        $messages = $sink->get_messages();
        $this->assertEquals('Hello world!', $messages[0]->subject);
        $this->assertEquals('You have a message to read!', $messages[0]->fullmessage);
        $this->assertEquals($u1->id, $messages[0]->useridto);

        // Assert the errors.
        $this->assertCount(1, $result->errors);

        $error = array_shift($result->errors);
        $this->assertEquals(2, $error['id']);
        $this->assertEquals('email_not_found', $error['code']);
    }
}
