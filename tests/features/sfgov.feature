Feature: Sfgov Content
  In order to test some basic Behat functionality
  As a website user
  I need to be able to see that the Drupal and Drush drivers are working

@api @sfgov
Scenario: Create department
  Given "department" content:
  | title           |
  | Test Department |
  When I go to "departments/test-department"
  Then I should see "Test Department"
  And I should see a "meta[name*='department']" element

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
  And I should see a "meta[name*='transaction']" element
  Given I am not logged in
  When I go to "topics/test-topic"
  Then I should see "Test Topic"
  And I should see "Test Transaction"
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

@api @sfgov @sfgov-person-test
  Scenario: Create person and test url
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
  And I enter "published" for "moderation_state[0][state]"
  And I click the "#edit-submit" element
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
  And I enter "Apply for a aaaawesome thing for your city related thing" for "Title"
  And I enter "published" for "moderation_state[0][state]"
  And I enter "aaaawesome thing for your city related thing" for "field_sort_title[0][value]"
  And I press "Save"
  When I go to "apply-aaaawesome-thing-your-city-related-thing"
  And I click the ".sfgov-tabbed-navigation>ul>li>a[href*='edit']" element
  Then I should see "Published"
  When I go to "services/all"
  Then I should see "Apply for a aaaawesome thing for your city related thing"
  And I should see an "a[data-sort-title='aaaawesome thing for your city related thing']" element
  
@api @sfgov
  Scenario: Create transaction with translation
  Given I am logged in as a user with the "administrator" role
  When I go to "node/add/transaction"
  And I enter "Test translation transaction" for "Title"
  And I enter "published" for "moderation_state[0][state]"
  And I press "Save"
  When I go to "test-translation-transaction"
  And I click the ".sfgov-tabbed-navigation>ul>li>a[href*='translations']" element
  And I click the "a[hreflang='es']" element
  Then I should see "translation of Test Translation Transaction"
  When I enter "This is the translated version" for "Description"
  And I enter "published" for "moderation_state[0][state]"
  And I click the "#edit-submit" element
  Then I should be on "es/test-translation-transaction"
  And I should see "This is the translated version"

@api @sfgov
  Scenario: Create information page
  Given I am logged in as a user with the "administrator" role
  When I go to "node/add/information_page"
  And I enter "Test information page" for "Title"
  And I enter "Test information page description" for "Description"
  And I enter "Get a San Francisco birth certificate" for "field_transactions[0][target_id]"
  And I enter "Info page section heading" for "field_information_section[0][subform][field_title][0][value]"
  And I enter "County Clerk" for "field_dept[0][target_id]"
  And I enter "published" for "moderation_state[0][state]"
  And I click the "#edit-submit" element
  Then I should be on "information/test-information-page"
  And I should see "Test information page"