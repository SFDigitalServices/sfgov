<?php

namespace Drupal\sfgov_formio\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a FormioDataSource constraint.
 *
 * @Constraint(
 *   id = "FormioDataSource",
 *   label = @Translation("FormioDataSource", context = "Validation"),
 * )
 */
class FormioDataSourceConstraint extends Constraint {

  /**
   * The violation message.
   *
   * @var string
   */
  public $invalidUrl = 'The url provided in field_formio_data_source not valid.';

  /**
   * The violation message.
   *
   * @var string
   */
  public $invalidJson = 'The url provided in field_formio_data_source is not providing valid formio data for translations. Error: %error';

}
