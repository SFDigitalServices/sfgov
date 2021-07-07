<?php

namespace Drupal\sfgov_media\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates a Power BI URL>
 *
 * @Constraint(
 *   id = "PowerBiUrl",
 *   label = @Translation("Power BI URL", context = "Validation"),
 * )
 */
class PowerBiUrlConstraint extends Constraint {

  /**
   * {@inheritdoc}
   */
  public $incorrectDurationFormat = 'The Power BI embed URL is invalid. The URL must start with "https://app.powerbigov.us/view".';

}
