<?php

namespace Vikpe;

class HtmlHeadingNormalizer
{
    public static function demote($html, $numberOfLevels)
    {
        return self::normalize($html, $numberOfLevels);
    }

    public static function promote($html, $numberOfLevels)
    {
        return self::normalize($html, -$numberOfLevels);
    }

    public static function min($html, $minLevel)
    {
        if (!self::containsHeadings($html)) {
            return $html;
        }

        $currentMinLevel = min(self::headingLevels($html));
        $levelDiff = $minLevel - $currentMinLevel;

        return self::normalize($html, $levelDiff);
    }

    private static function normalize($html, $numberOfLevels)
    {
        $normalizationIsRequired = ((abs($numberOfLevels) > 0) && self::containsHeadings($html));

        if (!$normalizationIsRequired) {
            return $html;
        }

        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($html);

        $originalHeadings = self::getHeadings($domDocument);
        $normalizedHeadings = self::normalizeHeadings($originalHeadings, $numberOfLevels);
        self::replaceHeadings($originalHeadings, $normalizedHeadings);

        return self::formatResult($domDocument, $html);
    }

    private static function getHeadings(\DOMDocument $domDocument)
    {
        $tagNames = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
        $headings = array();

        foreach ($tagNames as $tagName) {
            foreach ($domDocument->getElementsByTagName($tagName) as $heading) {
                $headings[] = $heading;
            }
        }

        return $headings;
    }

    private static function normalizeHeadings(array $originalHeadings, $numberOfLevels)
    {
        $normalizedHeadings = array();

        foreach ($originalHeadings as $heading) {
            $currentLevel = self::tagNameToLevel($heading->tagName);
            $newLevel = $currentLevel + $numberOfLevels;

            $normalizedHeadings[] = self::cloneHeading($heading, $newLevel);
        }

        return $normalizedHeadings;
    }

    private static function replaceHeadings(array $needles, array $replacements)
    {
        foreach ($needles as $i => $needle) {
            $needle->parentNode->replaceChild($replacements[$i], $needle);
        }
    }

    private static function containsHeadings($html)
    {
        $headingNeedle = '<h';
        $containsHeadings = (false !== stripos($html, $headingNeedle));

        return $containsHeadings;
    }

    private static function tagNameToLevel($tagName)
    {
        return substr($tagName, 1);
    }

    private static function levelToTagName($level)
    {
        return 'h'.$level;
    }

    private static function cloneHeading(\DOMElement $sourceHeading, $newLevel)
    {
        $tagName = self::levelToTagName($newLevel);

        $targetHeading = $sourceHeading->parentNode->ownerDocument->createElement($tagName);
        self::copyAttributes($sourceHeading, $targetHeading);
        self::moveChildNodes($sourceHeading, $targetHeading);

        return $targetHeading;
    }

    private static function copyAttributes(\DOMElement $source, \DOMElement $target)
    {
        foreach ($source->attributes as $attribute) {
            $target->setAttribute($attribute->name, $attribute->value);
        }
    }

    private static function moveChildNodes(\DOMElement $source, \DOMElement $target)
    {
        while ($source->hasChildNodes()) {
            // appendChild() actually moves the childNode
            $target->appendChild($source->childNodes->item(0));
        }
    }

    private static function formatResult(\DOMDocument $domDocument, $originalHtml)
    {
        if (!self::containsDocType($originalHtml)) {
            $domDocument->removeChild($domDocument->doctype);
        }

        if (self::containsHtmlTag($originalHtml)) {
            return $domDocument->saveHTML();
        } else {
            $bodyDomElement = $domDocument->getElementsByTagName('body')
                                          ->item(0);

            $html = $domDocument->saveHTML($bodyDomElement);

            return str_replace(array('<body>', '</body>'), '', $html);
        }
    }

    private static function containsDocType($html)
    {
        return self::stringContains($html, '<!DOCTYPE');
    }

    private static function stringContains($string, $needle)
    {
        return false !== strpos($string, $needle);
    }

    private static function containsHtmlTag($html)
    {
        return self::stringContains($html, '<html');
    }

    private static function headingLevels($html)
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($html);
        $headings = self::getHeadings($domDocument);
        $headingLevels = array();

        foreach ($headings as $heading) {
            $headingLevels[] = self::tagNameToLevel($heading->tagName);
        }

        return $headingLevels;
    }
}
