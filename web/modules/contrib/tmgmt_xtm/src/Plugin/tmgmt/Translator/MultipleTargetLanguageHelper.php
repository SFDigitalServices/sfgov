<?php

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\tmgmt\Entity\Job;

/**
 * Extend Helper class to provide multiple target project language in XTM
 *
 * Class MultipleTargetLanguageHelper
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator
 */
class MultipleTargetLanguageHelper extends Helper
{
    /**
     * @param $jobs
     * @return array
     */
    public function createSingleXMLFile($jobs)
    {
        list($keys, $job) = $this->createIDsForMultipleTranslation($jobs);
        $xml = new \SimpleXMLElement('<xtm-drupal-jobs></xtm-drupal-jobs>');
        $data = \Drupal::service('tmgmt.data')->filterTranslatable($this->getJobData($job));
        $data = $this->reorderItems($data);
        foreach ($data as $id => $text) {
            $str = $this->stripInvalidXml($text['#text']);
            $xml->addChild('xtm-drupal-job', htmlspecialchars($str))->addAttribute('id', $id);
        }
        $xml->addChild('xtm-drupal-multiple-job', '')->addAttribute('keys', \GuzzleHttp\json_encode($keys));

        return [
            [
                'fileName'            => $this->filterFileName($job->label(), $job->id()),
                'fileMTOM'            => $xml->asXML(),
                'externalDescriptors' => []
            ]
        ];
    }

    /**
     * @param $jobs
     * @return array
     */
    private function createIDsForMultipleTranslation($jobs)
    {
        $keys = [];
        $job = null;
        foreach ($jobs as $job) {
            /** @var Job $job */
            $dataP = \Drupal::service('tmgmt.data')->filterTranslatable($this->getJobData($job));
            $dataP = $this->reorderItems($dataP);
            foreach ($dataP as $idP => $textP) {
                $keys[$job->getTargetLangcode()][] = intval($idP);
            }
            $keys[$job->getTargetLangcode()] = array_unique($keys[$job->getTargetLangcode()]);
        }
       // $keys = array_unique($keys);
        return [$keys, $job];
    }


    /**
     * @param $jobs
     * @return array
     */
    public function createMultipleXMLFiles($jobs)
    {
        list($keys, $job) = $this->createIDsForMultipleTranslation($jobs);

        $files = [];
        $data = \Drupal::service('tmgmt.data')->filterTranslatable($this->getJobData($job));

        $data = $this->reorderItems($data);

        foreach ($data as $id => $text) {
            $xml = new \SimpleXMLElement('<xtm-drupal-jobs></xtm-drupal-jobs>');
            $xml->addChild('xtm-drupal-job', htmlspecialchars($text['#text']))->addAttribute('id', $id);
            $xml->addChild('xtm-drupal-multiple-job', '')->addAttribute('keys', \GuzzleHttp\json_encode($keys));

            $files[] = [
                'fileName'            => $this->filterFileName($job->label(), $id),
                'fileMTOM'            => $xml->asXML(),
                'externalDescriptors' => []
            ];
        }

        return $files;
    }
}
