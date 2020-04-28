<?php


namespace Drupal\tmgmt_xtm\Plugin\tmgmt\Translator;

/**
 * Class TMGMTXtmException
 * @package Drupal\tmgmt_xtm\Plugin\tmgmt\Translator
 */
class TMGMTXtmException extends \Exception
{
    /**
     * @param string $message
     *
     * @param array $data
     *   Associative array of dynamic data that will be inserted into $message.
     *
     * @param int $code
     *
     * @param \Exception|null $previous
     */
    public function __construct($message = "", $data = [], $code = 0, \Exception $previous = null)
    {
        parent::__construct(strtr($message, $data), $code, $previous);
    }
}
