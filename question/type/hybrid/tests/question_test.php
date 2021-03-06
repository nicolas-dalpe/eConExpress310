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
 * Unit tests for the hybrid question definition class.
 *
 * @package    qtype
 * @subpackage hybrid
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');


/**
 * Unit tests for the matching question definition class.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_hybrid_question_test extends advanced_testcase {
    public function test_get_question_summary() {
        $hybrid = test_question_maker::make_an_hybrid_question();
        $hybrid->questiontext = 'Hello <img src="http://example.com/globe.png" alt="world" />';
        $this->assertEquals('Hello [world]', $hybrid->get_question_summary());
    }

    /**
     * Test summarise_response() when teachers view quiz attempts and then
     * review them to see what has been saved in the response history table.
     *
     * @dataProvider summarise_response_provider
     * @param int $responserequired
     * @param int $attachmentsrequired
     * @param string $answertext
     * @param int $attachmentuploaded
     * @param string $expected
     */
    public function test_summarise_response(int $responserequired, int $attachmentsrequired,
                                            string $answertext, int $attachmentuploaded, string $expected): void {
        $this->resetAfterTest();

        // If number of allowed attachments is set to 'Unlimited', generate 10 attachments for testing purpose.
        $numberofattachments = ($attachmentsrequired === -1) ? 10 : $attachmentsrequired;

        // Create sample attachments.
        $attachments = $this->create_user_and_sample_attachments($numberofattachments);

        // Create the hybrid question under test.
        $hybrid = test_question_maker::make_an_hybrid_question();
        $hybrid->start_attempt(new question_attempt_step(), 1);

        $hybrid->responseformat = 'editor';
        $hybrid->responserequired = $responserequired;
        $hybrid->attachmentsrequired = $attachmentsrequired;

        $this->assertEquals($expected, $hybrid->summarise_response(
            ['answer' => $answertext, 'answerformat' => FORMAT_HTML,  'attachments' => $attachments[$attachmentuploaded]]));
    }

    /**
     * Data provider for summarise_response() test cases.
     *
     * @return array List of data sets (test cases)
     */
    public function summarise_response_provider(): array {
        return [
            'text input required, not attachments required'  =>
                [1, 0, 'This is the text input for this hybrid.', 0, 'This is the text input for this hybrid.'],
            'Text input required, one attachments required, one uploaded'  =>
                [1, 1, 'This is the text input for this hybrid.', 1, 'This is the text input for this hybrid.Attachments: 0 (1 bytes)'],
            'Text input is optional, four attachments required, one uploaded'  => [0, 4, '', 1, 'Attachments: 0 (1 bytes)'],
            'Text input is optional, four attachments required, two uploaded'  => [0, 4, '', 2, 'Attachments: 0 (1 bytes), 1 (1 bytes)'],
            'Text input is optional, four attachments required, three uploaded'  => [0, 4, '', 3, 'Attachments: 0 (1 bytes), 1 (1 bytes), 2 (1 bytes)'],
            'Text input is optional, four attachments required, four uploaded'  => [0, 4, 'I have attached 4 files.', 4,
                'I have attached 4 files.Attachments: 0 (1 bytes), 1 (1 bytes), 2 (1 bytes), 3 (1 bytes)'],
            'Text input is optional, unlimited attachments required, one uploaded'  => [0, -1, '', 1, 'Attachments: 0 (1 bytes)'],
            'Text input is optional, unlimited attachments required, five uploaded'  => [0, -1, 'I have attached 5 files.', 5,
                'I have attached 5 files.Attachments: 0 (1 bytes), 1 (1 bytes), 2 (1 bytes), 3 (1 bytes), 4 (1 bytes)'],
            'Text input is optional, unlimited attachments required, ten uploaded'  =>
                [0, -1, '', 10, 'Attachments: 0 (1 bytes), 1 (1 bytes), 2 (1 bytes), 3 (1 bytes), 4 (1 bytes), ' .
                    '5 (1 bytes), 6 (1 bytes), 7 (1 bytes), 8 (1 bytes), 9 (1 bytes)']
        ];
    }

    public function test_is_same_response() {
        $hybrid = test_question_maker::make_an_hybrid_question();

        $hybrid->responsetemplate = '';

        $hybrid->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($hybrid->is_same_response(
                array(),
                array('answer' => '')));

        $this->assertTrue($hybrid->is_same_response(
                array('answer' => ''),
                array('answer' => '')));

        $this->assertTrue($hybrid->is_same_response(
                array('answer' => ''),
                array()));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => 'Hello'),
                array()));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => 'Hello'),
                array('answer' => '')));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => 0),
                array('answer' => '')));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => ''),
                array('answer' => 0)));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => '0'),
                array('answer' => '')));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => ''),
                array('answer' => '0')));
    }

    public function test_is_same_response_with_template() {
        $hybrid = test_question_maker::make_an_hybrid_question();

        $hybrid->responsetemplate = 'Once upon a time';

        $hybrid->start_attempt(new question_attempt_step(), 1);

        $this->assertTrue($hybrid->is_same_response(
                array(),
                array('answer' => 'Once upon a time')));

        $this->assertTrue($hybrid->is_same_response(
                array('answer' => ''),
                array('answer' => 'Once upon a time')));

        $this->assertTrue($hybrid->is_same_response(
                array('answer' => 'Once upon a time'),
                array('answer' => '')));

        $this->assertTrue($hybrid->is_same_response(
                array('answer' => ''),
                array()));

        $this->assertTrue($hybrid->is_same_response(
                array('answer' => 'Once upon a time'),
                array()));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => 0),
                array('answer' => '')));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => ''),
                array('answer' => 0)));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => '0'),
                array('answer' => '')));

        $this->assertFalse($hybrid->is_same_response(
                array('answer' => ''),
                array('answer' => '0')));
    }

    public function test_is_complete_response() {
        $this->resetAfterTest(true);

        // Create sample attachments.
        $attachments = $this->create_user_and_sample_attachments();

        // Create the hybrid question under test.
        $hybrid = test_question_maker::make_an_hybrid_question();
        $hybrid->start_attempt(new question_attempt_step(), 1);

        // Test the "traditional" case, where we must receive a response from the user.
        $hybrid->responserequired = 1;
        $hybrid->attachmentsrequired = 0;
        $hybrid->responseformat = 'editor';

        // The empty string should be considered an incomplete response, as should a lack of a response.
        $this->assertFalse($hybrid->is_complete_response(array('answer' => '')));
        $this->assertFalse($hybrid->is_complete_response(array()));

        // Any nonempty string should be considered a complete response.
        $this->assertTrue($hybrid->is_complete_response(array('answer' => 'A student response.')));
        $this->assertTrue($hybrid->is_complete_response(array('answer' => '0 times.')));
        $this->assertTrue($hybrid->is_complete_response(array('answer' => '0')));

        // Test the case where two files are required.
        $hybrid->attachmentsrequired = 2;

        // Attaching less than two files should result in an incomplete response.
        $this->assertFalse($hybrid->is_complete_response(array('answer' => 'A')));
        $this->assertFalse($hybrid->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[0])));
        $this->assertFalse($hybrid->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[1])));

        // Anything without response text should result in an incomplete response.
        $this->assertFalse($hybrid->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[2])));

        // Attaching two or more files should result in a complete response.
        $this->assertTrue($hybrid->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[2])));
        $this->assertTrue($hybrid->is_complete_response(
                array('answer' => 'A', 'attachments' => $attachments[3])));

        // Test the case in which two files are required, but the inline
        // response is optional.
        $hybrid->responserequired = 0;

        $this->assertFalse($hybrid->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[1])));

        $this->assertTrue($hybrid->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[2])));

        // Test the case in which both the response and online text are optional.
        $hybrid->attachmentsrequired = 0;

        // Providing no answer and no attachment should result in an incomplete
        // response.
        $this->assertFalse($hybrid->is_complete_response(
                array('answer' => '')));
        $this->assertFalse($hybrid->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[0])));

        // Providing an answer _or_ an attachment should result in a complete
        // response.
        $this->assertTrue($hybrid->is_complete_response(
                array('answer' => '', 'attachments' => $attachments[1])));
        $this->assertTrue($hybrid->is_complete_response(
                array('answer' => 'Answer text.', 'attachments' => $attachments[0])));

        // Test the case in which we're in "no inline response" mode,
        // in which the response is not required (as it's not provided).
        $hybrid->reponserequired = 0;
        $hybrid->responseformat = 'noinline';
        $hybrid->attachmensrequired = 1;

        $this->assertFalse($hybrid->is_complete_response(
                array()));
        $this->assertFalse($hybrid->is_complete_response(
                array('attachments' => $attachments[0])));

        // Providing an attachment should result in a complete response.
        $this->assertTrue($hybrid->is_complete_response(
                array('attachments' => $attachments[1])));

        // Ensure that responserequired is ignored when we're in inline response mode.
        $hybrid->reponserequired = 1;
        $this->assertTrue($hybrid->is_complete_response(
                array('attachments' => $attachments[1])));

    }

    /**
     * Create sample attachemnts and retun generated attachments.
     * @param int $numberofattachments
     * @return array
     */
    private function create_user_and_sample_attachments($numberofattachments = 4) {
        // Create a new logged-in user, so we can test responses with attachments.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create sample attachments to use in testing.
        $helper = test_question_maker::get_test_helper('hybrid');
        $attachments = [];
        for ($i = 0; $i < ($numberofattachments + 1); ++$i) {
            $attachments[$i] = $helper->make_attachments_saver($i);
        }
        return $attachments;
    }
}
