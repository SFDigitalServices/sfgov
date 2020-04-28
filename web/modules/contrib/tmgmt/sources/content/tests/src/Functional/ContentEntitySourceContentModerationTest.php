<?php

namespace Drupal\Tests\tmgmt_content\Functional;

use Drupal\Tests\tmgmt\Functional\TmgmtEntityTestTrait;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests content entity source integration with content moderation.
 *
 * @group tmgmt
 */
class ContentEntitySourceContentModerationTest extends TMGMTTestBase {

  use TmgmtEntityTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['tmgmt_content', 'content_moderation'];

  /**
   * The workflow entity.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->addLanguage('de');
    $this->addLanguage('es');

    $this->createNodeType('article', 'Article', TRUE);
    $this->createEditorialWorkflow('article');
  }

  /**
   * Test the content moderation workflow with translatable nodes.
   */
  function testModeratedContentTranslations() {
    $this->loginAsTranslator([
      'administer tmgmt',
      'translate any entity',
      'create content translations',
      'access content',
      'view own unpublished content',
      'edit own article content',
      'access content overview',
      'view all revisions',
      'view latest version',
      'use ' . $this->workflow->id() . ' transition create_new_draft',
      'use ' . $this->workflow->id() . ' transition publish',
    ]);

    // Create a node in English.
    $title = 'Moderated node';
    $node = $this->createNode([
      'title' => $title,
      'type' => 'article',
      'langcode' => 'en',
      'moderation_state' => 'published',
      'uid' => $this->translator_user->id(),
    ]);

    // Go to content overview and translate a node.
    $this->drupalGet('admin/tmgmt/sources');
    $this->assertLink($title);
    $edit = [
      'items[' . $node->id() . ']' => $node->id(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Request translation');
    $this->assertText('One job needs to be checked out.');
    $this->assertText($title . ' (English to ?, Unprocessed)');
    $edit = [
      'target_language' => 'de',
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit to provider');
    $this->assertText(t('The translation of @title to German is finished and can now be reviewed.', ['@title' => $title]));

    $this->drupalGet('admin/tmgmt/jobs');
    $this->clickLink('Manage');
    $this->assertText($title . ' (English to German, Active)');
    $this->clickLink('Review');
    $this->assertText('Job item ' . $title);

    // Assert there is no content moderation form element.
    $this->assertNoFieldByName('moderation_state|0|value[source]');
  }

  /**
   * Creates a workflow entity.
   *
   * @param string $bundle
   *   The node bundle.
   */
  protected function createEditorialWorkflow($bundle) {
    if (!isset($this->workflow)) {
      $this->workflow = Workflow::create([
        'type' => 'content_moderation',
        'id' => $this->randomMachineName(),
        'label' => 'Editorial',
        'type_settings' => [
          'states' => [
            'archived' => [
              'label' => 'Archived',
              'weight' => 5,
              'published' => FALSE,
              'default_revision' => TRUE,
            ],
            'draft' => [
              'label' => 'Draft',
              'published' => FALSE,
              'default_revision' => FALSE,
              'weight' => -5,
            ],
            'published' => [
              'label' => 'Published',
              'published' => TRUE,
              'default_revision' => TRUE,
              'weight' => 0,
            ],
          ],
          'transitions' => [
            'archive' => [
              'label' => 'Archive',
              'from' => ['published'],
              'to' => 'archived',
              'weight' => 2,
            ],
            'archived_draft' => [
              'label' => 'Restore to Draft',
              'from' => ['archived'],
              'to' => 'draft',
              'weight' => 3,
            ],
            'archived_published' => [
              'label' => 'Restore',
              'from' => ['archived'],
              'to' => 'published',
              'weight' => 4,
            ],
            'create_new_draft' => [
              'label' => 'Create New Draft',
              'to' => 'draft',
              'weight' => 0,
              'from' => [
                'draft',
                'published',
              ],
            ],
            'publish' => [
              'label' => 'Publish',
              'to' => 'published',
              'weight' => 1,
              'from' => [
                'draft',
                'published',
              ],
            ],
          ],
        ],
      ]);
    }

    $this->workflow->getTypePlugin()->addEntityTypeAndBundle('node', $bundle);
    $this->workflow->save();
  }

}
