<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Kernel;

use Drupal\node\Entity\Node;

/**
 * Tests the Scheduler hook functions implemented by this module.
 *
 * @group scheduler_content_moderation_integration
 */
class HookImplementationTest extends SchedulerContentModerationTestBase {

  /**
   * A node of a type which is enabled for moderation.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $moderatedNode;

  /**
   * A node of a type which is not enabled for moderation.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nonModeratedNode;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test 'Example' node which will be moderated.
    $this->moderatedNode = Node::create([
      'type' => 'example',
      'title' => 'Example content is moderated',
    ]);

    // Create a test 'Other' node which will not be moderated.
    $this->nonModeratedNode = Node::create([
      'type' => 'other',
      'title' => 'Other is not moderated',
    ]);
  }

  /**
   * Tests if the Scheduler Publish-on and Unpublish-on fields should be hidden.
   *
   * @dataProvider hideSchedulerFieldsProvider
   */
  public function testHookHideSchedulerFields($expected, $nodeChoice, $options) {
    $node = $this->$nodeChoice;
    $form = [];
    $form['publish_state']['widget'][0]['#options'] = $options;
    $form['unpublish_state']['widget'][0]['#options'] = $options;

    $result = scheduler_content_moderation_integration_scheduler_hide_publish_on_field($form, [], $node);
    $this->assertEquals($expected, $result, sprintf('Hide the publish-on field: Expected %s, Result %s', $expected ? 'Yes' : 'No', $result ? 'Yes' : 'No'));

    $result = scheduler_content_moderation_integration_scheduler_hide_unpublish_on_field($form, [], $node);
    $this->assertEquals($expected, $result, sprintf('Hide the unpublish-on field: Expected %s, Result %s', $expected ? 'Yes' : 'No', $result ? 'Yes' : 'No'));
  }

  /**
   * Data provider for self:testHookHideSchedulerFields().
   */
  public function hideSchedulerFieldsProvider() {
    return [
      // Two states in addition to _none. Should not hide the fields.
      [FALSE, 'moderatedNode', ['_none' => 'None', 'Some state', 'Not hidden']],

      // Just one state in addition to _none. Should not hide the fields.
      [FALSE, 'moderatedNode', ['_none' => 'Nothing', 'Will not be hidden']],

      // Just one state, does not include _none. Should not hide the fields.
      [FALSE, 'moderatedNode', ['The only state']],

      // The only state is 'None'. This should cause the fields to be hidden.
      [TRUE, 'moderatedNode', ['_none' => 'Nothing']],

      // No states at all. This should cause the fields to be hidden.
      [TRUE, 'moderatedNode', []],

      // Content type is not moderated. Should not hide the fields.
      [FALSE, 'nonModeratedNode', ['_none' => 'Nothing']],
    ];
  }

}
