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
 * Mootivated renderer.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \local_mootivated\school;
use \local_mootivated\helper;

/**
 * Mootivated renderer class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mootivated_renderer extends plugin_renderer_base {

    /**
     * Render admin navigation.
     *
     * @param string $page Current page.
     * @return string
     */
    public function admin_navigation($page) {
        $tabs = [
            new tabobject('global', new moodle_url('/admin/settings.php', ['section' => 'local_mootivated']),
                get_string('global', 'local_mootivated'))
        ];
        $baseurl = new moodle_url('/local/mootivated/school.php');

        $schools = school::get_menu();
        foreach ($schools as $id => $name) {
            $tabs[] = new tabobject('school_' . $id, new moodle_url($baseurl, array('id' => $id)), $name);
        }

        if (helper::uses_sections()) {
            $tabs[] = new tabobject(
                'school_0', new moodle_url($baseurl, array('id' => 0)), get_string('addschool', 'local_mootivated')
            );
        }

        return $this->tabtree($tabs, $page);
    }

    /**
     * Render delete school button.
     *
     * @param school $school The school.
     * @return string
     */
    public function delete_school_button(school $school) {
        $deleteurl = new moodle_url('/local/mootivated/school.php', ['id' => $school->get_id(), 'delete' => 1]);
        $icon = new pix_icon('t/delete', '', '', ['class' => 'icon iconsmall']);
        return $this->action_link($deleteurl, get_string('deleteschool', 'local_mootivated'), null, null, $icon);
    }

    /**
     * Override pix_url to auto-handle deprecation.
     *
     * It's just simpler than having to deal with differences between
     * Moodle < 3.3, and Moodle >= 3.3.
     *
     * @param string $image The file.
     * @param string $component The component.
     * @return string
     */
    public function pix_url($image, $component = 'moodle') {
        if (method_exists($this, 'image_url')) {
            return $this->image_url($image, $component);
        }
        return parent::pix_url($image, $component);
    }

    /**
     * Render status report.
     *
     * @return string
     */
    public function status_report() {
        $ok = $this->pix_icon('i/valid', '');
        $nok = $this->pix_icon('i/invalid', '');

        $o = '';

        $missingbits = !helper::mootivated_role_exists() || !helper::webservices_enabled() || !helper::rest_enabled();
        if ($missingbits) {
            $o .= html_writer::tag('p', get_string('setupnotcomplete', 'local_mootivated'));
            $o .= html_writer::tag('p',
                $this->action_link(new moodle_url('/local/mootivated/quicksetup.php', ['sesskey' => sesskey()]),
                get_string('doitforme', 'local_mootivated'), null, ['class' => 'btn btn-default']));
        }

        $o .= html_writer::start_tag('table', ['class' => 'generaltable']);

        $o .= html_writer::start_tag('tr');
        $o .= html_writer::start_tag('td', ['width' => 20]);
        $o .= helper::webservices_enabled() ? $ok : $nok;
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        $o .= get_string('webservicesenabled', 'local_mootivated');
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        $o .= $this->action_link(new moodle_url('/admin/search.php', ['query' => 'enablewebservices']), '', null,
            null, new pix_icon('t/edit', get_string('edit')));
        $o .= html_writer::end_tag('td');
        $o .= html_writer::end_tag('tr');

        $o .= html_writer::start_tag('tr');
        $o .= html_writer::start_tag('td');
        $o .= helper::rest_enabled() ? $ok : $nok;
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        $o .= get_string('restprotocolenabled', 'local_mootivated');
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        $o .= $this->action_link(new moodle_url('/admin/settings.php', ['section' => 'webserviceprotocols']), '', null,
            null, new pix_icon('t/edit', get_string('edit')));
        $o .= html_writer::end_tag('td');
        $o .= html_writer::end_tag('tr');

        $o .= html_writer::start_tag('tr');
        $o .= html_writer::start_tag('td');
        $o .= helper::mootivated_role_exists() ? $ok : $nok;
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        $o .= get_string('mootivatedrolecreated', 'local_mootivated');
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        if (helper::mootivated_role_exists()) {
            $role = helper::get_mootivated_role();
            $contextid = context_system::instance()->id;
            $o .= $this->action_link(new moodle_url('/admin/roles/assign.php', ['contextid' => $contextid, 'roleid' => $role->id]),
                '', null, null, new pix_icon('t/assignroles', get_string('assignrole', 'role')));
            $o .= '&nbsp;';
            $o .= $this->action_link(new moodle_url('/admin/roles/define.php', ['action' => 'view', 'roleid' => $role->id]),
                '', null, null, new pix_icon('t/edit', get_string('edit')));
        } else {
            $o .= $this->action_link(new moodle_url('/admin/roles/manage.php'), '', null,
                null, new pix_icon('t/edit', get_string('edit')));
        }
        $o .= html_writer::end_tag('td');
        $o .= html_writer::end_tag('tr');


        $o .= html_writer::start_tag('tr');
        $o .= html_writer::start_tag('td');
        if (!helper::mootivated_role_was_ever_synced() && !helper::adhoc_role_sync_scheduled()) {
            $o .= $nok;
        } else {
            $o .= $ok;
        }
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        $o .= get_string('rolesync', 'local_mootivated') . ' ';
        if (!helper::adhoc_role_sync_scheduled()) {
            if (!helper::mootivated_role_was_ever_synced()) {
                $o .= get_string('lastfullsync', 'local_mootivated', get_string('never'));
            } else {
                $o .= get_string('lastfullsync', 'local_mootivated', userdate(helper::mootivated_role_last_synced(),
                    get_string('strftimedatetimeshort', 'langconfig')));
            }
        } else {
            $o .= get_string('scheduledorrunning', 'local_mootivated');
        }
        $o .= html_writer::end_tag('td');
        $o .= html_writer::start_tag('td');
        if (!helper::adhoc_role_sync_scheduled()) {
            $o .= $this->action_link(new moodle_url('/local/mootivated/rolesync.php', ['sesskey' => sesskey()]),
                '', null, null, new pix_icon('sync', get_string('syncnow', 'local_mootivated'), 'local_mootivated'));
        } else {
            $o .= html_writer::tag('span',
                $this->render(new pix_icon('sync', get_string('syncnow', 'local_mootivated'), 'local_mootivated')),
                ['style' => 'opacity: .5']
            );
        }
        $o .= '&nbsp;';
        $o .= $this->action_link(new moodle_url('/admin/tool/task/scheduledtasks.php'),
            '', null, null, new pix_icon('t/edit', get_string('edit')));
        $o .= html_writer::end_tag('td');
        $o .= html_writer::end_tag('tr');

        $o .= html_writer::end_tag('table');

        return $o;
    }

    /**
     * Users push trigger.
     *
     * @return string
     */
    public function queue_users_push() {
        if (!helper::mootivated_role_exists()) {
            return $this->notification(get_string('setupnotcomplete', 'local_mootivated'), 'notifyproblem');
        }

        $runningorscheduled = helper::adhoc_queue_users_for_push_scheduled();
        $lastrun = helper::when_users_for_push_were_queued();
        $userpusher = helper::get_user_pusher();
        $o = '';

        $o .= markdown_to_html(get_string('pushusersdesc', 'local_mootivated'));

        $o .= html_writer::start_tag('ul');

        $o .= html_writer::start_tag('li');
        $o .= get_string('usersinqueue', 'local_mootivated', $userpusher->count_queue());
        $o .= html_writer::end_tag('li');

        $o .= html_writer::start_tag('li');
        if (!empty($runningorscheduled)) {
            $o .= get_string('lastqueueuserspush', 'local_mootivated', get_string('scheduledorrunning', 'local_mootivated'));
        } else if (empty($lastrun)) {
            $o .= get_string('lastqueueuserspush', 'local_mootivated', get_string('never'));
        } else {
            $o .= get_string('lastqueueuserspush', 'local_mootivated', userdate($lastrun,
                get_string('strftimedatetimeshort', 'langconfig')));
        }
        $o .= html_writer::end_tag('li');

        $o .= html_writer::end_tag('ul');

        if (empty($runningorscheduled)) {
            $queueurl = new moodle_url('/local/mootivated/queueuserspush.php', ['sesskey' => sesskey()]);
            $o .= html_writer::link($queueurl, get_string('pushallusers', 'local_mootivated'), [
                'class' => 'btn btn-sm btn-small btn-secondary'
            ]);
        }

        return $o;
    }

}
