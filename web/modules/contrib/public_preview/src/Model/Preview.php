<?php

namespace Drupal\public_preview\Model;

use Drupal\Core\Language\LanguageInterface;
use InvalidArgumentException;

/**
 * Class Preview.
 *
 * Data class for a single preview.
 *
 * @package Drupal\public_preview\Model
 */
class Preview {

  /**
   * The preview ID or NULL.
   *
   * @var int|null
   */
  public $id;

  /**
   * The ID of the target Node.
   *
   * @var int
   */
  public $nid;

  /**
   * The generated, unique alphanumeric string.
   *
   * @var string
   */
  public $hash;

  /**
   * The language code for the node translation.
   *
   * @var string
   */
  public $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;

  /**
   * The original Preview when a DB UPDATE was done.
   *
   * @var \Drupal\public_preview\Model\Preview|null
   */
  public $original;

  /**
   * Preview constructor.
   *
   * @param int $nid
   *   The ID of the target Node.
   * @param string $hash
   *   The generated, unique alphanumeric string.
   * @param string $langcode
   *   The language code for the node translation.
   * @param int|null $id
   *   The preview ID or NULL.
   * @param null|\Drupal\public_preview\Model\Preview $original
   *   NULL, or the original Preview after a DB UPDATE.
   */
  public function __construct(
    $nid,
    $hash,
    $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED,
    $id = NULL,
    $original = NULL
  ) {
    $this->id = $id;
    $this->nid = $nid;
    $this->hash = $hash;
    $this->langcode = $langcode;
    $this->original = $original;
  }

  /**
   * Return the object as an array.
   *
   * @return array
   *   The array representation of the class.
   */
  public function toArray() {
    return (array) $this;
  }

  /**
   * Create a new Preview from an array of values.
   *
   * @param array $values
   *   The values.
   *   Required parameters are the nid and hash.
   *   Optional parameters are the langcode and id.
   *
   * @return \Drupal\public_preview\Model\Preview
   *   The new preview.
   *
   * @throws \InvalidArgumentException
   */
  public static function create(array $values) {
    if (!isset($values['nid'], $values['hash'])) {
      throw new InvalidArgumentException('The required argument nid or hash is not found.');
    }

    $id = isset($values['id']) ? $values['id'] : NULL;
    $langcode = isset($values['langcode']) ? $values['langcode'] : LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $original = isset($values['original']) ? $values['original'] : NULL;

    return new static(
      $values['nid'],
      $values['hash'],
      $langcode,
      $id,
      $original
    );
  }

}
