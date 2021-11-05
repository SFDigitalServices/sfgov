<?php

namespace Drupal\fancy_file_delete\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Unmanaged Files entities.
 */
class UnmanagedFilesViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['unmanaged_files']['unmanaged_directories'] = array(
      'title' => t('Directory Choice'),
      'help' => t('Filter by Directory.'),
      'filter' => array(
        'id' => 'ffd_unmanaged_directory_filter',
      ),
    );

    return $data;
  }

}
