<?php

namespace Drupal\simple_instagram_feed\Services;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements Simple Instagram Feed Library service.
 */
class SimpleInstagramFeedLibrary implements SimpleInstagramFeedLibraryInterface {
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The drupal root path.
   *
   * @var string
   */
  private $root;

  /**
   * {@inheritdoc}
   */
  public function isAvailable(bool $warning = FALSE) {
    if (!file_exists($this->root . $this->getPath())) {
      if ($warning) {
        $this->messenger()->addWarning($this->getWarningMessage());
      }

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWarningMessage() {
    return $this->t('Missing library: The <a href=":url">jquery.instagramFeed</a> library should be installed at <strong>:path</strong>.', [
      ':url'  => 'https://github.com/jsanahuja/jquery.instagramFeed',
      ':path' => $this->getPath(),
    ]);
  }

  /**
   * Returns the expected js library path.
   */
  private function getPath() {
    return '/libraries/jqueryinstagramfeed/jquery.instagramFeed.min.js';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function create(ContainerInterface $container) {
    return new static($container->get('app.root'));
  }

}
