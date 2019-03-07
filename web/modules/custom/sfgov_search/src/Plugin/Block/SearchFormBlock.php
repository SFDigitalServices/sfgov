<?php

namespace Drupal\sfgov_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;


/**
 * Provides a block for sfgov search form
 * 
 * @Block(
 *   id = "sfgov_search_form_block",
 *   admin_label = @Translation("SF Gov Search Block"),
 *   category = @Translation("SF Gov Blocks"),
 * )
 */
class SearchFormBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\sfgov_search\Form\SearchForm');
    return ['form' => $form];
  }
}