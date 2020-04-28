<?php

namespace Drupal\color_field;

/**
 * Hex represents the Hex color format.
 */
class ColorHex extends ColorBase {

  /**
   * The Hex triplet of the color.
   *
   * @var int
   */
  private $color;

  /**
   * Create a new Hex from a string.
   *
   * @param string $color
   *   The string hex value (i.e. "FFFFFF").
   * @param string $opacity
   *   The opacity value.
   *
   * @throws Exception
   */
  public function __construct($color, $opacity) {
    $color = trim(strtolower($color));

    if (substr($color, 0, 1) === '#') {
      $color = substr($color, 1);
    }

    if (strlen($color) === 3) {
      $color = str_repeat($color[0], 2) . str_repeat($color[1], 2) . str_repeat($color[2], 2);
    }

    if (!preg_match('/[0-9A-F]{6}/i', $color)) {
      // @throws exception.
    }

    $this->color = hexdec($color);
    $this->setOpacity(floatval($opacity));

    return $this;
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
    $rgb = $this->toRgb();
    $hex = '#';
    $hex .= str_pad(dechex($rgb->getRed()), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb->getGreen()), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($rgb->getBlue()), 2, "0", STR_PAD_LEFT);
    if ($opacity) {
      $hex .= ' ' . $this->getOpacity();
    }
    return strtoupper($hex);
  }

  /**
   * {@inheritdoc}
   */
  public function toHex() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toRgb() {
    $red = (($this->color & 0xFF0000) >> 16);
    $green = (($this->color & 0x00FF00) >> 8);
    $blue = (($this->color & 0x0000FF));
    $opacity = $this->getOpacity();
    return new ColorRGB($red, $green, $blue, $opacity);
  }

  /**
   * {@inheritdoc}
   */
  public function toHsl() {
    return $this->toRGB()->toHsl();
  }

}
