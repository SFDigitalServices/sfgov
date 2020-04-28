<?php

namespace Drupal\tmgmt_xtm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt_xtm\Plugin\tmgmt\Translator\Connector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RemoteCallbackController
 * @package Drupal\tmgmt_xtm\Controller
 */
class RemoteCallbackController extends ControllerBase
{
    const TMGMT_JOB = 'tmgmt_job';
    const TMGMT_JOB_ID = 'xtmJobId';
    const XTM_PROJECT_ID = 'xtmProjectId';


    /**
     * @param Request $request
     * @return Response
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     */
    public function callback(Request $request)
    {
        \Drupal::logger('xtm')->debug('Request received %request.', ['%request' => $request]);
        $tmgmtJobIDs = explode(",", htmlspecialchars_decode($_REQUEST[Connector::TMGMT_JOB_ID]));
        foreach ($tmgmtJobIDs as $tmgmtJobId) {
            $xtmProjectId = (int)$_REQUEST[self::XTM_PROJECT_ID];
            if (empty($tmgmtJobId) || empty($xtmProjectId)) {
                return new Response('Bad request.', 400);
            }
            /** @var Job $job */
            $job = \Drupal::entityTypeManager()->getStorage(self::TMGMT_JOB)->load($tmgmtJobId);

            $jobArray = $job->reference->getValue();
            if (!$job || (int)$jobArray[0]['value'] != $xtmProjectId) {
                return new Response('Bad request.', 400);
            }
            $connector = new Connector();
            $connector->retrieveTranslation($job);
        }

        return new Response();
    }
}
