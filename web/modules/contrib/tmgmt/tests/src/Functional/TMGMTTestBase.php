<?php

namespace Drupal\Tests\tmgmt\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\tmgmt\Entity\Translator;

/**
 * Base class for tests.
 */
abstract class TMGMTTestBase extends BrowserTestBase {

  use TmgmtTestTrait;

  /**
   * A default translator using the test translator.
   *
   * @var Translator
   */
  protected $default_translator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'tmgmt',
    'tmgmt_test',
    'node',
    'block',
    'locale',
  );

  /**
   * Overrides DrupalWebTestCase::setUp()
   */
  function setUp() {
    parent::setUp();
    $this->default_translator = Translator::load('test_translator');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

}
