<?php

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\Core\Url;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\TMGMTException;
use GuzzleHttp;
use Masterminds\HTML5\Exception;

/**
 * Class Connector
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator *
 */
class Connector
{
    const INTEGRATION_KEY = '163209fcd9394e34b625f371f66a0cb7';
    const XTM_ACTION_ARCHIVE = 'ARCHIVE';

    const CREATE_PROJECT_FOR_PMMTOM = 'createProjectForPMMTOM';
    const UPDATE_PROJECT_ACTIVITY = 'updateProjectActivity';
    const CHECK_PROJECT_COMPLETION = 'checkProjectCompletion';
    const DOWNLOAD_PROJECT_MTOM = 'downloadProjectMTOM';
    const FIND_CUSTOMER = 'findCustomer';
    const FIND_TEMPLATE = 'findTemplate';
    const GET_XTM_INFO = 'getXTMInfo';

    const XTM_TEMPLATE_SCOPE_ALL = "ALL";
    const XTM_PROJECT_CUSTOMER_ID = 'xtm_project_customer_id';
    const PROJECT_NAME_PREFIX = 'project_name_prefix';
    const XTM_API_URL = 'xtm_api_url';
    const API_TEMPLATE_ID = 'api_template_id';
    const XTM_API_USER_ID = 'xtm_api_user_id';
    const XTM_API_PASSWORD = 'xtm_api_password';
    const XTM_API_CLIENT_NAME = 'xtm_api_client_name';
    const API_PROJECT_MODE = 'api_project_mode';

    const TMGMT_JOB_ID = 'tmgmtJobId';
    const XTM_LOGGER = 'tmgmt_xtm';
    /**
     * @var array
     */
    protected $availableActions = [
        self::CREATE_PROJECT_FOR_PMMTOM,
        self::UPDATE_PROJECT_ACTIVITY,
        self::FIND_TEMPLATE,
        self::DOWNLOAD_PROJECT_MTOM,
        self::CHECK_PROJECT_COMPLETION,
        self::GET_XTM_INFO,
        self::FIND_CUSTOMER
    ];

    /**
     * @param Translator $translator
     * @param bool $global
     * @param null $customerId
     * @return array
     */
    public function getTemplates(Translator $translator, $global = true, $customerId = null)
    {
        try {
            if (true === is_null($customerId)) {
                $customerId = $translator->getSetting(self::XTM_PROJECT_CUSTOMER_ID);
            }

            $input = ['filter' => ['scope' => self::XTM_TEMPLATE_SCOPE_ALL]];
            $output = [];
            $response = $this->doRequest($translator, self::FIND_TEMPLATE, $input);
            if (empty($response->templates)) {
                return $output;
            }
            foreach ($this->parseToArray($response->templates) as $template) {
                if (($global && !isset($template->customer)) || $template->customer->id == $customerId) {
                    $output[$template->template->id] = $template->template->name;
                }
            }

            return $output;
        } catch (\SoapFault $fault) {
            watchdog_exception(self::XTM_LOGGER, $fault, $fault->faultstring);
            drupal_set_message($fault->faultstring, 'error');
            return [];
        } catch (TMGMTXtmException $e) {
            watchdog_exception(self::XTM_LOGGER, $e, $e->getMessage());
            drupal_set_message($e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * @param Translator $translator
     * @return array
     */
    public function getXTMInfo(Translator $translator)
    {
        $response = $this->doRequest($translator, self::GET_XTM_INFO, []);

        if (empty($response->xtmInfo)) {
            return [];
        }

        return $response->xtmInfo;
    }


    /**
     * Retrieve translated project from XTM service.
     * @param Job $job
     * @return bool
     */
    public function retrieveTranslation(Job $job)
    {
        try {
            if ($job->getState() == Job::STATE_FINISHED) {
                return false;
            }
            if (!is_writable(file_directory_temp())) {
                throw new TMGMTXtmException('The temporary directory is not writable. 
Please check settings or the permissions on <i>@name</i> directory.',
                    ['@name' => file_directory_temp()]);
            }
            $reference = $job->getReference();
            $filesMTOM = $this->doRequest(
                $job->getTranslator(),
                self::DOWNLOAD_PROJECT_MTOM,
                [
                    'project' =>
                        ['id' => $reference]
                ]
            );

            if (empty($filesMTOM->project->jobs)) {
                throw new TMGMTXtmException("Could not get translated files from project 
                #@projectId or project has not been completed.",
                    ['@projectId' => $reference]);
            }

            foreach ($this->parseToArray($filesMTOM->project->jobs) as $file) {
                $this->populateJobs($job, $file);
            }

            return true;
        } catch (\SoapFault $fault) {
            \Drupal::logger(self::XTM_LOGGER)->notice("XTM retrieveTranslation:" . GuzzleHttp\json_encode($fault));
            drupal_set_message($fault->faultstring, 'error', false);
            $job->addMessage($fault->faultstring, [], 'error');
            return false;
        } catch (TMGMTXtmException $e) {
            \Drupal::logger(self::XTM_LOGGER)->notice("XTM retrieveTranslation:" . GuzzleHttp\json_encode($e));
            watchdog_exception(self::XTM_LOGGER, $e, $e->getMessage());
            drupal_set_message($e->getMessage(), 'error', false);
            $job->addMessage($e->getMessage(), [], 'error');
            return false;
        } catch (Exception $e) {
            \Drupal::logger(self::XTM_LOGGER)->notice("XTM retrieveTranslation:" . GuzzleHttp\json_encode($e));
            watchdog_exception(self::XTM_LOGGER, $e, $e->getMessage());
            drupal_set_message($e->getMessage(), 'error', false);
            $job->addMessage($e->getMessage(), [], 'error');
            return false;
        }
    }

    /**
     * @param Job $job
     * @return object | array
     */
    public function checkProjectStatus(Job $job)
    {
        $input = ['project' => ['id' => $job->getReference()]];

        $response = $this->doRequest($job->getTranslator(), self::CHECK_PROJECT_COMPLETION, $input);

        if (empty($response->project)) {
            return [];
        }
        $output = $response->project;
        $output->jobs = $this->parseToArray($response->project->jobs);

        return $output;
    }

    /**
     * @param Job $job
     * @param string $activity
     * @return bool
     */
    public function updateProjectActivity(Job $job, $activity = self::XTM_ACTION_ARCHIVE)
    {
        try {
            $input = [
                'projects' => ['id' => $job->getReference()],
                'options'  => ['activity' => $activity]
            ];
            $response = $this->doRequest($job->getTranslator(), self::UPDATE_PROJECT_ACTIVITY, $input);
            return $response->projects->result == 1;
        } catch (\SoapFault $fault) {
            \Drupal::logger(self::XTM_LOGGER)->notice(GuzzleHttp\json_encode($fault));
            drupal_set_message($fault->faultstring, 'error');
            return false;
        } catch (TMGMTXtmException $e) {
            watchdog_exception(self::XTM_LOGGER, $e, $e->getMessage());
            drupal_set_message($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * @param Translator $translator
     * @param $id
     * @return mixed
     */
    public function findCustomer(Translator $translator, $id)
    {
        $input = [];

        if (!is_null($id)) {
            $input['filter'] = [
                'customers' => [
                    'id' => (int)$id
                ]
            ];
        }
        try {
            return $this->doRequest($translator, self::FIND_CUSTOMER, $input);
        } catch (\SoapFault $fault) {
            \Drupal::logger(self::XTM_LOGGER)->notice(GuzzleHttp\json_encode($fault));
            drupal_set_message($fault->faultstring, 'error');
            return false;
        } catch (TMGMTXtmException $e) {
            watchdog_exception(self::XTM_LOGGER, $e, $e->getMessage());
            drupal_set_message($e->getMessage(), 'error');
            return false;
        }
    }


    /**
     * @param Job $job
     * @throws TMGMTException
     */
    public function xtmRequestTranslation($job)
    {
        try {
            $translator = $job->getTranslator();
            $projectMTOM = $this->createProjectMTOM($job, $translator);
            $callbackUrl = Url::fromRoute(
                'tmgmt_xtm.callback',
                [self::TMGMT_JOB_ID => $job->id()],
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

            $response = $this->doRequest(
                $translator,
                self::CREATE_PROJECT_FOR_PMMTOM,
                $input
            );

            if (isset($response->project)) {
                $job->reference = $response->project->projectDescriptor->id;
                $job->save();
                $job->submitted(
                    'The project has been successfully submitted
                     for translation. Project ID: @project_id.',
                    ['@project_id' => $job->getReference()]
                );
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
     * @param Translator $translator
     * @param $action
     * @param array $query
     * @param array $options
     * @return mixed
     */
    protected function doRequest(Translator $translator, $action, array $query = [], array $options = [])
    {
        $this->checkRequestConditions($translator, $action);
        $loginAPI = [
            'loginAPI' => [
                'userId'         => $translator->getSetting(self::XTM_API_USER_ID),
                'password'       => $translator->getSetting(self::XTM_API_PASSWORD),
                'client'         => $translator->getSetting(self::XTM_API_CLIENT_NAME),
                'integrationKey' => self::INTEGRATION_KEY
            ]
        ];
        $client = new \SoapClient($translator->getSetting(self::XTM_API_URL));
        $result = $client->__soapCall(
            $action,
            [array_merge($query, $loginAPI)]
        );

        return $result->return;
    }


    /**
     *
     * @return bool
     */
    private function isSoapEnabled()
    {
        return class_exists('SoapClient');
    }

    /**
     * @param $wsdl
     * @return bool
     */
    private function isWsdlAvailable($wsdl)
    {
        return !!@file_get_contents($wsdl);
    }

    /**
     * @param $item
     * @return array
     */
    private function parseToArray($item)
    {
        if (is_array($item)) {
            return $item;
        }
        return [$item];
    }

    /**
     * @param Job $job
     * @param Translator $translator
     * @return array
     */
    private function createProjectMTOM(Job $job, Translator $translator)
    {
        $helper = new Helper();
        $prefix = $translator->getSetting(self::PROJECT_NAME_PREFIX);

        $projectName = ($prefix ? "[$prefix] " : '');
        if ($job->isContinuous()) {
            $items = $job->getItems();
            $item = end($items);
            $projectName .= $item->getSourceLabel();
        } else {
            $projectName .= $helper->clearLabel($job->label());
        }
        $projectMTOM = [
            'name'             => $projectName,
            'sourceLanguage'   => $helper->mapLanguageToXTMFormat($job->getRemoteSourceLanguage(), $translator),
            'targetLanguages'  => $helper->mapLanguageToXTMFormat($job->getRemoteTargetLanguage(), $translator),
            'translationFiles' => ($job->getSetting(self::API_PROJECT_MODE) == 0)
                ? $helper->createSingleXMLFile($job) : $helper->createMultipleXMLFiles($job),
            'referenceId'      => $job->id(),
            'customer'         =>
                ['id' => $translator->getSetting(self::XTM_PROJECT_CUSTOMER_ID)],
        ];

        return $projectMTOM;
    }

    /**
     * @param Translator $translator
     * @param $action
     * @throws TMGMTException
     */
    private function checkRequestConditions(Translator $translator, $action)
    {
        if (!$this->isSoapEnabled()) {
            \Drupal::logger(self::XTM_LOGGER)->notice("The SOAP extension library is not installed.");
            throw new TMGMTException('The SOAP extension library is not installed.');
        }

        if (!$this->isWsdlAvailable($translator->getSetting(self::XTM_API_URL))) {
            \Drupal::logger(self::XTM_LOGGER)
                ->notice(" Could not connect to the XTM SOAP service. Please check settings. URL:" .
                    $translator->getSetting(self::XTM_API_URL));
            throw new TMGMTException('Could not connect to the XTM SOAP service. Please check settings.  URL:'
                . $translator->getSetting(self::XTM_API_URL));
        }

        if (!in_array($action, $this->availableActions)) {
            \Drupal::logger(self::XTM_LOGGER)->notice("XTM SOAP service");
            throw new TMGMTException('Invalid action requested: @action', ['@action' => $action]);
        }
    }

    /**
     * @param Job $job
     * @param $file
     * @throws TMGMTXtmException
     * @throws Exception
     */
    private function populateJobs(Job $job, $file)
    {
        $tempFile = file_directory_temp(). '/' . $file->targetLanguage . "_" . $file->fileName;
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        if (empty($file->fileMTOM)) {
            throw new Exception("Could not read XTM file");
        }
        if (file_put_contents($tempFile, $file->fileMTOM) === false) {
            throw new TMGMTXtmException("Could not download a file #@fileId from project #@projectId.",
                ['@fileId' => $file->fileDescriptor->id, '@projectId' => $job->getReference()]);
        }
        $helper = new Helper();
        $translatedText = $this->tryToGetXMLFromZip($file, $tempFile);

        if (!$translatedText) {
            throw new Exception("Could not read XTM file");
        }
        $xml = simplexml_load_string($translatedText);

        $keys = $this->getTargetLanguageKeys($xml);
        $jobId = $this->getJobId($job, $keys);

        foreach ($xml->children() as $xmlJob) {
            $data = [];
            $id = (string)$xmlJob->attributes()->id;
            if ($jobId > 0) {
                if ($file->targetLanguage != $helper->mapLanguageToXTMFormat($job->getTargetLangcode(),
                        $job->getTranslator())
                ) {
                    continue;
                }
                foreach ($keys as $key) {
                    $id = str_replace($key, $jobId, $id);
                }
            }

            $data[$id]['#text'] = (string)$xmlJob;
            $job->addTranslatedData(\Drupal::service('tmgmt.data')->unflatten($data));
        }
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    /**
     * @param object $file
     * @param string $tempFile
     * @return string
     */
    private function tryToGetXMLFromZip($file, $tempFile)
    {
        $helper = new Helper();
        $originalFileName = $file->originalFileName;
        $originalFileNameArray = explode(".", $originalFileName);
        $buildFileName = $originalFileNameArray[0] . "_" . $file->targetLanguage . "." . $originalFileNameArray[1];
        $filePathArray = [];
        $filePathArray[] = $tempFile . '#' . $file->targetLanguage . "/" . $buildFileName;
        $filePathArray[] = $tempFile;
        $filePathArray[] = $tempFile . '#' . $file->originalFileName;
        $filePathArray[] = $tempFile . '#' . $file->targetLanguage . '/' . $file->originalFileName;
        $filePathArray[] = $tempFile . '#' . $buildFileName;

        foreach ($filePathArray as $filePath) {
            $translatedText = $helper->readZipArchive($filePath);
            if ($translatedText) {
                return $translatedText;
            }
        }
        return "";
    }

    /**
     * @param $xml
     * @return array|mixed
     */
    private function getTargetLanguageKeys($xml)
    {
        $keys = [];

        foreach ($xml->children() as $xmlJob2) {
            if ("" != (string)$xmlJob2->attributes()->keys) {
                $keys = GuzzleHttp\json_decode((string)$xmlJob2->attributes()->keys, true);
            }
        }

        return $keys;
    }

    /**
     * @param Job $job
     * @param $keys
     * @return int
     */
    private function getJobId(Job $job, $keys)
    {
        $jobId = 0;
        if ($keys != []) {
            $jobId = $keys[$job->getTargetLangcode()];

        }
        return $jobId;
    }
}
