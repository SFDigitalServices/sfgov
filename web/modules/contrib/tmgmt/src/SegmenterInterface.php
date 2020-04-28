<?php

namespace Drupal\tmgmt;
use Drupal\Core\Form\FormStateInterface;

/**
 * This interface offers a suite of methods to add segments and filter data.
 *
 * @internal
 */
interface SegmenterInterface {

  /**
   * Remove the "tmgmt-segment" tags from the data.
   *
   * This function will return the same data removing the "tmgmt-segment"
   * XML Element.
   *
   * @param string $segmented_text
   *   A string with "tmgmt-segment" tags.
   *
   * @return string
   *   A string without the "tmgmt-segment" tags.
   */
  public function filterData($segmented_text);

  /**
   * Returns the segments in the data.
   *
   * @param string $serialized_data
   *   A string with the XML serialized data.
   *
   * @return array
   *   An array with the the segments.
   *   Example:
   *   [
   *     [
   *       'hash' => 'e1a937a716311fd11c2079b6209d513a4048cef9fb5a0425c2be77ee3b1fa743',
   *       'id' => 'ID 1',
   *       'data' => 'Segment 1',
   *     ],
   *     [
   *       'hash' => 'c8e90d4ed846ff50bdf5603b1f683e71a56c923ea65306a2f2f95300d16d79e9',
   *       'id' => 'ID 2',
   *       'data' => 'Segment 2',
   *     ],
   *   ]
   */
  public function getSegmentsOfData($serialized_data);

  /**
   * Add the segmented data tag to the data or update it.
   *
   * Will just segment an item if it has the key '#translate' set to TRUE.
   *
   * @param array $data
   *   The data array of a JobItem.
   *   Example:
   *   [
   *     '#text' => 'Unsegmented source text',
   *     '#translate' => TRUE,
   *   ]
   *
   * @return array
   *   An array with the the segments.
   *   Example:
   *   [
   *     '#text' => 'Unsegmented source text',
   *     '#translate' => TRUE,
   *     '#segmented_text' => '<tmgmt-segment>Segmented source text</tmgmt-segment>'
   *   ]
   */
  public function getSegmentedData($data);

  /**
   * Validate the segments from the JobItemForm.
   *
   * If the form does not validate, it will add the error to the $element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A string with the XML serialized data.
   * @param array $element
   *   The translation element of the form.
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The Job Item.
   */
  public function validateFormTranslation(FormStateInterface &$form_state, $element, JobItemInterface $job_item);

}
