<?php

namespace Drupal\sfgov_departments;

use Drupal\node\NodeInterface;

/**
 * Class SFgovDepartment.
 */
class SFgovDepartment {

  /**
   * @var \Drupal\node\NodeInterface Department node reference.
   */
  protected $department_node;

  public function __construct(NodeInterface $department_node) {
    $this->department_node = $department_node;
  }

  /**
   * Get the department field name given the node bundle.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return string|null
   *   The field name.
   */
  public static function getDepartmentFieldName(string $bundle): ?string {
    switch ($bundle) {
      case 'transaction':
      case 'topic':
      case 'public_body':
      case 'location':
        return 'field_departments';
        break;

      case 'news':
      case 'event':
      case 'information_page':
      case 'meeting':
      case 'campaign':
      case 'department_table':
      case 'form_confirmation_page':
      case 'page':
      case 'person':
      case 'resource_collection':
      case 'step_by_step':
        return 'field_dept';

        break;
    }

    return NULL;
  }

}
