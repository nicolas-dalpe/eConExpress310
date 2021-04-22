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
 * Defines the editing form for the hybrid question type.
 *
 * @package    qtype
 * @subpackage hybrid
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Hybrid question type editing form.
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_hybrid_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('hybrid');

        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_hybrid'));
        $mform->setExpanded('responseoptions');

        // Response format.
        $mform->addElement('select', 'responseformat',
                get_string('responseformat', 'qtype_hybrid'), $qtype->response_formats());
        $mform->setDefault('responseformat', 'plain');

        // Require text.
        $mform->addElement('select', 'responserequired',
                get_string('responserequired', 'qtype_hybrid'), $qtype->response_required_options());
        $mform->setDefault('responserequired', 1);
        $mform->disabledIf('responserequired', 'responseformat', 'eq', 'noinline');

        // Input box size.
        $mform->addElement('select', 'responsefieldlines',
                get_string('responsefieldlines', 'qtype_hybrid'), $qtype->response_sizes());
        $mform->setDefault('responsefieldlines', 1);
        $mform->disabledIf('responsefieldlines', 'responseformat', 'eq', 'noinline');

        // Allow attachments.
        $mform->addElement('select', 'attachments',
                get_string('allowattachments', 'qtype_hybrid'), $qtype->attachment_options());
        $mform->setDefault('attachments', 0);

        // Require attachments.
        $mform->addElement('select', 'attachmentsrequired',
                get_string('attachmentsrequired', 'qtype_hybrid'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 0);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_hybrid');
        $mform->disabledIf('attachmentsrequired', 'attachments', 'eq', 0);

        // Accepted file types.
        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes', 'qtype_hybrid'));
        $mform->addHelpButton('filetypeslist', 'acceptedfiletypes', 'qtype_hybrid');
        $mform->disabledIf('filetypeslist', 'attachments', 'eq', 0);

        $mform->addElement('header', 'responsetemplateheader', get_string('responsetemplateheader', 'qtype_hybrid'));
        $mform->addElement('editor', 'responsetemplate', get_string('responsetemplate', 'qtype_hybrid'),
                array('rows' => 10),  array_merge($this->editoroptions, array('maxfiles' => 0)));
        $mform->addHelpButton('responsetemplate', 'responsetemplate', 'qtype_hybrid');

        $mform->addElement('header', 'graderinfoheader', get_string('graderinfoheader', 'qtype_hybrid'));
        $mform->setExpanded('graderinfoheader');
        $mform->addElement('editor', 'graderinfo', get_string('graderinfo', 'qtype_hybrid'),
                array('rows' => 10), $this->editoroptions);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->responseformat = $question->options->responseformat;
        $question->responserequired = $question->options->responserequired;
        $question->responsefieldlines = $question->options->responsefieldlines;
        $question->attachments = $question->options->attachments;
        $question->attachmentsrequired = $question->options->attachmentsrequired;
        $question->filetypeslist = $question->options->filetypeslist;

        $draftid = file_get_submitted_draft_itemid('graderinfo');
        $question->graderinfo = array();
        $question->graderinfo['text'] = file_prepare_draft_area(
            $draftid,           // Draftid
            $this->context->id, // context
            'qtype_hybrid',      // component
            'graderinfo',       // filarea
            !empty($question->id) ? (int) $question->id : null, // itemid
            $this->fileoptions, // options
            $question->options->graderinfo // text.
        );
        $question->graderinfo['format'] = $question->options->graderinfoformat;
        $question->graderinfo['itemid'] = $draftid;

        $question->responsetemplate = array(
            'text' => $question->options->responsetemplate,
            'format' => $question->options->responsetemplateformat,
        );

        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        // Don't allow both 'no inline response' and 'no attachments' to be selected,
        // as these options would result in there being no input requested from the user.
        if ($fromform['responseformat'] == 'noinline' && !$fromform['attachments']) {
            $errors['attachments'] = get_string('mustattach', 'qtype_hybrid');
        }

        // If 'no inline response' is set, force the teacher to require attachments;
        // otherwise there will be nothing to grade.
        if ($fromform['responseformat'] == 'noinline' && !$fromform['attachmentsrequired']) {
            $errors['attachmentsrequired'] = get_string('mustrequire', 'qtype_hybrid');
        }

        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
        if ($fromform['attachments'] != -1 && $fromform['attachments'] < $fromform['attachmentsrequired'] ) {
            $errors['attachmentsrequired']  = get_string('mustrequirefewer', 'qtype_hybrid');
        }

        return $errors;
    }

    public function qtype() {
        return 'hybrid';
    }
}
