<?php

namespace Drupal\tmgmt_contentapi\Util;

use Drupal\file\FileInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\Component\Utility\Xss;
use Drupal\file\Entity\File;
use Drupal\tmgmt\TranslatorInterface;

/**
 * CreateToken Class Doc Comment.
 *
 * @category Class
 * @package Drupal\tmgmt_contentapi\Util
 * @author Arben Sabani
 */
class GeneralHelper {

  /**
   * Get label of the job.
   *
   * @param Drupal\tmgmt\JobInterface $job
   *   Job.
   *
   * @return string
   *   stingyfied label.
   */
  public static function getJobLabel(JobInterface $job) {
    return isset($job->get("label")->value) ? $job->get("label")->value : $job->label()->getArguments()["@title"];
  }

  /**
   * Remove spec. characters from label.
   *
   * @param Drupal\tmgmt\JobInterface $job
   *   Job.
   *
   * @return string
   *   stingyfied label.
   */
  public static function getJobLabelNoSpeChars(JobInterface $job) {
    $toreturn = GeneralHelper::getJobLabel($job);
    return GeneralHelper::getStringNoSpeChars($toreturn);
  }

  /**
   * Remove spec. characters from label.
   *
   * @param string $arg
   *   Job.
   *
   * @return string
   *   removed spec. chars string.
   */
  public static function getStringNoSpeChars($arg) {
    $toreturn = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $arg);
    // Remove any runs of periods (thanks falstro!)
    $toreturn = mb_ereg_replace("([\.]{2,})", '', $toreturn);
    $toreturn = Xss::filter($toreturn);
    return $toreturn;
  }
  /**
   * Create file object from uri.
   *
   * @param string $uri
   *   Uri.
   *
   * @return \stdClass
   *   file class object.
   */
  public static function createFileObject($uri) {
    $filsystem = \Drupal::service('file_system');
    $file = File::create([
      'uid' => \Drupal::currentUser()->id(),
      'filename' => $filsystem->basename($uri),
      'uri' => $uri,
      'filemime' => \Drupal::service('file.mime_type.guesser')->guess($uri),
      'filesize' => filesize($uri),
      'status' => 1
    ]);

    $file->save();
    return $file;
  }

  /**
   * Create file object from uri.
   *
   * @param Drupal\tmgmt\JobInterface $job
   *   Job.
   * @param string $cpsettings
   *   Job.
   */
  public static function addCpaSettingsToJob(JobInterface $job, $cpsettings) {
    $sett_job = $job->__get("settings");
    $vals = $sett_job->getValue();
    $vals[0]["capi-remote"] = $cpsettings;
    $sett_job->setValue($vals);
    $job->__set("settings", $sett_job->getValue());
  }

  /**
   * Get all jobs for connector config.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   TranslatorInterface.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   A storage instance.
   */
  public static function getAllJobsByTranslator(TranslatorInterface $translator){
    $trname = $translator->id();
    $trjobs = \Drupal::entityManager()->getStorage('tmgmt_job')->loadByProperties(['translator' => $trname]);
    return $trjobs;
  }

  /**
   * Get all jobs for connector config.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   TranslatorInterface.
   *
   * @return string
   *   Id of contentapi Job.
   */
  public static function getCpJobIdfromLocJob(JobInterface $job){
    $jobcpsettings = unserialize($job->getSetting('capi-remote'));
    $task = $job->getSetting('capi-settings')['task'];
    $firstrequst = $task == 'trans' ? array_values($jobcpsettings)[0][0]:array_values($jobcpsettings)[0];
    if (!isset($firstrequst)){
      throw new \Exception("Job Id could not be found in local job!");
    }
    else{
      return $firstrequst->getJobId();
    }
  }

  /**
   * Reset Job and Items to state Active.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   TranslatorInterface.
   * @param \Drupal\file\FileInterface $file
   *   TranslatorInterface.
   */
  public static function resetJobandItemsToActive(JobInterface &$job, FileInterface $file){
    $itemsToset = $job->getItems();
    $loadedxml = simplexml_load_file(drupal_realpath($file->getFileUri()));
    $loadedxml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');
    foreach ($itemsToset as $item) {
      // If the xlf caontains one of job's items, set state to active. [@phase-name='extraction']
      $tjiid = $item->id();
      $groups = $loadedxml->xpath("//xliff:group[@id='" . $tjiid ."']");
      if(count($groups) == 1){
        $item->setState(Job::STATE_ACTIVE);
        $job->setState(\Drupal\tmgmt\Entity\JobItem::STATE_ACTIVE);
      }
    }
  }

}
