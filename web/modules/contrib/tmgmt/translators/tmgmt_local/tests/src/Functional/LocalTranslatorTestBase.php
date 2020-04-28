<?php

namespace Drupal\Tests\tmgmt_local\Functional;

use Drupal\Tests\tmgmt\Functional\TmgmtEntityTestTrait;
use Drupal\Tests\tmgmt\Functional\TMGMTTestBase;

/**
 * Base class for local translator tests.
 */
abstract class LocalTranslatorTestBase extends TMGMTTestBase {
  use TmgmtEntityTestTrait;

  /**
   * Translator user.
   *
   * @var object
   */
  protected $assignee;

  protected $localTranslatorPermissions = array(
    'provide translation services',
  );

  protected $localManagerPermissions = [
    'administer translation tasks',
    'provide translation services',
    'view the administration theme',
    'administer themes',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'tmgmt',
    'tmgmt_language_combination',
    'tmgmt_local',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->loginAsAdmin();
    $this->addLanguage('de');
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Asserts task status icon.
   *
   * @param int $row
   *   The row of the item you want to check.
   * @param string $view
   *   The view where we want to assert.
   * @param string $overview
   *   The overview table to check.
   * @param int $state
   *   The expected state.
   */
  protected function assertTaskStatusIcon($row, $view, $overview, $state) {
    $result = $this->xpath('//*[@id="views-form-tmgmt-local-' . $view . '-' . $overview . '"]/table/tbody/tr[' . $row . ']/td[2]/img')[0];
    $this->assertEquals($state, $result->getAttribute('title'));
  }

  /**
   * Asserts task item status icon.
   *
   * @param string $row
   *   Identifier for the row.
   * @param int $state
   *   The expected state.
   */
  protected function assertTaskItemStatusIcon($row, $state) {
    $result = $this->xpath('//*[@id="edit-items"]/div/div/table/tbody/tr[td//text()[contains(., :row)]]/td[1]/img', [':row' => $row])[0];
    $this->assertEquals($state, $result->getAttribute('title'));
  }

  /**
   * Asserts the task progress bar.
   *
   * @param int $row
   *   The row of the item you want to check.
   * @param string $overview
   *   The overview to be checked.
   * @param int $untranslated
   *   The amount of untranslated items.
   * @param int $translated
   *   The amount of translated items.
   * @param int $completed
   *   The amount of completed items.
   */
  protected function assertTaskProgress($row, $overview, $untranslated, $translated, $completed) {
    $result = $this->xpath('//*[@id="views-form-tmgmt-local-task-overview-' . $overview . '"]/table/tbody/tr[' . $row . ']/td[3]')[0];
    $div_number = 1;
    if ($untranslated > 0) {
      $this->assertEquals('tmgmt-local-progress-untranslated', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
      $div_number++;
    }
    else {
      $this->assertNotEquals('tmgmt-local-progress-untranslated', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
    }
    if ($translated > 0) {
      $this->assertEquals('tmgmt-local-progress-translated', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
      $div_number++;
    }
    else {
      $child = $result->find('css', "div > div:nth-child($div_number)");
      $this->assertTrue(!$child || $child->getAttribute('class'), 'tmgmt-local-progress-translated');
    }
    if ($completed > 0) {
      $this->assertEquals('tmgmt-local-progress-completed', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
    }
    else {
      $child = $result->find('css', "div > div:nth-child($div_number)");
      $this->assertTrue(!$child || $child->getAttribute('class'), 'tmgmt-local-progress-completed');
    }
    $title = t('Untranslated: @untranslated, translated: @translated, completed: @completed.', array(
      '@untranslated' => $untranslated,
      '@translated' => $translated,
      '@completed' => $completed,
    ));
    $this->assertEquals($title, $result->find('css', 'div')->getAttribute('title'));
  }

  /**
   * Asserts the task item progress bar.
   *
   * @param string $row
   *   Identifier for the row.
   * @param int $untranslated
   *   The amount of untranslated items.
   * @param int $translated
   *   The amount of translated items.
   * @param int $completed
   *   The amount of completed items.
   */
  protected function assertTaskItemProgress($row, $untranslated, $translated, $completed) {
    $result = $this->xpath('//*[@id="edit-items"]/div/div/table/tbody/tr[td//text()[contains(., :row)]]/td[2]', [':row' => $row])[0];
    $div_number = 1;
    if ($untranslated > 0) {
      $this->assertEquals('tmgmt-local-progress-untranslated', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
      $div_number++;
    }
    else {
      $this->assertNotEquals('tmgmt-local-progress-untranslated', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
    }
    if ($translated > 0) {
      $this->assertEquals('tmgmt-local-progress-translated', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
      $div_number++;
    }
    else {
      $child = $result->find('css', "div > div:nth-child($div_number)");
      $this->assertTrue(!$child || $child->getAttribute('class'), 'tmgmt-local-progress-translated');
    }
    if ($completed > 0) {
      $this->assertEquals('tmgmt-local-progress-completed', $result->find('css', "div > div:nth-child($div_number)")->getAttribute('class'));
    }
    else {
      $child = $result->find('css', "div > div:nth-child($div_number)");
      $this->assertTrue(!$child || $child->getAttribute('class'), 'tmgmt-local-progress-completed');
    }
    $title = t('Untranslated: @untranslated, translated: @translated, completed: @completed.', array(
      '@untranslated' => $untranslated,
      '@translated' => $translated,
      '@completed' => $completed,
    ));
    $this->assertEquals($title, $result->find('css', 'div')->getAttribute('title'));
  }

}
