Feature: Sfgov Content
  In order to test some basic Behat functionality
  As a website user
  I need to be able to see that the Drupal and Drush drivers are working

@api @sfgov
Scenario: Create department node
  Given "department" content:
  | title           |
  | Test Department |
  When I go to "departments/test-department"
  Then I should see "Test Department"

@api @sfgov
  Scenario: Create topic and transaction related to that topic
  Given I am logged in as a user with the "administrator" role
  When I go to "node/add/topic"
  Then I enter "Test Topic" for "Title"
  And I enter "published" for "moderation_state[0][state]"
  And I press "Save"
  When I go to "topics/test-topic"
  Then I should see "Test Topic"
  When I go to "node/add/transaction"
  Then I enter "Test Transaction" for "Title"
  And I enter "http://sfgov.org" for "field_direct_external_url[0][uri]"
  And I enter "Test Topic" for "field_topics[0][target_id]"
  And I enter "published" for "moderation_state[0][state]"
  And I press "Save"
  When I go to "topics/test-topic"
  Then I should see "Test Topic"
  And I should see "Test Transaction"
  When I click "Test Transaction"
  Then I should see "Test Transaction"
  Given I am not logged in
  When I go to "topics/test-topic"
  Then I should see "Test Topic"
  And I should see "Test Transaction"
  And I should see a ".title-url" element
  And the ".title-url" element should have the attribute "target" equal to "_blank"
  When I go to "test-transaction"
  Then I should be on "http://sfgov.org"

@api @sfgov
  Scenario: Verify transaction view and related dept, topic, and has start page filters exist
  Given I am logged in as a user with the "administrator" role
  Given "department" content:
  | title           | status |
  | Test Department | 1      |
  When I go to "departments/test-department"
  Then I should see "Test Department"
  When I go to "node/add/topic"
  Then I enter "Test Topic" for "Title"
  And I enter "published" for "moderation_state[0][state]"
  And I press "Save"
  When I go to "topics/test-topic"
  Then I should see "Test Topic"
  When I go to "node/add/transaction"
  Then I enter "Test Transaction" for "Title"
  And I enter "http://external-url.org" for "field_direct_external_url[0][uri]"
  And I enter "Test Topic" for "field_topics[0][target_id]"
  And I enter "Test Department" for "field_departments[0][target_id]"
  And I enter "published" for "moderation_state[0][state]"
  And I press "Save"
  When I go to "admin/content/transactions"
  Then I should see "Test Transaction"
  And I should see a "select#edit-field-departments-target-id" element
  And I should see a "select#edit-field-topics-target-id" element
  And I should see a "select#edit-field-direct-external-url-uri-op" element
  When I select "Test Topic" from "field_topics_target_id"
  Then I should see "Test Transaction"

@api @sfgov
  Scenario: Create Person
  Given I am logged in as a user with the "administrator" role
  Given "person" content:
  | title              | first_name | last_name | person_url       | status |
  | Testfirst Testlast | Testfirst  | Testlast  | http://sfgov.org | 1      |
  When I go to "admin/content"
  Then I should see "Testfirst Testlast"
  When I click "Testfirst Testlast"
  Then I should see "Testfirst Testlast"
