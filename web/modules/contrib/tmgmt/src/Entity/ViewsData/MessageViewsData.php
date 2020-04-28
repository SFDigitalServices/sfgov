<?php

namespace Drupal\tmgmt\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the message entity type.
 */
class MessageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['tmgmt_message']['message']['field']['id'] = 'tmgmt_message';
    return $data;
  }

}
