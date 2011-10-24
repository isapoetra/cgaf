<?php
namespace System\Captcha;

class Base extends AbstractCaptcha {
	private $_code;
	private $_tmpimg = null;
	private $_width = 200;
	private $_height = 50;
	private $_scale = 1;
	private $_image_bg_color;
	private $_text_color;
	private $_line_color;
	private $_signature_color;
	private $_text_transparency_percentage = 30;
	private $_use_transparent_text = true;
	private $_use_multi_text = false;
	private $_bgimg;
	private $_background_directory;
	private $_draw_lines_over_text = true;
	private $_num_lines = 1;
	private $_use_gd_font = false;
	private $_gd_font_file = 'fonts/automatic.gdf';
	private $_gd_font_size = 24;
	private $_text_x_start = 15;
	private $_image_type = IMG_JPG;
	private $_ttf_file = 'fonts/AHGBold.ttf';
	private $_perturbation = 0.75;
	private $_numchar = 3;
	private $_dotNoiseLevel = 100;
	private $_lineNoiseLevel = 5;

	protected function _initialize() {
		$this->_imageage_bg_color = new Securimage_Color(0, 0, 0);
		$this->_text_color = new Securimage_Color(0x0, 0x0, 0x0);
		$this->_line_color = new Securimage_Color(0x0, 0x0, 0x2d);
		$this->_signature_color = new Securimage_Color(0x20, 0x50, 0xCC);
		$this->_background_directory = $this->_container->getConfig('BackgroundDirectory');
		$this->_imageage_signature = '';
		$this->_signature_color = new Securimage_Color(0x20, 0x50, 0xCC);
	}

	private function distortedCopy() {
		$numpoles = 3; // distortion factor
		// make array of poles AKA attractor points
		for ($i = 0; $i < $numpoles; ++$i) {
			$px[$i] = rand($this->_width * 0.3, $this->_width * 0.7);
			$py[$i] = rand($this->_height * 0.3, $this->_height * 0.7);
			$rad[$i] = rand($this->_width * 0.4, $this->_width * 0.7);
			$tmp = -$this->frand() * 0.15 - 0.15;
			$amp[$i] = $this->_perturbation * $tmp;
		}

		$bgCol = imagecolorat($this->_tmpimg, 0, 0);
		$width2 = $this->_scale * $this->_width;
		$height2 = $this->_scale * $this->_height;

		imagepalettecopy($this->_image, $this->_tmpimg); // copy palette to final image so text colors come across

		// loop over $img pixels, take pixels from $tmpimg with distortion field
		for ($ix = 0; $ix < $this->_width; ++$ix) {
			for ($iy = 0; $iy < $this->_height; ++$iy) {
				$x = $ix;
				$y = $iy;

				for ($i = 0; $i < $numpoles; ++$i) {
					$dx = $ix - $px[$i];
					$dy = $iy - $py[$i];
					if ($dx == 0 && $dy == 0)
						continue;

					$r = sqrt($dx * $dx + $dy * $dy);
					if ($r > $rad[$i])
						continue;

					$rscale = $amp[$i] * sin(3.14 * $r / $rad[$i]);
					$x += $dx * $rscale;
					$y += $dy * $rscale;
				}

				$c = $bgCol;
				$x *= $this->_scale;
				$y *= $this->_scale;

				if ($x >= 0 && $x < $width2 && $y >= 0 && $y < $height2) {
					$c = imagecolorat($this->_tmpimg, $x, $y);
				}

				if ($c != $bgCol) { // only copy pixels of letters to preserve any background image
					imagesetpixel($this->_image, $ix, $iy, $c);
				}
			}
		}
	}

	function getBackgroundFromDirectory() {
		$images = array();

		if ($dh = opendir($this->_background_directory)) {
			while (($file = readdir($dh)) !== false) {
				if (preg_match('/(jpg|gif|png)$/i', $file))
					$images[] = $file;
			}

			closedir($dh);

			if (sizeof($images) > 0) {
				return rtrim($this->_background_directory, '/') . '/' . $images[rand(0, sizeof($images) - 1)];
			}
		}

		return false;
	}

	function setBackground() {
		imagefilledrectangle($this->_image, 0, 0, $this->_width * $this->_scale, $this->_height * $this->_scale, $this->_gdbgcolor);
		imagefilledrectangle($this->_tmpimg, 0, 0, $this->_width * $this->_scale, $this->_height * $this->_scale, $this->_gdbgcolor);

		if (!$this->_bgimg) {
			if ($this->_background_directory != null && is_dir($this->_background_directory) && is_readable($this->_background_directory)) {
				$img = $this->getBackgroundFromDirectory();
				if ($img != false) {
					$this->_bgimg = $img;
				}
			}
		}

		$dat = @getimagesize($this->_bgimg);
		if ($dat == false) {
			return;
		}

		switch ($dat[2]) {
		case 1:
			$newim = @imagecreatefromgif($this->_bgimg);
			break;
		case 2:
			$newim = @imagecreatefromjpeg($this->_bgimg);
			break;
		case 3:
			$newim = @imagecreatefrompng($this->_bgimg);
			break;
		case 15:
			$newim = @imagecreatefromwbmp($this->_bgimg);
			break;
		case 16:
			$newim = @imagecreatefromxbm($this->_bgimg);
			break;
		default:
			return;
		}

		if (!$newim)
			return;

		imagecopyresized($this->_image, $newim, 0, 0, 0, 0, $this->_width, $this->_height, imagesx($newim), imagesy($newim));
	}

	function renderImage() {
		$code = $this->_code;

		$this->_image = imagecreatetruecolor($this->_width, $this->_height);
		$this->_tmpimg = imagecreatetruecolor($this->_width * $this->_scale, $this->_height * $this->_scale);
		$this->allocateColors();
		imagepalettecopy($this->_tmpimg, $this->_image);
		$lnl = $this->getConfig('lineNoiseLevel', 5);
		$this->setBackground();
		if (!$this->_draw_lines_over_text && $this->num_lines > 0)
			$this->drawRandomLines($this->_image, $lnl, $this->_line_color);

		$this->drawWord($code);

		$ttf = $this->getResource($this->_ttf_file);
		if ($this->_use_gd_font == false && is_readable($ttf))
			$this->distortedCopy();

		if ($this->_draw_lines_over_text && $this->_num_lines > 0)
			$this->drawRandomLines($this->_image, $lnl, $this->_line_color);

		if (trim($this->_imageage_signature) != '')
			$this->addSignature();
		return $this->_image;
	}

	private function getResource($resourceName) {
		return $this->_container->getResource($resourceName);
	}

	private function drawWord($code) {
		$width2 = $this->_width * $this->_scale;
		$height2 = $this->_height * $this->_scale;

		if ($this->_use_gd_font == true || !is_readable($this->_ttf_file)) {
			if (!is_int($this->_gd_font_file)) { //is a file name
				$font = @imageloadfont($this->getResource($this->_gd_font_file));
				if ($font == false) {
					trigger_error("Failed to load GD Font file {$this->gd_font_file} ", E_USER_WARNING);
					return;
				}
			} else { //gd font identifier
				$font = $this->_gd_font_file;
			}

			imagestring($this->_image, $font, $this->_text_x_start, ($this->_height / 2) - ($this->_gd_font_size / 2), $code, $this->_gdtextcolor);
		} else { //ttf font
			$font_size = $height2 * .35;
			$bb = imagettfbbox($font_size, 0, $this->ttf_file, $this->code);
			$tx = $bb[4] - $bb[0];
			$ty = $bb[5] - $bb[1];
			$x = floor($width2 / 2 - $tx / 2 - $bb[0]);
			$y = round($height2 / 2 - $ty / 2 - $bb[1]);

			$strlen = strlen($this->code);
			if (!is_array($this->multi_text_color))
				$this->use_multi_text = false;

			if ($this->use_multi_text == false && $this->text_angle_minimum == 0 && $this->text_angle_maximum == 0) { // no angled or multi-color characters
				imagettftext($this->tmpimg, $font_size, 0, $x, $y, $this->gdtextcolor, $this->ttf_file, $code);
			} else {
				for ($i = 0; $i < $strlen; ++$i) {
					$angle = rand($this->text_angle_minimum, $this->text_angle_maximum);
					$y = rand($y - 5, $y + 5);
					if ($this->use_multi_text == true) {
						$font_color = $this->gdmulticolor[rand(0, sizeof($this->gdmulticolor) - 1)];
					} else {
						$font_color = $this->gdtextcolor;
					}

					$ch = $this->code{$i};

					imagettftext($this->tmpimg, $font_size, $angle, $x, $y, $font_color, $this->ttf_file, $ch);

					// estimate character widths to increment $x without creating spaces that are too large or too small
					// these are best estimates to align text but may vary between fonts
					// for optimal character widths, do not use multiple text colors or character angles and the complete string will be written by imagettftext
					if (strpos('abcdeghknopqsuvxyz', $ch) !== false) {
						$min_x = $font_size - ($this->iscale * 6);
						$max_x = $font_size - ($this->iscale * 6);
					} else if (strpos('ilI1', $ch) !== false) {
						$min_x = $font_size / 5;
						$max_x = $font_size / 3;
					} else if (strpos('fjrt', $ch) !== false) {
						$min_x = $font_size - ($this->iscale * 12);
						$max_x = $font_size - ($this->iscale * 12);
					} else if ($ch == 'wm') {
						$min_x = $font_size;
						$max_x = $font_size + ($this->iscale * 3);
					} else { // numbers, capitals or unicode
						$min_x = $font_size + ($this->iscale * 2);
						$max_x = $font_size + ($this->iscale * 5);
					}

					$x += rand($min_x, $max_x);
				} //for loop
			} // angled or multi-color
		} //else ttf font
		//$this->im = $this->tmpimg;
		//$this->output();
	} //function

	/* Allocate all colors that will be used in the CAPTCHA image
	 *
	 * @since 2.0.1
	 * @access private
	 */
	function allocateColors() {
		// allocate bg color first for imagecreate
		$this->_gdbgcolor = imagecolorallocate($this->_image, $this->_imageage_bg_color->r, $this->_imageage_bg_color->g, $this->_imageage_bg_color->b);

		$alpha = intval($this->_text_transparency_percentage / 100 * 127);

		if ($this->_use_transparent_text == true) {
			$this->_gdtextcolor = imagecolorallocatealpha($this->_image, $this->_text_color->r, $this->_text_color->g, $this->_text_color->b, $alpha);
			$this->_gdlinecolor = imagecolorallocatealpha($this->_image, $this->_line_color->r, $this->_line_color->g, $this->_line_color->b, $alpha);
		} else {
			$this->_gdtextcolor = imagecolorallocate($this->_image, $this->_text_color->r, $this->_text_color->g, $this->_text_color->b);
			$this->_gdlinecolor = imagecolorallocate($this->_image, $this->_line_color->r, $this->_line_color->g, $this->_line_color->b);
		}

		$this->gdsignaturecolor = imagecolorallocate($this->_image, $this->_signature_color->r, $this->_signature_color->g, $this->_signature_color->b);

		if ($this->_use_multi_text == true) {
			$this->gdmulticolor = array();

			foreach ($this->multi_text_color as $color) {
				if ($this->use_transparent_text == true) {
					$this->gdmulticolor[] = imagecolorallocatealpha($this->im, $color->r, $color->g, $color->b, $alpha);
				} else {
					$this->gdmulticolor[] = imagecolorallocate($this->im, $color->r, $color->g, $color->b);
				}
			}
		}
	}

	/**
	 * @param unknown_type $return
	 */
	public function render() {
		return $this->renderImage();
	}

}

/**
 * Color object for Securimage CAPTCHA
 *
 * @since 2.0
 * @package Securimage
 * @subpackage classes
 *
 */

class Securimage_Color {
	/**
	 * Red component: 0-255
	 *
	 * @var int
	 */
	public $r;
	/**
	 * Green component: 0-255
	 *
	 * @var int
	 */
	public $g;
	/**
	 * Blue component: 0-255
	 *
	 * @var int
	 */
	public $b;

	/**
	 * Create a new Securimage_Color object.<br />
	 * Specify the red, green, and blue components using their HTML hex code equivalent.<br />
	 * Example: The code for the HTML color #4A203C is:<br />
	 * $color = new Securimage_Color(0x4A, 0x20, 0x3C);
	 *
	 * @param $red Red component 0-255
	 * @param $green Green component 0-255
	 * @param $blue Blue component 0-255
	 */
	function Securimage_Color($red, $green = null, $blue = null) {
		if ($green == null && $blue == null && preg_match('/^#[a-f0-9]{3,6}$/i', $red)) {
			$col = substr($red, 1);
			if (strlen($col) == 3) {
				$red = str_repeat(substr($col, 0, 1), 2);
				$green = str_repeat(substr($col, 1, 1), 2);
				$blue = str_repeat(substr($col, 2, 1), 2);
			} else {
				$red = substr($col, 0, 2);
				$green = substr($col, 2, 2);
				$blue = substr($col, 4, 2);
			}

			$red = hexdec($red);
			$green = hexdec($green);
			$blue = hexdec($blue);
		} else {
			if ($red < 0)
				$red = 0;
			if ($red > 255)
				$red = 255;
			if ($green < 0)
				$green = 0;
			if ($green > 255)
				$green = 255;
			if ($blue < 0)
				$blue = 0;
			if ($blue > 255)
				$blue = 255;
		}

		$this->r = $red;
		$this->g = $green;
		$this->b = $blue;
	}

}

?>
