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
 * School form.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mootivated\form;
defined('MOODLE_INTERNAL') || die();

use context_system;
use MoodleQuickForm;
use local_mootivated\school as schoolclass;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

MoodleQuickForm::registerElementType('local_mootivated_duration', __DIR__ . '/duration.php', 'local_mootivated_form_duration');
MoodleQuickForm::registerElementType('local_mootivated_integer', __DIR__ . '/integer.php', 'local_mootivated_form_integer');

/**
 * Form class.
 *
 * @package    local_mootivated
 * @copyright  2017 Mootivation Technologies Corp.
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class school extends \moodleform {

    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $school = $this->_customdata['school'];
        $usessections = $this->_customdata['usessections'];
        $renderer = $PAGE->get_renderer('local_mootivated');

        if ($usessections) {
            $mform->addElement('select', 'cohortid', get_string('cohortid', 'local_mootivated'), $this->get_cohorts());
            $mform->addHelpButton('cohortid', 'cohortid', 'local_mootivated');
        }

        $mform->addElement('text', 'privatekey', get_string('privatekey', 'local_mootivated'));
        $mform->setType('privatekey', PARAM_RAW);
        $mform->addHelpButton('privatekey', 'privatekey', 'local_mootivated');

        $mform->addElement('advcheckbox', 'sendusername', get_string('senduserinformation', 'local_mootivated'));
        $mform->setType('sendusername', PARAM_BOOL);
        $mform->addHelpButton('sendusername', 'senduserinformation', 'local_mootivated');

        $methods = [
            schoolclass::METHOD_EVENT => get_string('rewardmethod_event', 'local_mootivated'),
            schoolclass::METHOD_COMPLETION => get_string('rewardmethod_completion', 'local_mootivated'),
            schoolclass::METHOD_COMPLETION_ELSE_EVENT => get_string('rewardmethod_completionelseevent', 'local_mootivated'),
            schoolclass::METHOD_DASHBOARD_RULES => get_string('rewardmethod_dashboardrules', 'local_mootivated'),
        ];
        $mform->addElement('select', 'rewardmethod', get_string('rewardmethod', 'local_mootivated'), $methods);
        $mform->addHelpButton('rewardmethod', 'rewardmethod', 'local_mootivated');

        $mform->addElement('local_mootivated_integer', 'coursecompletionreward', get_string('coursecompletionreward',
            'local_mootivated'), ['style' => 'width: 5em;']);
        $mform->setType('coursecompletionreward', PARAM_INT);
        $mform->addHelpButton('coursecompletionreward', 'coursecompletionreward', 'local_mootivated');

        $leaderboardchoices = [
            0 => get_string('disabled', 'local_mootivated'),
            1 => get_string('enabled', 'local_mootivated'),
        ];
        $mform->addElement('select', 'leaderboardenabled', get_string('leaderboard', 'local_mootivated'), $leaderboardchoices);
        $mform->addHelpButton('leaderboardenabled', 'leaderboard', 'local_mootivated');
        $mform->hardFreeze('leaderboardenabled');

        $mform->addElement('header', 'eventcheatguardhdr', get_string('eventbasedcheatguard', 'local_mootivated'));
        $mform->setExpanded('eventcheatguardhdr', false);

        $mform->addElement('text', 'maxactions', get_string('maxactions', 'local_mootivated'));
        $mform->setType('maxactions', PARAM_INT);
        $mform->addHelpButton('maxactions', 'maxactions', 'local_mootivated');

        $mform->addElement('local_mootivated_duration', 'timeframeformaxactions', get_string('timeframeformaxactions',
            'local_mootivated'));
        $mform->setType('timeframeformaxactions', PARAM_INT);
        $mform->addHelpButton('timeframeformaxactions', 'timeframeformaxactions', 'local_mootivated');

        $mform->addElement('local_mootivated_duration', 'timebetweensameactions', get_string('timebetweensameactions',
            'local_mootivated'));
        $mform->setType('timebetweensameactions', PARAM_INT);
        $mform->addHelpButton('timebetweensameactions', 'timebetweensameactions', 'local_mootivated');

        $mform->addElement('header', 'rewardshdr', get_string('modcompletionrewards', 'local_mootivated'));
        $mform->addHelpButton('rewardshdr', 'modcompletionrewards', 'local_mootivated');
        $mform->setExpanded('rewardshdr', false);

        $mform->addElement('selectyesno', 'modcompletionrulesusedefault',
            get_string('userecommendedsettings', 'local_mootivated'));

        $calculator = $school->get_completion_points_calculator_by_mod();
        $mods = get_module_types_names();       // Only shows visible modules, which should be fine.
        foreach ($mods as $mod => $modname) {
            $mform->addElement('text', "modcompletionrule[$mod]", $modname, ['size' => 4]);
            $mform->setType("modcompletionrule[$mod]", PARAM_INT);
            $mform->disabledIf("modcompletionrule[$mod]", 'modcompletionrulesusedefault', 'eq', 1);
            // This is how we load the values in the form, not ideal but it works.
            $mform->setDefault("modcompletionrule[$mod]", $calculator->get_for_module($mod));
        }

        $this->add_action_buttons();

        if ($usessections && $school->get_id()) {
            $mform->addElement('static', '', '', $renderer->delete_school_button($school));
        }

        $completioneventenabled = schoolclass::METHOD_EVENT;
        $completionelseeventenabled = schoolclass::METHOD_COMPLETION_ELSE_EVENT;
        $completionenabled = schoolclass::METHOD_COMPLETION;
        $dashboardrulesenabled = schoolclass::METHOD_DASHBOARD_RULES;
        $PAGE->requires->js_init_code("
            var coursecompletionfield = Y.one('#id_coursecompletionreward_int').ancestor('.fitem');
            var rewardmethod = Y.one('#id_rewardmethod');
            var rewardfieldset = Y.one('#id_rewardshdr');
            var eventcheatguardfieldset = Y.one('#id_eventcheatguardhdr');
            var fn = function() {
                var val = rewardmethod.get('value');
                var hidecheatguard = val == '$completionenabled' || val == '$dashboardrulesenabled';
                var hiderewards = val == '$completioneventenabled' || val == '$dashboardrulesenabled';
                var hidecoursecompletion = val == '$dashboardrulesenabled';

                hidecheatguard ? eventcheatguardfieldset.hide() : eventcheatguardfieldset.show();
                hiderewards ? rewardfieldset.hide() : rewardfieldset.show();
                hidecoursecompletion ? coursecompletionfield.hide() : coursecompletionfield.show();
            }
            fn();
            rewardmethod.on('change', fn);", false);
    }

    /**
     * Get the list of cohorts.
     *
     * @return array Keys are cohort IDs, values are their names.
     */
    protected function get_cohorts() {
        $context = context_system::instance();
        $cohorts = cohort_get_cohorts($context->id, 0, 500)['cohorts'];
        $data = [0 => '---'];
        foreach ($cohorts as $id => $cohort) {
            $data[$id] = format_string($cohort->name);
        }
        return $data;
    }

    /**
     * Get data.
     *
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }

        if (!empty($data->modcompletionrulesusedefault) || empty($data->modcompletionrule)) {
            $data->modcompletionrules = [];

        } else {
            $rules = array_reduce(array_keys($data->modcompletionrule), function($carry, $mod) use ($data) {
                $points = $data->modcompletionrule[$mod];
                $carry[] = [
                    'points' => $points,
                    'mod' => $mod
                ];
                return $carry;
            }, []);
            $data->modcompletionrules = $rules;
        }

        if (!empty($data->modcompletionrules)) {
            $data->modcompletionrules = json_encode($rules);
        } else {
            $data->modcompletionrules = '';
        }

        unset($data->modcompletionrule);
        unset($data->modcompletionrulesusedefault);

        return $data;
    }

    /**
     * Set the data.
     *
     * @param stdClass $data The data.
     */
    public function set_data($data) {
        $data->modcompletionrulesusedefault = 1;
        if (!empty($data->modcompletionrules)) {
            $data->modcompletionrulesusedefault = 0;
            $rules = json_decode($data->modcompletionrules);
            if (empty($rules)) {
                $data->modcompletionrulesusedefault = 1;
            }
        }
        unset($data->modcompletionrules);

        parent::set_data($data);
    }

    /**
     * Validation.
     *
     * @param array $data The data.
     * @param array $files The files.
     * @return array
     */
    public function validation($data, $files) {
        if (!empty($data['modcompletionrulesusedefault']) || empty($data['modcompletionrule'])) {
            return [];
        }

        $rules = $data['modcompletionrule'];
        return array_reduce(array_keys($rules), function($carry, $mod) use ($rules) {
            $points = $rules[$mod];
            if ($points < 0 || $points > 50) {
                $carry["modcompletionrule[{$mod}]"] = get_string('invalidcoinvalueminmax', 'local_mootivated',
                    (object) ['min' => 0, 'max' => 50]);
            }
            return $carry;
        }, []);
    }
}
