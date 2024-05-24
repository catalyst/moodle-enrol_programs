@enrol @enrol_programs @openlms
Feature: Test import notifications for enrol_programs

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | manager1  | Manager   | 1         | manager1@example.com  |
    And the following "roles" exist:
      | name              | shortname |
      | Program manager   | pmanager  |
    And the following "permission overrides" exist:
      | capability                      | permission | role      | contextlevel | reference |
      | enrol/programs:view             | Allow      | pmanager  | System       |           |
      | enrol/programs:clone            | Allow      | pmanager  | System       |           |
      | enrol/programs:edit             | Allow      | pmanager  | System       |           |
      | enrol/programs:delete           | Allow      | pmanager  | System       |           |
      | enrol/programs:addcourse        | Allow      | pmanager  | System       |           |
      | enrol/programs:allocate         | Allow      | pmanager  | System       |           |
      | enrol/programs:manageallocation | Allow      | pmanager  | System       |           |
      | moodle/cohort:view              | Allow      | pmanager  | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
    And the following "enrol_programs > programs" exist:
      | fullname    | idnumber | category | cohorts  | public |
      | Program 000 | PR0      |          |          |        |
      | Program 001 | PR1      |          |          | 1      |
  @javascript
  Scenario: Manager can import notification from one program to another
    When I log in as "manager1"
    And I am on all programs management page
    And I follow "Program 000"
    And I follow "Notifications"
    And I follow "Add notification"
    And I set the following fields to these values:
      |  User allocated        |   1   |
      |  Program started       |   1   |
      |  Program due date soon |   1   |
    And I press dialog form button "Add notification"

    When I am on all programs management page
    And I follow "Program 001"
    And I follow "Notifications"
    And I follow "Import notification"
    And I set the following fields to these values:
      |  Select program        |   Program 000   |
    And I press dialog form button "Import notification"
    Then I set the following fields to these values:
      |  User allocated        |   1   |
      |  Program due date soon |   1   |
    And I press dialog form button "Confirm import notification"
    Then I should see "User allocated"
    And I should see "Program due date soon"
    And I should not see "Program started"
