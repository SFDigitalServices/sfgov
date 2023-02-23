<?php

namespace Drupal\sfgov_change_content_type\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\migrate\MigrateSkipProcessException;

/**
 * Only allows certain paragraph types to be mapped
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: paragraph_type_filter
 *     allowed_types:
 *       - foo
 *       - bar
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "paragraph_type_filter"
 * )
 */
class ParagraphTypeFilter extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $paragraph = Paragraph::load($value['target_id']);
    if (in_array($paragraph->bundle(), $this->configuration['allowed_types'])) {
      return $value;
    }
    else {
      throw new MigrateSkipProcessException();
    }
  }

}
