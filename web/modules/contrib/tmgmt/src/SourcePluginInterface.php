<?php

namespace Drupal\tmgmt;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for source plugin controllers.
 *
 * @ingroup tmgmt_source
 */
interface SourcePluginInterface extends PluginInspectionInterface {

  /**
   * Returns an array with the data structured for translation.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item entity.
   *
   * @see JobItem::getData()
   */
  public function getData(JobItemInterface $job_item);

  /**
   * Saves a translation.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item entity.
   * @param string $target_langcode
   *   The target language code.
   *
   * @return bool
   *   TRUE if the translation was saved successfully, FALSE otherwise.
   */
  public function saveTranslation(JobItemInterface $job_item, $target_langcode);

  /**
   * Return a title for this job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item entity.
   */
  public function getLabel(JobItemInterface $job_item);

  /**
   * Returns the Uri for this job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item entity.
   *
   * @return \Drupal\Core\Url|null
   *   The URL object for the source object.
   */
  public function getUrl(JobItemInterface $job_item);

  /**
   * Returns an array of translatable source item types.
   */
  public function getItemTypes();

  /**
   * Returns the label of a source item type.
   *
   * @param $type
   *   The identifier of a source item type.
   */
  public function getItemTypeLabel($type);

  /**
   * Returns the type of a job item.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   *
   * @return string
   *   A type that describes the job item.
   */
  public function getType(JobItemInterface $job_item);

  /**
   * Gets language code of the job item source.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   *
   * @return string
   *   Language code.
   */
  public function getSourceLangCode(JobItemInterface $job_item);

  /**
   * Gets existing translation language codes of the job item source.
   *
   * Returns language codes that can be used as the source language for a
   * translation job.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   *
   * @return array
   *   Array of language codes.
   */
  public function getExistingLangCodes(JobItemInterface $job_item);

}
