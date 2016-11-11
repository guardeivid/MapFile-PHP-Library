<?php
/**
 * MapFile Generator - MapServer .MAP Generator (Read, Write & Preview).
 * PHP Version 5.3+
 * @link https://github.com/jbelien/MapFile-Generator
 * @author Jonathan Beliën <jbe@geo6.be>
 * @copyright 2015 Jonathan Beliën
 * @license GNU General Public License, version 2
 * @note This project is still in development. Please use with caution !
 */
namespace MapFile;

/**
 * MapFile Generator - Scalebar (SCALEBAR) Class.
 * [MapFile SCALEBAR clause](http://mapserver.org/mapfile/scalebar.html).
 * @package MapFile
 * @author Jonathan Beliën <jbe@geo6.be>
 * @link http://mapserver.org/mapfile/scalebar.html
 */
class Scalebar {
  const STATUS_ON = 1;
  const STATUS_OFF = 0;

  const UNITS_INCHES = 0;
  const UNITS_FEET = 1;
  const UNITS_MILES = 2;
  const UNITS_METERS = 3;
  const UNITS_KILOMETERS = 4;
  const UNITS_DD = 5;
  const UNITS_PIXELS = 6;
  const UNITS_NAUTICALMILES = 8;

  /**
   * @var integer[] Color (RGB Format).
   * @note Index `0` = Red [0-255], Index `1` = Green [0-255], Index `2` = Blue [0-255]
   */
  private $color = array(0,0,0);
  /**
   * @var integer[] Outline color (RGB Format).
   * @note Index `0` = Red [0-255], Index `1` = Green [0-255], Index `2` = Blue [0-255]
   */
  private $outlinecolor = array(0,0,0);

  /** @var integer $intervals Number of intervals to break the scalebar into. */
  public $intervals = 4;
  /**
   * @var integer Scalebar Status (Is the scalebar active ?).
   * @note Use :
   * * self::STATUS_ON
   * * self::STATUS_OFF
   */
  public $status = self::STATUS_OFF;
  /**
   * @var integer Units of the map coordinates.
   * @note Use :
   * * self::UNITS_INCHES
   * * self::UNITS_FEET
   * * self::UNITS_MILES
   * * self::UNITS_METERS
   * * self::UNITS_KILOMETERS
   * * self::UNITS_DD
   * * self::UNITS_PIXELS
   * * self::UNITS_NAUTICALMILES
   */
  public $units = self::UNITS_METERS;

  /**
   * @var \MapFile\Label Scalebar Label object.
   */
  public $label;

  /**
   * Constructor.
   * @param string[] $scalebar Array containing MapFile SCALEBAR clause.
   * @todo Must read a MapFile SCALEBAR clause without passing by an Array.
   */
  public function __construct($scalebar = NULL) {
    if (!is_null($scalebar)) {
      $this->read($scalebar);
    }

    if (is_null($this->label)) {
      $this->label = new Label();
    }
  }

  /**
   * Set the `color` property.
   * @param integer $r Red component [0-255].
   * @param integer $g Green component [0-255].
   * @param integer $b Blue component [0-255].
   * @throws \MapFile\Exception if any component is lower < 0 or > 255
   */
  public function setColor($r,$g,$b) {
    if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255) {
          $this->color = array($r,$g,$b);
    } else {
          throw new Exception('Invalid SCALEBAR COLOR('.$r.' '.$g.' '.$b.').');
    }
  }
  /**
   * Set the `outlinecolor` property.
   * @param integer $r Red component [0-255].
   * @param integer $g Green component [0-255].
   * @param integer $b Blue component [0-255].
   * @throws \MapFile\Exception if any component is lower < 0 or > 255
   */
  public function setOutlineColor($r,$g,$b) {
    if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255) {
          $this->outlinecolor = array($r,$g,$b);
    } else {
          throw new Exception('Invalid SCALEBAR OUTLINECOLOR('.$r.' '.$g.' '.$b.').');
    }
  }

  /**
   * Write a valid MapFile SCALEBAR clause.
   * @return string
   * @uses \MapFile\Label::write()
   */
  public function write() {
    $scalebar  = '  SCALEBAR'.PHP_EOL;
    $scalebar .= '    STATUS '.self::convertStatus($this->status).PHP_EOL;
    if (!is_null($this->units)) $scalebar .= '    UNITS '.self::convertUnits($this->units).PHP_EOL;
    if (!empty($this->color) && array_sum($this->color) >= 0) $scalebar .= '    COLOR '.implode(' ', $this->color).PHP_EOL;
    if (!empty($this->outlinecolor) && array_sum($this->outlinecolor) >= 0) $scalebar .= '    OUTLINECOLOR '.implode(' ', $this->outlinecolor).PHP_EOL;
    if (!empty($this->intervals)) $scalebar .= '    INTERVALS '.intval($this->intervals).PHP_EOL;
    $scalebar .= $this->label->write(2);
    $scalebar .= '  END # SCALEBAR'.PHP_EOL;

    return $scalebar;
  }

  /**
   * Read a valid MapFile SCALEBAR clause (as array).
   * @param string[] $array MapFile SCALEBAR clause splitted in an array.
   * @uses \MapFile\Label::read()
   * @todo Must read a MapFile SCALEBAR clause without passing by an Array.
   */
  private function read($array) {
    $scalebar = FALSE; $reading = NULL;

    foreach ($array as $_sz) {
      $sz = trim($_sz);

      if (preg_match('/^SCALEBAR$/i', $sz)) {
        $scalebar = TRUE;
      } else if ($scalebar && is_null($reading) && preg_match('/^END( # SCALEBAR)?$/i', $sz)) {
        $scalebar = FALSE;
      } else if ($scalebar && is_null($reading) && preg_match('/^LABEL$/i', $sz)) { $reading = 'LABEL'; $label = array( $sz ); } else if ($scalebar && $reading == 'LABEL' && preg_match('/^END( # LABEL)?$/i', $sz)) { $label[] = $sz; $this->label = new Label($label); $reading = NULL; unset($label); } else if ($scalebar && $reading == 'LABEL') { $label[] = $sz; } else if ($scalebar && is_null($reading) && preg_match('/^STATUS (.+)$/i', $sz, $matches)) {
        $this->status = intval(self::convertStatus(strtoupper($matches[1])));
      } else if ($scalebar && is_null($reading) && preg_match('/^INTERVALS ([0-9]+)$/i', $sz, $matches)) {
        $this->intervals = intval($matches[1]);
      } else if ($scalebar && is_null($reading) && preg_match('/^COLOR ([0-9]+) ([0-9]+) ([0-9]+)$/i', $sz, $matches)) {
        $this->color = array(intval($matches[1]), intval($matches[2]), intval($matches[3]));
      } else if ($scalebar && is_null($reading) && preg_match('/^OUTLINECOLOR ([0-9]+) ([0-9]+) ([0-9]+)$/i', $sz, $matches)) {
        $this->outlinecolor = array(intval($matches[1]), intval($matches[2]), intval($matches[3]));
      } else if ($scalebar && is_null($reading) && preg_match('/^UNITS (.+)$/i', $sz, $matches)) {
        $this->units = intval(self::convertUnits(strtoupper($matches[1])));
      }
    }
  }

  /**
   * Convert `status` property to the text value or to the constant matching the text value.
   * @param string|integer $s
   * @return integer|string
   */
  private static function convertStatus($s = NULL) {
    $statuses = array(
      self::STATUS_ON  => 'ON',
      self::STATUS_OFF => 'OFF'
    );

    if (is_numeric($s) && isset($statuses[$s])) {
      return $statuses[$s];
    }
    else if (!is_numeric($s) && array_search($s, $statuses)) {
      return array_search($s, $statuses);
    }
    else {
      throw new Exception(sprintf('Invalid STATUS (%s).', $s));
    }
  }
  /**
   * Convert `units` property to the text value or to the constant matching the text value.
   * @param string|integer $u
   * @return integer|string
   */
  private static function convertUnits($u = NULL) {
    $units = array(
      self::UNITS_INCHES        => 'INCHES',
      self::UNITS_FEET          => 'FEET',
      self::UNITS_MILES         => 'MILES',
      self::UNITS_METERS        => 'METERS',
      self::UNITS_KILOMETERS    => 'KILOMETERS',
      self::UNITS_DD            => 'DD',
      self::UNITS_PIXELS        => 'PIXELS',
      self::UNITS_NAUTICALMILES => 'NAUTICALMILES'
    );

    if (is_numeric($u) && isset($units[$u])) {
      return $units[$u];
    }
    else if (!is_numeric($u) && array_search($u, $units)) {
      return array_search($u, $units);
    }
    else {
      throw new Exception(sprintf('Invalid UNITS (%s).', $u));
    }
  }
}