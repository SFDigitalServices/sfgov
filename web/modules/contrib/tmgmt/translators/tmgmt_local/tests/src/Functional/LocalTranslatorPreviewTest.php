<?php

namespace Drupal\Tests\tmgmt_local\Functional;

use Drupal\tmgmt\Entity\Translator;

/**
 * Preview related tests for the local translator.
 *
 * @group tmgmt
 */
class LocalTranslatorPreviewTest extends LocalTranslatorTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'tmgmt_content',
  ];

  /**
   * Test the preview of TMGMT local.
   */
  public function testPreview() {
    // Create translatable node.
    $this->createNodeType('article', 'Article', TRUE);
    $node = $this->createTranslatableNode('article', 'en');
    $node->setUnpublished();
    $node->save();
    $translator = Translator::load('local');
    $job = $this->createJob('en', 'de');
    $job->translator = $translator;
    $job->save();
    /** @var \Drupal\tmgmt\JobItemInterface $job_item */
    $job_item = tmgmt_job_item_create('content', $node->getEntityTypeId(), $node->id(), array('tjid' => $job->id()));
    $job_item->save();

    // Create another local translator with the required abilities.
    $this->loginAsAdmin($this->localManagerPermissions);
    // Configure language abilities.
    $edit = array(
      'tmgmt_translation_skills[0][language_from]' => 'en',
      'tmgmt_translation_skills[0][language_to]' => 'de',
    );
    $this->drupalPostForm('user/' . $this->admin_user->id() . '/edit', $edit, t('Save'));

    $this->drupalGet('admin/tmgmt/jobs/' . $job->id());
    $edit = [
      'settings[translator]' => $this->admin_user->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit to provider'));

    $this->drupalGet('translate');
    $this->clickLink('View');
    $this->clickLink('Translate');

    // Check preview.
    $edit = array(
      'title|0|value[translation]' => $translation1 = 'German translation of title',
      'body|0|value[translation][value]' => $translation2 = 'German translation of body',
    );
    $this->drupalPostForm(NULL, $edit, t('Preview'));
    $this->assertResponse(200);
    $this->assertText($translation1);
    $this->assertText($translation2);

    $this->drupalGet('translate');
    $this->clickLink('View');
    $this->clickLink('Translate');

    // Assert source link.
    $this->assertLink($node->getTitle());

    // Test that local translator can access an unpublished node.
    $this->clickLink($node->getTitle());
    $this->assertText($node->getTitle());

    $this->drupalGet('admin/tmgmt/items/' . $job_item->id());
    // Check the preliminary state warning appears.
    $this->assertText('The translations below are in preliminary state and can not be changed.');
    // Checking if the 'Save as completed' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-save-as-completed"]');
    $this->assertTrue(empty($elements), "'Save as completed' button does not appear.");
    // Checking if the 'Save' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-save"]');
    $this->assertTrue(empty($elements), "'Save' button does not appear.");
    // Checking if the 'Validate' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-validate"]');
    $this->assertFalse(empty($elements), "'Validate' button appears.");
    // Checking if the 'Validate HTML tags' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-validate-html"]');
    $this->assertFalse(empty($elements), "'Validate HTML tags' button appears.");
    // Checking if the 'Preview' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-preview"]');
    $this->assertFalse(empty($elements), "'Preview' button appears.");
    // Checking if the '✓' button is not displayed.
    $elements = $this->xpath('//*[@id="edit-title0value-actions-finish-title0value"]');
    $this->assertTrue(empty($elements), "'✓' button does not appear.");
    // Checking translation is readonly.
    $this->assertRaw('data-drupal-selector="edit-title0value-translation" disabled="disabled"');
  }

}
