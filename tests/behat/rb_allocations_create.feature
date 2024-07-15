@enrol_programs @local_reportbuilder @javascript @openlms
Feature: Can create program allocations report

  Scenario: Can create program allocations report
    Given I skip tests if "local_reportbuilder" is not installed

    And I log in as "admin"
    When I navigate to "Reports > Manage user reports" in site administration
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User Report         |
      | Source      | Program Allocations |
    And I click on "Create report" "button"
    And I click on "View This Report" "link"
