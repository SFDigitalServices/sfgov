<?php

namespace Drupal\Tests\sfgov_media\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests for Power BI embed formatter.
 *
 * @group sfgov_media
 */
class PowerBiEmbedFormatterTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sfgov_media',
    'media',
    'link',
  ];

  /**
   * {@inheritDoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->config('media.settings')->set('standalone_url', TRUE)->save();
    $this->refreshVariables();

    \Drupal::service('router.builder')->rebuild();

    $account = $this->drupalCreateUser([
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
    ]);

    $this->drupalLogin($account);
  }

  /**
   * Tests adding and editing a power_bi embed formatter.
   */
  public function testPowerBiEmbedFormatter() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface */
    $entity_display_repository = \Drupal::service('entity_display.repository');

    $media_type = $this->createMediaType('power_bi', ['id' => 'power_bi']);

    $source_field = $media_type->getSource()->getSourceFieldDefinition($media_type);
    $this->assertSame('field_media_power_bi', $source_field->getName());
    $this->assertSame('string', $source_field->getType());

    // Set form and view displays.
    $entity_display_repository->getFormDisplay('media', $media_type->id(), 'default')
      ->setComponent('field_media_power_bi', [
        'type' => 'string_textfield',
      ])
      ->save();

    $entity_display_repository->getViewDisplay('media', $media_type->id(), 'full')
      ->setComponent('field_media_power_bi', [
        'type' => 'power_bi',
      ])
      ->save();

    $this->drupalGet('media/add/' . $media_type->id());
    $page = $this->getSession()->getPage();
    $page->fillField('name[0][value]', 'Power BI');
    $page->fillField('field_media_power_bi[0][value]', 'https://app.powerbigov.us/view?r=eyJrIjoiYWVlMDcyZjgtNGYzYS00NGE5LWE3ZmItMmI5M2E2NWM3MGEzIiwidCI6IjIyZDVjMmNmLWNlM2UtNDQzZC05YTdmLWRmY2MwMjMxZjczZiJ9&pageName=ReportSectionb342c47c7c4068d220e');
    $page->pressButton('Save');
    $assert = $this->assertSession();
    $assert->pageTextContains('has been created');
    $medias = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
      'name' => 'Power BI',
    ]);

    /** @var \Drupal\media\MediaInterface */
    $media = reset($medias);
    $this->drupalGet(Url::fromRoute('entity.media.canonical', ['media' => $media->id()])->toString());
    $assert->statusCodeEquals(200);

    // Assert that the formatter exists on this page.
    $assert->elementExists('css', 'iframe[src*="powerbigov"]');
  }

}
