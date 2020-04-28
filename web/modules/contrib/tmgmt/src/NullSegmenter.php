<?php

namespace Drupal\tmgmt;

use Drupal\Core\Form\FormStateInterface;

/**
 * Null implementation for the SegmenterInterface.
 */
class NullSegmenter implements SegmenterInterface {

  /**
   * {@inheritdoc}
   */
  public function filterData($segmented_text) {
    return $segmented_text;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentsOfData($serialized_data) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentedData($data) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormTranslation(FormStateInterface &$form_state, $element, JobItemInterface $job_item) {}

}
