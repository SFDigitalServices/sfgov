<?php

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;
use GuzzleHttp;

/**
 * Class Connector
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator *
 */
class MultipleTargetLanguageConnector extends Connector
{
    /**
     * Method handling multiple target languages
     *
     * @param $jobs
     */
    public function xtmRequestTranslation($jobs)
    {
        $ids = [];
        /** @var Job $job */
        foreach ($jobs as $job) {
            $ids[] = $job->id();
        }

        $translator = $job->getTranslator();
        $projectMTOM = $this->createOneProjectMTOM($jobs, $translator);
        $callbackUrl = Url::fromRoute(
            'tmgmt_xtm.callback',
            [self::TMGMT_JOB_ID => implode(',', $ids)],
            ['absolute' => true]
        )->toString();
        $projectMTOM['projectCallback'][$job->getSetting(self::API_PROJECT_MODE) == 2
            ? 'projectFinishedCallback' : 'jobFinishedCallback'] = $callbackUrl;

        if ($job->getSetting(self::API_TEMPLATE_ID)) {
            $projectMTOM['template'] = ['id' => $job->getSetting(self::API_TEMPLATE_ID)];
        }
        $input = [
            'project' => $projectMTOM,
            'options' => ['autopopulate' => true]
        ];
        try {
            $response = $this->doRequest(
                $translator,
                self::CREATE_PROJECT_FOR_PMMTOM,
                $input
            );

            if (isset($response->project)) {
                $job = $this->updatedJobs($jobs, $response);
            }
        } catch (\SoapFault $fault) {
            \Drupal::logger(self::XTM_LOGGER)->notice(GuzzleHttp\json_encode($fault));
            $job->rejected(
                'Job has been rejected with following error: @error',
                ['@error' => $fault->faultstring],
                'error'
            );
        } catch (TMGMTXtmException $e) {
            watchdog_exception(self::XTM_LOGGER, $e, $e->getMessage());
            $job->rejected('Job has been rejected with following error: @error', ['@error' => $e->getMessage()],
                'error');
        }
    }


    /**
     * Only one project is beeing created
     *
     * @param $jobs
     * @param Translator $translator
     * @return array
     */
    private function createOneProjectMTOM($jobs, Translator $translator)
    {
        $helper = new MultipleTargetLanguageHelper();

        /** @var Job $job */
        $targetLanguages = $jobsIDs = [];
        foreach ($jobs as $job) {
            $targetLanguages[] = $helper->mapLanguageToXTMFormat($job->getRemoteTargetLanguage(), $translator);
            $jobsIDs[] = $job->id();
        }

        $prefix = $translator->getSetting(self::PROJECT_NAME_PREFIX);
        $projectMTOM = [
            'name'             => ($prefix ? "[$prefix] " : '') . $helper->clearLabel($job->label()),
            'sourceLanguage'   => $helper->mapLanguageToXTMFormat($job->getRemoteSourceLanguage(), $translator),
            'targetLanguages'  => $targetLanguages,
            'translationFiles' => ($job->getSetting(self::API_PROJECT_MODE) == 0)
                ? $helper->createSingleXMLFile($jobs) : $helper->createMultipleXMLFiles($jobs),
            'referenceId'      => implode(",", $jobsIDs),
            'customer'         =>
                ['id' => $translator->getSetting(self::XTM_PROJECT_CUSTOMER_ID)],
        ];

        return $projectMTOM;
    }

    /**
     * @param $jobs
     * @param $response
     * @return mixed
     */
    private function updatedJobs($jobs, $response)
    {
        /** @var Job $job */
        foreach ($jobs as $job) {
            $projectId = $response->project->projectDescriptor->id;
            $job->reference = $projectId;
            $job->save();
            $job->submitted(
                'The project has been successfully submitted
                     for translation. Project ID: @project_id.',
                ['@project_id' => $projectId]
            );
        }
        return $job;
    }
}
