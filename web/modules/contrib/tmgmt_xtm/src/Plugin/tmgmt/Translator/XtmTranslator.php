<?php

/**
 * @file
 * Contains \Drupal\tmgmt_microsoft\Plugin\tmgmt\Translator\MicrosoftTranslator.
 */

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Xtm translator plugin.
 *
 * @TranslatorPlugin(
 *   id = "xtm",
 *   label = @Translation("XTM"),
 *   description = @Translation("Xtm Translator service."),
 *   ui = "Drupal\tmgmt_xtm\XtmTranslatorUi",
 *   logo = "icons/xtm.jpg",
 *   map_remote_languages = false
 * )
 */
class XtmTranslator extends TranslatorPluginBase implements
    ContainerFactoryPluginInterface,
    ContinuousTranslatorInterface
{

    /**
     * Name of parameter that contains source string to be translated.
     *
     * @var string
     */
    protected $qParamName = 'q';

    /**
     * Maximum supported characters.
     *
     * @var int
     */
    protected $maxCharacters = 100000;

    /**
     * Guzzle HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * Constructs a LocalActionBase object.
     *
     * @param \GuzzleHttp\ClientInterface $client
     *   The Guzzle HTTP client.
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param array $plugin_definition
     *   The plugin implementation definition.
     */
    public function __construct(
        ClientInterface $client,
        array $configuration,
        $plugin_id,
        array $plugin_definition
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->client = $client;
    }

    /**
     * @param JobInterface $job
     * @return bool
     */
    public function abortTranslation(JobInterface $job)
    {
        $connector = new Connector();
        if ($connector->updateProjectActivity($job)) {
            $job->aborted();
        } else {
            $message = 'The job was aborted, but the project activity has not been
             updated in XTM. Please check settings and/or update project manually.';
            $job->aborted($message, [], 'warning');
            drupal_set_message(t($message), 'warning');
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
        return new static(
            $container->get('http_client'),
            $configuration,
            $plugin_id,
            $plugin_definition
        );
    }

    /**
     * Overrides TMGMTDefaultTranslatorPluginController::checkAvailable().
     * @param TranslatorInterface $translator
     * @return AvailableResult
     */
    public function checkAvailable(TranslatorInterface $translator)
    {
        if ($translator->getSetting('xtm_api_url')) {
            return AvailableResult::yes();
        }

        return AvailableResult::no(
            t(
                '@translator is not available. Make sure it is properly
 <a href=:configured>configured</a>.',
                [
                    '@translator' => $translator->label(),
                    ':configured' => $translator->url()
                ])
        );
    }

    /**
     * @param TranslatorInterface $translator
     * @param JobInterface $job
     * @return mixed
     */
    public function checkTranslatable(TranslatorInterface $translator, JobInterface $job)
    {
        foreach (\Drupal::service('tmgmt.data')->filterTranslatable($job->getData()) as $value) {
            if (Unicode::strlen($value['#text']) > $this->maxCharacters) {
                return TranslatableResult::no(t('The length of the job exceeds tha max character count (@count).',
                    ['@count' => $this->maxCharacters]));
            }
        }
        if ($this->checkAvailable($translator)->getSuccess()) {
            $helper = new Helper();
            $supportedTargetLanguages = $helper->getXtmLanguage();
            $mapTargetLanguage = $helper->mapLanguageToXTMFormat($job->getTargetLangcode(), $translator);

            foreach ($supportedTargetLanguages as $spl) {
                if (array_key_exists($mapTargetLanguage, $spl)) {
                    return TranslatableResult::yes();
                }

                if (count($spl) > 1) {
                    // more then one lang
                    foreach ($spl as $lang) {
                        if (array_key_exists($mapTargetLanguage, $lang)) {
                            return TranslatableResult::yes();
                        }
                    }
                }
            }
        }

        return TranslatableResult::no(t('@translator can not translate from @source to @target.', [
            '@translator' => $translator->label(),
            '@source'     => $job->getSourceLanguage()->getName(),
            '@target'     => $job->getTargetLanguage()->getName()
        ]));
    }

    /**
     * Implements TMGMTTranslatorPluginControllerInterface::requestTranslation().
     * @param JobInterface $job
     */
    public function requestTranslation(JobInterface $job)
    {
        $this->requestJobItemsTranslation($job->getItems());
    }

    /**
     * Overrides TMGMTDefaultTranslatorPluginController::getSupportedRemoteLanguages().
     */
    public function getSupportedRemoteLanguages(TranslatorInterface $translator)
    {
        return [];
    }

    /**
     * Overrides TMGMTDefaultTranslatorPluginController::getDefaultRemoteLanguagesMappings().
     */
    public function getDefaultRemoteLanguagesMappings()
    {
        parent::getDefaultRemoteLanguagesMappings();
        return [
            'zh-hans' => 'zh-CHS',
            'zh-hant' => 'zh-CHT',
        ];
    }

    /**
     * @param TranslatorInterface $translator
     * @param $sourceLanguage
     * @return array|mixed
     */
    public function getSupportedTargetLanguages(TranslatorInterface $translator, $sourceLanguage)
    {
        $helper = new Helper();
        $languages = $helper->getXtmLanguage();

        if (array_key_exists($sourceLanguage, $languages)) {
            //unset($languages[$sourceLanguage]);
            return $languages[$sourceLanguage];
        }

        return [];
    }

    /**
     * @param JobInterface $job
     * @return bool
     */
    public function hasCheckoutSettings(JobInterface $job)
    {
        parent::hasCheckoutSettings($job);
        return false;
    }

    /**
     * @param Translator $translator
     * @param null $id
     * @return array
     */
    public function findCustomer(Translator $translator, $id = null)
    {
        $connector = new Connector();
        $response = $connector->findCustomer($translator, $id);

        if (empty($response->customers)) {
            return [];
        }

        return $this->parseToArray($response->customers);
    }

    /**
     * The q parameter name needs to be overridden for Drupal testing as it
     * collides with Drupal q parameter.
     *
     * @param $name
     */
    final public function setQParamName($name)
    {
        $this->qParamName = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function requestJobItemsTranslation(array $jobItems)
    {
        /** @var \Drupal\tmgmt\Entity\Job $job */
        $job = reset($jobItems)->getJob();
        foreach ($jobItems as $jobItem) {
            if ($job->isContinuous()) {
                $jobItem->active();
            }
        }
        try {
            $connector = new Connector();
            $connector->xtmRequestTranslation($job);
        } catch (TMGMTException $e) {
            $job->rejected('Translation has been rejected with following error: @error',
                ['@error' => $e->getMessage()], 'error');
        }
    }

    /**
     * @param $item
     * @return array
     */
    private function parseToArray($item)
    {
        if (is_array($item)) {
            return $item;
        } else {
            return [$item];
        }
    }
}
