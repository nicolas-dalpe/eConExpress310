@qtype @qtype_hybrid
Feature: Test editing an Hybrid question
  As a teacher
  In order to be able to update my Hybrid question
  I need to edit them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name      | template         |
      | Test questions   | hybrid | hybrid-001 | editor           |
      | Test questions   | hybrid | hybrid-002 | editorfilepicker |
      | Test questions   | hybrid | hybrid-003 | plain            |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  Scenario: Edit an Hybrid question
    When I choose "Edit question" action for "hybrid-001" in the question bank
    And I set the following fields to these values:
      | Question name | |
    And I press "id_submitbutton"
    Then I should see "You must supply a value here."
    When I set the following fields to these values:
      | Question name   | Edited hybrid-001 name |
      | Response format | No online text        |
    And I press "id_submitbutton"
    Then I should see "When \"No online text\" is selected, or responses are optional, you must allow at least one attachment."
    When I set the following fields to these values:
      | Response format | Plain text |
    And I press "id_submitbutton"
    Then I should see "Edited hybrid-001 name"
