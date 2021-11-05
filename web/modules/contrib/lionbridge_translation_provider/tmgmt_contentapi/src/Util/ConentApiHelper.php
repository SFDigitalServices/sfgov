<?php

namespace Drupal\tmgmt_contentapi\Util;

use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;

use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt_contentapi\Swagger\Client\Model\CreateJob;
use Drupal\tmgmt_contentapi\Swagger\Client\Model\ArrayOfRequestIds;

use Drupal\tmgmt_contentapi\Swagger\Client\Model\CreateToken;
use Drupal\tmgmt_contentapi\Swagger\Client\Model\Request;

use Drupal\tmgmt_contentapi\Swagger\Client\Api\TokenApi;
use Drupal\tmgmt_contentapi\Swagger\Client\Api\JobApi;
use Drupal\tmgmt_contentapi\Swagger\Client\Api\RequestApi;


/**
 * CreateToken Class Doc Comment.
 *
 * @category Class
 * @package Drupal\tmgmt_contentapi\Util
 * @author Arben Sabani
 */
class ConentApiHelper {

  /**
   * Create content api objects from Job and translator.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   Job.
   *
   * @return \Drupal\tmgmt_contentapi\Swagger\Client\Model\CreateJob
   *   Job.
   */
  public static function genrateJobRequst(JobInterface $job) {
    $shouldquote = (boolean) ($job->getSetting('capi-settings')['quote']['is_quote']);
    $capijobsettings = $job->getSetting("capi-settings");
    $jobarray = array(
      'job_name' => GeneralHelper::getJobLabelNoSpeChars($job),
      'description' => isset($capijobsettings["description"]) ? $capijobsettings["description"] : NULL,
      'po_reference' => isset($capijobsettings["po_reference"]) ? $capijobsettings["po_reference"] : NULL,
      'due_date' => isset($capijobsettings["due_date"]) ? $capijobsettings["due_date"] : NULL,
      'should_quote'=>$shouldquote
    );
    // TODO: Check with Dev why erro with costom dagta set.
    if (isset($capijobsettings["custom_data"]) && $capijobsettings["custom_data"] !== "") {
      $job['custom_data'] = $capijobsettings["custom_data"];
    }
    $jobrequest = new CreateJob($jobarray);
    return $jobrequest;
  }

  /**
   * Create content api token from translator.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   Job.
   *
   * @return string
   *   Token.
   */
  public static function generateToken(TranslatorInterface $translator) {
    return $translator->getSetting('capi-settings')['token'];
  }

  public static function checkJobFinishAndApproveRemote(JobInterface $job){
    $job = Job::load($job->id());
    $translator = $job->getTranslator();
    $allrequests = unserialize($job->getSetting('capi-remote'));
    if(isset($allrequests) && count($allrequests) == 1){
      $test = new Request();
      $arraywithrequests = $allrequests[0];
      if($job->getState() == JobInterface::STATE_FINISHED){
        try {
          $arrywithrequestIds = [];
          foreach ($arraywithrequests as $req) {
            $arrywithrequestIds[] = $req->getRequestId();
          }
          $token = ConentApiHelper::generateToken($translator);
          $jobapi = new JobApi();
          $requestapi = new RequestApi();
          $arrayruquest = new ArrayOfRequestIds();
          $arrayruquest->setRequestIds($arrywithrequestIds);
          $requestapi->jobsJobIdRequestsApprovePut($token, reset($arraywithrequests)->getJobId(), $arrayruquest);
          $job->addMessage(t('Remote job archived.'));
          return $jobapi->jobsJobIdArchivePut($token, reset($arraywithrequests)->getJobId());
        }
        catch (\Exception $exception){
          $job->addMessage(t('Could not approve remote requests: ' . $exception->getMessage()));
          return NULL;
        }
      }
    }
    return NULL;
  }

}