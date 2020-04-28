<?php

namespace Drupal\Tests\eck\Functional;

use Drupal\Core\Url;

/**
 * Tests if eck entities are correctly created and updated.
 *
 * @group eck
 */
class EntityCRUDTest extends FunctionalTestBase {

  /**
   * @test
   */
  public function newEntitiesCanBeCreated() {
    $entityTypeInfo = $this->createEntityType(['title'], 'TestType');
    $bundleInfo = $this->createEntityBundle($entityTypeInfo['id'], 'TestBundle');

    $params = [
      'eck_entity_type' => $entityTypeInfo['id'],
      'eck_entity_bundle' => $bundleInfo['type'],
    ];
    $url = Url::fromRoute('eck.entity.add', $params);
    $values = ['title[0][value]' => 'testEntity'];
    $this->drupalPostForm($url, $values, 'Save');

    $currentUrl = $this->getSession()->getCurrentUrl();
    $this->assertRegExp('@/testtype/\d$@', $currentUrl);
  }

  /**
   * @test
   */
  public function attemptedCreationOfNonExistingEntityTypeResultsIn404() {
    $params = [
      'eck_entity_type' => 'non-existing',
      'eck_entity_bundle' => 'non-existing',
    ];
    $url = Url::fromRoute('eck.entity.add', $params);

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * @test
   */
  public function attemptedCreationOfNonExistingBundleResultsIn404() {
    $this->createEntityType([], 'TestType');
    $params = [
      'eck_entity_type' => 'testtype',
      'eck_entity_bundle' => 'non-existing',
    ];
    $url = Url::fromRoute('eck.entity.add', $params);

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(404);
  }

}
