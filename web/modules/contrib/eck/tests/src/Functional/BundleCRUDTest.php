<?php

namespace Drupal\Tests\eck\Functional;

/**
 * Tests eck's bundle creation, update and deletion.
 *
 * @group eck
 */
class BundleCRUDTest extends FunctionalTestBase {

  /**
   * Tests single bundle creation.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function singleBundleCreation() {
    $entityTypeInfo = $this->createEntityType([], 'TestType');
    $this->createEntityBundle($entityTypeInfo['id'], 'TestBundle');
  }

  /**
   * Tests multiple bundle creation.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function multipleBundleCreation() {
    $entityTypeInfo = $this->createEntityType([], 'TestType');
    $this->createEntityBundle($entityTypeInfo['id'], 'TestBundle1');
    $this->createEntityBundle($entityTypeInfo['id'], 'TestBundle2');
  }

  /**
   * Tests identically named bundle creation.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function identicallyNamedBundleCreation() {
    $entityTypeInfo1 = $this->createEntityType([], 'TestType1');
    $entityTypeInfo2 = $this->createEntityType([], 'TestType2');

    $this->createEntityBundle($entityTypeInfo1['id'], 'TheBundle');
    $this->createEntityBundle($entityTypeInfo2['id'], 'TheBundle');
  }

}
