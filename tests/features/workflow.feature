Feature: Workflow
  In order to test that users have the correct permissions.
  We will create some users with required roles and test their access.

  @api
  Scenario: Workflows
    Given users:
      | name      | mail                | roles         | status |
      | Arthur    | arthur@sfgov.org    | writer        | 1      |
      | Penelope  | penelope@sfgov.org  | publisher     | 1      |
      | Admin     | admin@sfgov.org     | administrator | 1      |

    ## Make sure all roles are set properly.
    When I am logged in as "Admin"
    And  I visit "admin/people"
    Then I should see the text "Administrator" in the "Admin" row
    And  I should not see the text "Administrator" in the "Arthur" row
    And  I should not see the text "Publisher" in the "Arthur" row
    And  I should see the text "Writer" in the "Arthur" row
    And  I should not see the text "Administrator" in the "Penelope" row
    And  I should not see the text "Writer" in the "Penelope" row
    And  I should see the text "Publisher" in the "Penelope" row

    ## Test the admin can publish to any moderaiton state.
    When I visit "node/add/department"
    Then I should see "Draft" in the "#edit-moderation-state-wrapper" element
    And  I should see "Ready for review" in the "#edit-moderation-state-wrapper" element
    And  I should see "Published" in the "#edit-moderation-state-wrapper" element

    ## Make sure Writers cannot create Departments
    When I am logged in as "Arthur"
    And  I am on "node/add/department"
    Then I should see "Access denied"

    ## Make sure the Writers can use draft and Ready for review but not Published moderation states.
    When I visit "node/add/transaction"
    Then I should see "Draft" in the "#edit-moderation-state-wrapper" element
    And  I should see "Ready for review" in the "#edit-moderation-state-wrapper" element
    And  I should not see "Published" in the "#edit-moderation-state-wrapper" element

    ## Make sure the Writers can use the correct topic transitions.
    When I visit "node/add/topic"
    Then I should see "Draft" in the "#edit-moderation-state-wrapper" element
    And  I should see "Ready for review" in the "#edit-moderation-state-wrapper" element
    And  I should not see "Published" in the "#edit-moderation-state-wrapper" element

    ## Make sure Publishers cannot create Departments
    When I am logged in as "Penelope"
    And  I am on "node/add/department"
    Then I should see "Access denied"

    ## Make sure the Publishers can use any moderation state.
    When I visit "node/add/transaction"
    Then I should see "Draft" in the "#edit-moderation-state-wrapper" element
    And  I should see "Ready for review" in the "#edit-moderation-state-wrapper" element
    And  I should see "Published" in the "#edit-moderation-state-wrapper" element

    ## Make sure the Publishers can use the correct topic transitions.
    When I visit "node/add/topic"
    Then I should see "Draft" in the "#edit-moderation-state-wrapper" element
    And  I should see "Ready for review" in the "#edit-moderation-state-wrapper" element
    And  I should see "Published" in the "#edit-moderation-state-wrapper" element
