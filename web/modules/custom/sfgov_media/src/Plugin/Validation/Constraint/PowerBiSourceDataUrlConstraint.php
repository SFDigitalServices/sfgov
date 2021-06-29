<?php

namespace Drupal\sfgov_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates a Power BI Source Data URL
 *
 * @Constraint(
 *   id = "PowerBiSourceDataUrl",
 *   label = @Translation("Power BI Source Data URL", context = "Validation"),
 * )
 */
class PowerBiSourceDataUrlConstraint extends Constraint {

  /**
   * {@inheritdoc}
   */
  public $message = 'The source data URL is invalid. Format: https://example.com.';

}
