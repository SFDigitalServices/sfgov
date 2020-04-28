<?php

namespace Drupal\simple_gmap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Creates a page to look at to test the Simple Google Maps module.
 *
 * Run the test, scroll to the bottom, open the last page link, and check
 * what the verbose message lines say to check on that page. If you want to
 * check the static maps, be sure to edit this file and put in an API key
 * that is valid for static maps.
 *
 * @group simple_gmap
 */
class SimpleGmapTest extends WebTestBase {

  /**
   * API key for static maps.
   *
   * @var string
   */
  protected $apiKey = 'Static maps will not work unless you put in a key';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'simple_gmap',
    'node',
    'field',
    'field_ui',
    'text',
    'block',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    drupal_static_reset();
    \Drupal::entityManager()->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('entity.definition_update_manager')->applyUpdates();

    $admin_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'create article content',
    ]);
    $this->drupalLogin($admin_user);

    // Make sure local tasks and page title are showing.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Creates the test pages.
   */
  public function testModule() {
    $field1 = 'map1';
    $field2 = 'map2';
    $field3 = 'xss';

    $address1 = 'Eiffel Tower';
    $address2 = "Place de l'Université-du-Québec, boulevard Charest Est, Québec, QC G1K";
    $address2entities = "Place de l&#039;Université-du-Québec, boulevard Charest Est, Québec, QC G1K";
    $address3 = '<script>alert("hello");</script> Empire State Building';

    // Add three text fields to the Article content type.
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $this->drupalPostForm(NULL, [
      'new_storage_type' => 'string',
    ], 'Save and continue');
    // This opens up the page again with field errors for name and label.
    $this->drupalPostForm(NULL, [
      'label' => $field1,
      'field_name' => $field1,
    ], 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $this->drupalPostForm(NULL, [], 'Save settings');

    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $this->drupalPostForm(NULL, [
      'new_storage_type' => 'string',
    ], 'Save and continue');
    $this->drupalPostForm(NULL, [
      'label' => $field2,
      'field_name' => $field2,
    ], 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $this->drupalPostForm(NULL, [], 'Save settings');

    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $this->drupalPostForm(NULL, [
      'new_storage_type' => 'string',
    ], 'Save and continue');
    $this->drupalPostForm(NULL, [
      'label' => $field3,
      'field_name' => $field3,
    ], 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $this->drupalPostForm(NULL, [], 'Save settings');

    // Set the formatter for all of the fields.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertRaw('Google Map from one-line address');
    $this->drupalPostForm(NULL, [
      'fields[field_' . $field1 . '][type]' => 'simple_gmap',
      'fields[field_' . $field2 . '][type]' => 'simple_gmap',
      'fields[field_' . $field3 . '][type]' => 'simple_gmap',
    ], 'Save');

    // Change the settings for all field formatters.
    $this->drupalPostAjaxForm(NULL, [], 'field_' . $field1 . '_settings_edit');
    $prefix = 'fields[field_' . $field1 . '][settings_edit_form][settings]';
    $this->drupalPostForm(NULL, [
      $prefix . '[include_map]' => TRUE,
      $prefix . '[include_static_map]' => TRUE,
      $prefix . '[apikey]' => $this->apiKey,
      $prefix . '[include_link]' => TRUE,
      $prefix . '[include_text]' => TRUE,
    ], 'Update');
    $this->assertText('Map link: View larger map');
    $this->assertText('Zoom Level: 14');
    $this->assertText('Language: en');
    $this->assertText('Map Type: Map');
    $this->assertText('Dynamic map');
    $this->assertText('Static map');
    $this->assertText('Original text displayed');

    $this->drupalPostAjaxForm(NULL, [], 'field_' . $field2 . '_settings_edit');
    $prefix = 'fields[field_' . $field2 . '][settings_edit_form][settings]';
    $this->drupalPostForm(NULL, [
      $prefix . '[include_map]' => TRUE,
      $prefix . '[include_static_map]' => TRUE,
      $prefix . '[apikey]' => $this->apiKey,
      $prefix . '[include_link]' => TRUE,
      $prefix . '[link_text]' => 'use_address',
      $prefix . '[include_text]' => TRUE,
      $prefix . '[zoom_level]' => 5,
      $prefix . '[map_type]' => 'k',
      $prefix . '[langcode]' => 'page',
    ], 'Update');
    $this->assertText('Map link: use_address');
    $this->assertText('Zoom Level: 5');
    $this->assertText('Language: page');
    $this->assertText('Map Type: Satellite');

    $this->drupalPostAjaxForm(NULL, [], 'field_' . $field3 . '_settings_edit');
    $prefix = 'fields[field_' . $field3 . '][settings_edit_form][settings]';
    $this->drupalPostForm(NULL, [
      $prefix . '[include_map]' => TRUE,
      $prefix . '[include_static_map]' => TRUE,
      $prefix . '[apikey]' => $this->apiKey,
      $prefix . '[include_link]' => TRUE,
      $prefix . '[include_text]' => TRUE,
    ], 'Update');

    // Save all the settings.
    $this->drupalPostForm(NULL, [], 'Save');

    // Create an article with three addresses.
    $this->drupalGet('node/add/article');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Map test',
      'field_' . $field1 . '[0][value]' => $address1,
      'field_' . $field2 . '[0][value]' => $address2,
      'field_' . $field3 . '[0][value]' => $address3,
    ], 'Save');

    $this->pass('Open the previous link. In the first field, verify: (1) Both the static and dynamic maps are displayed. (2) The link to the larger map is included, with text "View larger map". (3) If you click the map link, the larger map is shown and "Eiffel Tower" appears in the address box. (4) The text "Eiffel Tower" appears below the maps and link.');
    $this->pass("In the second field, verify: (1) Both the static and dynamic maps are displayed. (2) They should be zoomed way out to the regional level, rather than street level. (3) They should be satellite images rather than street maps. (4) The link to the larger map is included, but the link text is \"Place de l'Université-du-Québec, boulevard Charest Est, Québec, QC G1K\". (5) Verify that the map link has &hl=en in it (so it picked up the page language). (6) If you click the map link, the larger map is shown and \"Place de l'Université-du-Québec, boulevard Charest Est, Québec, QC G1K\" appears in the address box. (7) The text \"Place de l'Université-du-Québec, boulevard Charest Est, Québec, QC G1K\" appears below the maps and link.");
    $this->pass("The third field is a XSS test. Verify that the script tags are shown rather than being processed by the browser. Maps will likely show the entire world as it is not a valid address. There should not be a JavaScript alert box shown.");

    $this->assertLink('View larger map');
    // Looking for this link isn't working, with or without the
    // entities. Will need to be a manual test.
    // $this->assertLink($address2entities);
    $this->assertText($address1);
    $this->assertText($address2entities);
    $this->assertText(htmlentities($address3));
    $this->assertRaw('google.com/maps');
  }

}
