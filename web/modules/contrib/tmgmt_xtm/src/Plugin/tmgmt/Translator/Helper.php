<?php

namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\Translator;

/**
 * Class Helper
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator
 */
class Helper
{

    /**
     * Available project modes.
     *
     * @var array
     */
    private $projectModes = [
        0 => 'Single file - translation returned at the end of the project',
        1 => 'Multiple files - translation returned when each file is complete',
        2 => 'Multiple files - translation returned when all files are complete'
    ];

    /**
     * Read a file from the ZIP archive.
     *
     * @param  string $filePath
     *
     * @return string
     */
    public function readZipArchive($filePath)
    {
        $zip = @fopen('zip://' . $filePath, 'r');
        if (!$zip) {
            return '';
        }
        $content = '';
        while (!feof($zip)) {
            $content .= fread($zip, 2);
        }
        fclose($zip);

        return $content;
    }

    /**
     * Return all available project modes (with translations).
     *
     * @return array
     */
    public function getProjectModes()
    {
        $out = [];
        foreach ($this->projectModes as $value) {
            $out[] = t($value);
        }

        return $out;
    }


    /**
     * @param $lang
     * @param Translator $translator
     * @return mixed
     */
    public function mapLanguageToXTMFormat($lang, Translator $translator)
    {
        $pluginWrapper = $translator->getSetting('plugin_wrapper');
        return $pluginWrapper['remote_languages_mappings'][$lang];
    }

    /**
     * @return array
     */
    public function getXtmLanguage()
    {
        return json_decode(file_get_contents(__DIR__ . "/countryList.json"), true);
    }

    /**
     * @return array
     */
    public function getXtmFlatLanguage()
    {
        $languageArray = $this->getXtmLanguage();

        $flatArray = [];
        foreach ($languageArray as $key => $value) {
            if (count($value) > 1) {
                foreach ($value as $subValue) {
                    $flatArray[key($subValue)] = $subValue[key($subValue)];
                }
            } else {
                $flatArray[$key] = $value;
            }
        }
        return $flatArray;
    }

    /**
     * @return array
     */
    private function getDefaultRemoteLanguagesMappings()
    {
        return [];
    }

    /**
     * @param Translator $translator
     * @param $language
     * @return mixed
     */
    public function mapToRemoteLanguage(Translator $translator, $language)
    {
        $default_mappings = $this->getDefaultRemoteLanguagesMappings();
        if (isset($default_mappings[$language])) {
            return $default_mappings[$language];
        }

        return $language;
    }

    /**
     * @param string $label
     * @return string
     */
    public function clearLabel($label)
    {
        $label = str_replace("&#039;", "'", $label);
        $pattern = [
            '"',
            "*",
            "$",
            "#",
            "^",
            "@",
            "!",
            "?",
            "~",
            "\\",
            "/",
            "&",
            ":",
            ";",
            "<",
            ">",
            "{",
            "}",
            "|",
            //   "'"
        ];
        return trim(urldecode(strip_tags(str_replace($pattern, "", html_entity_decode($label)))));
    }

    /**
     * @param string $label
     * @return string
     */
    public function clearFileName($label)
    {
        return trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ',
            urldecode(preg_replace("/&#?[a-z0-9]+;/i", "", strip_tags($label))))));
    }


    /**
     * @param Job $job
     * @return array
     */
    public function createSingleXMLFile(Job $job)
    {
        $xml = new \SimpleXMLElement('<xtm-drupal-jobs></xtm-drupal-jobs>');
        $data = \Drupal::service('tmgmt.data')->filterTranslatable($this->getJobData($job));
        $data = $this->reorderItems($data);
        foreach ($data as $id => $text) {
            $str = $this->stripInvalidXml($text['#text']);
            $xml->addChild('xtm-drupal-job', htmlspecialchars($str))->addAttribute('id', $id);
        }

        return [
            [
                'fileName'            => $this->filterFileName($job->label(), $job->id()),
                'fileMTOM'            => $xml->asXML(),
                'externalDescriptors' => []
            ]
        ];
    }

    /**
     * @param $value
     * @return string
     */
    protected function stripInvalidXml($value)
    {
        $ret = "";
        if (empty($value)) {
            return $ret;
        }

        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))
            ) {
                $ret .= chr($current);
            } else {
                $ret .= " ";
            }
        }
        return $ret;
    }


    /**
     * @param Job $job
     * @return array
     */
    public function createMultipleXMLFiles(Job $job)
    {
        $files = [];
        $data = \Drupal::service('tmgmt.data')->filterTranslatable($this->getJobData($job));

        $data = $this->reorderItems($data);

        foreach ($data as $id => $text) {
            $xml = new \SimpleXMLElement('<xtm-drupal-jobs></xtm-drupal-jobs>');
            $xml->addChild('xtm-drupal-job', htmlspecialchars($text['#text']))->addAttribute('id', $id);

            $files[] = [
                'fileName'            => $this->filterFileName($job->label(), $id),
                'fileMTOM'            => $xml->asXML(),
                'externalDescriptors' => []
            ];
        }

        return $files;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function reorderItems($data)
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (preg_match('/node_title/i', $key)) {
                $out[$key] = $value;
                unset($data[$key]);
            }
        }

        return $out += $data;
    }

    /**
     * Create filtered name of MTOM file.
     *
     * @param  string $label
     *   The main label for file.
     *
     * @param  string /int $id
     *   File name surfix.
     *
     * @return string
     */
    protected function filterFileName($label, $id)
    {
        $name = $this->clearFileName($label);
        return str_replace(['@name', '@id'], [$name, $id], '@name_[@id].xml');
    }

    /**
     * @param Job $job
     * @return array
     */
    protected function getJobData(Job $job)
    {
        $data = [];
        foreach ($job->getItems() as $tjiid => $item) {
            /** @var JobItem $item */
            if ($item->isActive() || $item->isInactive()) {
                $data[$tjiid] = $item->getData();
                // If not set, use the job item label as the data label.
                if (!isset($data[$tjiid]['#label'])) {
                    $data[$tjiid]['#label'] = $item->getSourceLabel();
                }
            }
        }

        return $data;
    }


}
