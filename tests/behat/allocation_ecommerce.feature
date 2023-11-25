@enrol @enrol_programs @openlms @opensourcelearning @local_commerce
Feature: Program selfallocation tests

  Background:
    Given the following config values are set as admin:
      | config                    | value        | plugin         |
      | defaultpaymentprovider    | nullprovider | local_commerce |
      | source_ecommerce_allownew | 1            | enrol_programs |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager  | Manager   | 1        | manager@example.com  |
      | student1 | student   | 1        | student1@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability               | permission | role     | contextlevel | reference |
      | enrol/programs:view      | Allow      | pmanager | System       |           |
      | enrol/programs:edit      | Allow      | pmanager | System       |           |
      | enrol/programs:delete    | Allow      | pmanager | System       |           |
      | enrol/programs:addcourse | Allow      | pmanager | System       |           |
      | enrol/programs:allocate  | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user    | role     | contextlevel | reference |
      | manager | pmanager | System       |           |
    And the following "enrol_programs > programs" exist:
      | fullname    | idnumber | category | cohorts | public |
      | Program 001 | PR1      |          |         | 1      |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable eCommerce | 1 |

    When I log in as "manager"
    And I am on all programs management page
    And I follow "Program 001"
    And I follow "Allocation settings"
    And I click on "Update E-Commerce allocation" "link"
    And I set the following fields to these values:
      | Active | Yes |
    And I press dialog form button "Update"

    And the following "local_commerce > products" exist:
      | name           |
      | A product name |
    And the following "local_commerce > benefits" exist:
      | product        | pluginname     | instance | instancetype |
      | A product name | enrol_programs | PR1      |              |
    And the following "local_commerce > prices" exist:
      | product        |
      | A product name |
    And I log out

  @javascript
  Scenario: Student may purchase access from the products screen
    When I log in as "student1"
    And I browse products
    When I click on "Checkout" "button"
    Then I should see "Program 001"
    And I should see "Your purchase of A product name has been successful."

  @javascript
  Scenario: Student may purchase access from the program catalog
    When I log in as "student1"
    And I am on Program catalogue page
    And I follow "Program 001"
    When I click on "Checkout" "button"
    Then I should see "Program 001"
    And I should see "Your purchase of A product name has been successful."
