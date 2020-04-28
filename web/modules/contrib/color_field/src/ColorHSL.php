<?php

namespace Drupal\color_field;

use Exception;

/**
 * RGB represents the RGB color format.
 */
class ColorHSL extends ColorBase {

  /**
   * Hue value (0-360).
   *
   * @var int
   */
  protected $hue;

  /**
   * Saturation value (0-100).
   *
   * @var int
   */
  protected $sat;

  /**
   * Luminance value (0-100).
   *
   * @var int
   */
  protected $lum;

  /**
   * Create a new HSL color.
   *
   * @param int $hue
   *   The hue (0-360)
   * @param int $sat
   *   The sat (0-100)
   * @param int $lum
   *   The lum (0-100)
   * @param float $opacity
   *   The opacity.
   *
   * @throws \Exception
   */
  public function __construct($hue, $sat, $lum, $opacity) {

    if ($hue < 0 || $hue > 360) {
      throw new Exception("Invalid hue: $hue");
    }
    if ($sat < 0 || $sat > 100) {
      throw new Exception("Invalid saturation: $sat");
    }
    if ($lum < 0 || $lum > 100) {
      throw new Exception("Invalid luminosity: $lum");
    }

    $this->hue = $hue;
    $this->sat = $sat;
    $this->lum = $lum;
    $this->opacity = floatval($opacity);
  }

  /**
   * Get the hue value.
   *
   * @return int
   *   The hue value
   */
  public function getHue() {
    return $this->hue;
  }

  /**
   * Get the sat value.
   *
   * @return int
   *   The sat value
   */
  public function getSat() {
    return $this->sat;
  }

  /**
   * Get the lum value.
   *
   * @return int
   *   The lum value
   */
  public function getLum() {
    return $this->lum;
  }

  /**
   * A string representation of this color in the current format.
   *
   * @param bool $opacity
   *   Whether or not to display the opacity.
   *
   * @return string
   *   The color in format: #RRGGBB
   */
  public function toString($opacity = TRUE) {
    if ($opacity) {
      $output = 'hsla(' . $this->hue . ',' . $this->sat . ',' . $this->lum . ',' . $this->getOpacity() . ')';
    }
    else {
      $output = 'hsl(' . $this->hue . ',' . $this->sat . ',' . $this->lum . ')';
    }
    return strtoupper($output);
  }

  /**
   * {@inheritdoc}
   */
  public function toHex() {
    return $this->toRGB()->toHex();
  }

  /**
   * {@inheritdoc}
   */
  public function toRgb() {
    $h = $this->getHue();
    $s = $this->getSat();
    $l = $this->getLum();

    $h /= 60;
    if ($h < 0) {
      $h = 6 - fmod(-$h, 6);
    }

    $h = fmod($h, 6);

    $s = max(0, min(1, $s / 100));
    $l = max(0, min(1, $l / 100));

    $c = (1 - abs((2 * $l) - 1)) * $s;
    $x = $c * (1 - abs(fmod($h, 2) - 1));

    if ($h < 1) {
      $r = $c;
      $g = $x;
      $b = 0;
    }
    elseif ($h < 2) {
      $r = $x;
      $g = $c;
      $b = 0;
    }
    elseif ($h < 3) {
      $r = 0;
      $g = $c;
      $b = $x;
    }
    elseif ($h < 4) {
      $r = 0;
      $g = $x;
      $b = $c;
    }
    elseif ($h < 5) {
      $r = $x;
      $g = 0;
      $b = $c;
    }
    else {
      $r = $c;
      $g = 0;
      $b = $x;
    }

    $m = $l - $c / 2;
    $r = round(($r + $m) * 255);
    $g = round(($g + $m) * 255);
    $b = round(($b + $m) * 255);

    return new ColorRGB(intval($r), intval($g), intval($b), $this->getOpacity());
  }

  /**
   * {@inheritdoc}
   */
  public function toHsl() {
    return $this;
  }

}
