@mod @mod_geogebra
Feature: Preview GeoGebra activity
  In order to preview GeoGebra activity
  As a teacher
  I need to preview GeoGebra activity.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "GeoGebra" to section "1" and I fill the form with:
      | Name | Test GeoGebra |
      | Description | A testing GeoGebra activity |
      | Type | External URL |
      | URL | https://tube.geogebra.org/files/00/02/92/27/material-2922763.ggb?v=1458139106 |

  Scenario: Preview GeoGebra
    Given I follow "Test GeoGebra"
    When I click on "Preview Geogebra activity" "link"
    Then I should see "Test GeoGebra"
    And I should see "A testing GeoGebra activity"