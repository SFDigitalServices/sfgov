Feature: Sfgov Content
  In order to test some basic Behat functionality
  As a website user
  I need to be able to see that the Drupal and Drush drivers are working

@api @sfgov @anttest
Scenario: Create department node
  Given "department" content:
  | title           |
  | Test Department |
  When I go to "departments/test-department"
  Then I should see "Test Department"
  And I should see a "meta[name='department']" element

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
  And I enter "http://google.com" for "field_direct_external_url[0][uri]"
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
  And the "target" attribute of the ".title-url" element should contain "_blank"
  When I go to "test-transaction"
  Then I should be on "http://google.com"

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
  Scenario: Create Person and Test URL
  Given I am logged in as a user with the "administrator" role
  Given "person" content:
  | title              | status |
  | Testfirst Testlast | 1      |
  When I go to "admin/content"
  Then I should see "Testfirst Testlast"
  When I go to "person/testfirst-testlast"
  Then I should see "Testfirst Testlast"
  When I click the ".sfgov-tabbed-navigation>ul>li>a[href*='edit']" element
  And I enter "http://google.com" for "field_direct_external_url[0][uri]"
  And I enter "Testfirst" for "field_first_name[0][value]"
  And I enter "Testlast" for "field_last_name[0][value]"
  And I attach the file "london-breed.jpg" to "files[field_photo_0]"
  And I press "Save"
  Then I should be on "person/testfirst-testlast"
  And the "style" attribute of the ".person-photo" element should contain "london-breed.jpg"
  And I should see "Success"
  Given I am not logged in
  When I go to "person/testfirst-testlast"
  Then I should be on "http://google.com"

@api @sfgov
  Scenario: Create transaction with sort title and verify sort title exists as an attribute
  Given I am logged in as a user with the "administrator" role
  When I go to "node/add/transaction"
  And I enter "Apply for a zawesome thing for your city related thing" for "Title"
  And I enter "published" for "moderation_state[0][state]"
  And I enter "Zawesome thing for your city related thing" for "field_sort_title[0][value]"
  And I press "Save"
  When I go to "apply-zawesome-thing-your-city-related-thing"
  And I click the ".sfgov-tabbed-navigation>ul>li>a[href*='edit']" element
  Then I should see "Published"
  When I go to "services/all?page=2"
  Then I should see "Apply for a zawesome thing for your city related thing"
  And I should see an "a[data-sort-title='Zawesome thing for your city related thing']" element
  