@enrol @enrol_programs @openlms
Feature: Upload other program evidence using csv

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
      | Cohort 3 | CH3      |
    And the following "courses" exist:
      | fullname | shortname | format | category |
      | Course 1 | C1        | topics | CAT1     |
      | Course 2 | C2        | topics | CAT2     |
      | Course 3 | C3        | topics | CAT3     |
      | Course 4 | C4        | topics | CAT4     |
      | Course 5 | C5        | topics | CAT4     |
      | Course 6 | C6        | topics | CAT4     |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | manager  | Site      | Manager  | manager@example.com  | m        |
      | manager1 | Manager   | 1        | manager1@example.com | m1       |
      | manager2 | Manager   | 2        | manager2@example.com | m2       |
      | viewer1  | Viewer    | 1        | viewer1@example.com  | v1       |
      | student1 | Student   | 1        | student1@example.com | s1       |
      | student2 | Student   | 2        | student2@example.com | s2       |
      | student3 | Student   | 3        | student3@example.com | s3       |
      | student4 | Student   | 4        | student4@example.com | s4       |
      | student5 | Student   | 5        | student5@example.com | s5       |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
      | student2 | CH1    |
      | student3 | CH1    |
      | student2 | CH2    |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | enrol/programs:view            | Allow      | pviewer  | System       |           |
      | enrol/programs:view            | Allow      | pmanager | System       |           |
      | enrol/programs:manageevidence  | Allow      | pmanager | System       |           |
      | enrol/programs:edit            | Allow      | pmanager | System       |           |
      | enrol/programs:delete          | Allow      | pmanager | System       |           |
      | enrol/programs:addcourse       | Allow      | pmanager | System       |           |
      | enrol/programs:allocate        | Allow      | pmanager | System       |           |
      | moodle/cohort:view             | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager   | manager       | System       |           |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |
    And the following "enrol_programs > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |
      | Program 001 | PR1      | Cat 1    |
      | Program 002 | PR2      | Cat 2    |
      | Program 003 | PR3      | Cat 3    |

  @javascript @_file_upload
  Scenario: Manager may upload CSV file for other evidence completion
    Given I log in as "manager1"
    And I am on all programs management page
    And I follow "Program 001"
    And I follow "Allocation settings"
    And I click on "Update Manual allocation" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I press dialog form button "Update"
    And I should see "Active" in the "Manual allocation:" definition list item
    And I click on "Users" "link" in the "#region-main" "css_element"
    And I should not see "Upload completions"

    When I press "Upload allocations"
    And I upload "enrol/programs/tests/fixtures/upload1.csv" file to "CSV file" filemanager
    And I set the following fields to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I press dialog form button "Continue"
    And the following fields match these values:
      | User identification column | username |
      | User mapping via           | Username |
      | First line is header       | 1        |
    And I press dialog form button "Upload allocations"
    Then I should see "3 users were assigned to program."
    Then I should see "Other completion evidence upload"

    When I press "Other completion evidence upload"
    And I upload "enrol/programs/tests/fixtures/uploadevidence.csv" file to "CSV file" filemanager
    And I set the following fields to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I press dialog form button "Continue"
    And I set the following fields to these values:
      | User identification column | username      |
      | User mapping via           | Username      |
      | First line is header       | 1             |
      | Completion date            | datecompleted |
    And I press dialog form button "Other completion evidence upload"
    And I should see "Evidence marked completed for 2 users"
    And I follow "Student 1"
    And I click on "Update other evidence" "link" in the "Program 001" "table_row"
    Then the following fields match these values:
      | evidencetimecompleted[day]     | 12    |
      | evidencetimecompleted[month]   | 02    |
      | evidencetimecompleted[year]    | 2023  |
      | evidencedetails                |       |

    And I am on all programs management page
    And I follow "Program 001"
    And I click on "Users" "link" in the "#region-main" "css_element"
    When I press "Other completion evidence upload"
    And I upload "enrol/programs/tests/fixtures/uploadevidencewithdetails.csv" file to "CSV file" filemanager
    And I set the following fields to these values:
      | CSV separator | ,     |
      | Encoding      | UTF-8 |
    And I press dialog form button "Continue"
    And I set the following fields to these values:
      | User identification column | username      |
      | User mapping via           | Username      |
      | First line is header       | 1             |
      | Completion date            | datecompleted |
      | Details                    | details       |
    And I press dialog form button "Other completion evidence upload"

    And I follow "Student 1"
    And I click on "Update other evidence" "link" in the "Program 001" "table_row"
    Then the following fields match these values:
      | evidencetimecompleted[day]     | 12                      |
      | evidencetimecompleted[month]   | 04                      |
      | evidencetimecompleted[year]    | 2023                    |
      | evidencedetails                | user has completed      |
