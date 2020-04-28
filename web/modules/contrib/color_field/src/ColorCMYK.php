<?php

namespace Drupal\color_field;

/**
 * ColorCMYK represents the CMYK color format.
 */
class ColorCMYK extends ColorBase {

  /**
   * The cyan.
   *
   * @var float
   */
  private $cyan;

  /**
   * The magenta.
   *
   * @var float
   */
  private $magenta;

  /**
   * The yellow.
   *
   * @var float
   */
  private $yellow;

  /**
   * The key (black).
   *
   * @var float
   */
  private $key;

  /**
   * Create a new CMYK color.
   *
   * @param float $cyan
   *   The cyan.
   * @param float $magenta
   *   The magenta.
   * @param float $yellow
   *   The yellow.
   * @param float $key
   *   The key (black).
   * @param float $opacity
   *   The opacity.
   */
  public function __construct($cyan, $magenta, $yellow, $key, $opacity) {
    $this->cyan = $cyan;
    $this->magenta = $magenta;
    $this->yellow = $yellow;
    $this->key = $key;
    $this->opacity = floatval($opacity);
  }

  /**
   * Get the amount of Cyan.
   *
   * @return int
   *   The amount of cyan.
   */
  public function getCyan() {
    return $this->cyan;
  }

  /**
   * Get the amount of Magenta.
   *
   * @return int
   *   The amount of magenta.
   */
  public function getMagenta() {
    return $this->magenta;
  }

  /**
   * Get the amount of Yellow.
   *
   * @return int
   *   The amount of yellow.
   */
  public function getYellow() {
    return $this->yellow;
  }

  /**
   * Get the key (black).
   *
   * @return int
   *   The amount of black.
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * A string representation of this color in the current format.
   *
   * @param bool $opacity
   *   Whether or not to display the opacity.
   *
   * @return string
   *   The color in format: #RRGGBB.
   */
  public function toString($opacity = TRUE) {
    return $this->toHex()->toString($opacity);
  }

  /**
   * {@inheritdoc}
   */
  public function toHex() {
    return $this->toRgb()->toHex();
  }

  /**
   * {@inheritdoc}
   */
  public function toRgb() {
    return $this->toCmy()->toRgb();
  }

  /**
   * {@inheritdoc}
   */
  public function toCmy() {
    $cyan = ($this->cyan * (1 - $this->key) + $this->key);
    $magenta = ($this->magenta * (1 - $this->key) + $this->key);
    $yellow = ($this->yellow * (1 - $this->key) + $this->key);
    return new ColorCMY($cyan, $magenta, $yellow, $this->getOpacity());
  }

  /**
   * {@inheritdoc}
   */
  public function toHsl() {
    return $this->toRgb()->toHsl();
  }

}
