<?php

namespace Drupal\Tests\mimemail\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Functionality tests for the Mime Mail Compress module.
 *
 * @group mimemail
 */
abstract class MimeMailCompressTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'mailsystem',
    'mimemail',
  ];

}
