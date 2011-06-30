<?php
using('System.captcha.base');

class TCaptchaZendImage extends TCaptchaBase  {

	protected function _initialize() {
		$this->_container->setConfig('useTTFFont', true);
		CGAF::isFunctionExist('imagecreatetruecolor',true);
	}

	/* Generate random frequency
     *
     * @return float
     */
	protected function _randomFreq() {
		return mt_rand(700000, 1000000) / 15000000;
	}

	/**
	 * Generate random character size
	 *
	 * @return int
	 */
	protected function _randomSize() {
		return mt_rand(300, 700) / 100;
	}

	/* Generate random phase
     *
     * @return float
     */
	protected function _randomPhase() {
		// random phase from 0 to pi
		return mt_rand(0, 3141592) / 1000000;
	}

	/**
	 *
	 */
	public function renderImage() {
		$word = $this->getCode();
		$font = $this->_container->getFont();

		$w = $this->getWidth();
		$h = $this->getHeight();
		$fsize = $this->fontSize;

		//$img_file = $this->getImgDir() . $id . $this->getSuffix();
		if (empty($this->_backgroundImage)) {
			$img = imagecreatetruecolor($w, $h);
		} else {
			$img = imagecreatefrompng($this->_backgroundImage);
			if (! $img) {
				throw new SystemException("Can not load start image");
			}
			$w = imagesx($img);
			$h = imagesy($img);
		}
		$text_color = imagecolorallocate($img, 0, 0, 0);
		$bg_color = imagecolorallocate($img, 255, 255, 255);
		imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, $bg_color);
		$textbox = imageftbbox($fsize, 0, $font, $word);
		$x = ($w - ($textbox [2] - $textbox [0])) / 2;
		$y = ($h - ($textbox [7] - $textbox [1])) / 2;
		imagefttext($img, $fsize, 0, $x, $y, $text_color, $font, $word);

		// generate noise
		for($i = 0; $i < $this->dotNoiseLevel; $i ++) {
			imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), 2, 2, $text_color);
		}
		for($i = 0; $i < $this->lineNoiseLevel; $i ++) {
			imageline($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $text_color);
		}

		// transformed image
		$this->_image = imagecreatetruecolor($w, $h);
		$bg_color = imagecolorallocate($this->_image, 255, 255, 255);
		imagefilledrectangle($this->_image, 0, 0, $w - 1, $h - 1, $bg_color);
		// apply wave transforms
		$freq1 = $this->_randomFreq();
		$freq2 = $this->_randomFreq();
		$freq3 = $this->_randomFreq();
		$freq4 = $this->_randomFreq();

		$ph1 = $this->_randomPhase();
		$ph2 = $this->_randomPhase();
		$ph3 = $this->_randomPhase();
		$ph4 = $this->_randomPhase();

		$szx = $this->_randomSize();
		$szy = $this->_randomSize();

		for($x = 0; $x < $w; $x ++) {
			for($y = 0; $y < $h; $y ++) {
				$sx = $x + (sin($x * $freq1 + $ph1) + sin($y * $freq3 + $ph3)) * $szx;
				$sy = $y + (sin($x * $freq2 + $ph2) + sin($y * $freq4 + $ph4)) * $szy;

				if ($sx < 0 || $sy < 0 || $sx >= $w - 1 || $sy >= $h - 1) {
					continue;
				} else {
					$color = (imagecolorat($img, $sx, $sy) >> 16) & 0xFF;
					$color_x = (imagecolorat($img, $sx + 1, $sy) >> 16) & 0xFF;
					$color_y = (imagecolorat($img, $sx, $sy + 1) >> 16) & 0xFF;
					$color_xy = (imagecolorat($img, $sx + 1, $sy + 1) >> 16) & 0xFF;
				}
				if ($color == 255 && $color_x == 255 && $color_y == 255 && $color_xy == 255) {
					// ignore background
					continue;
				} elseif ($color == 0 && $color_x == 0 && $color_y == 0 && $color_xy == 0) {
					// transfer inside of the image as-is
					$newcolor = 0;
				} else {
					// do antialiasing for border items
					$frac_x = $sx - floor($sx);
					$frac_y = $sy - floor($sy);
					$frac_x1 = 1 - $frac_x;
					$frac_y1 = 1 - $frac_y;

					$newcolor = $color * $frac_x1 * $frac_y1 + $color_x * $frac_x * $frac_y1 + $color_y * $frac_x1 * $frac_y + $color_xy * $frac_x * $frac_y;
				}
				imagesetpixel($this->_image, $x, $y, imagecolorallocate($this->_image, $newcolor, $newcolor, $newcolor));
			}
		}

		// generate noise
		for($i = 0; $i < $this->_dotNoiseLevel; $i ++) {
			imagefilledellipse($this->_image, mt_rand(0, $w), mt_rand(0, $h), 2, 2, $text_color);
		}
		for($i = 0; $i < $this->_lineNoiseLevel; $i ++) {
			imageline($this->_image, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $text_color);
		}

		imagefttext($this->_image, $fsize, 0, $x, $y, $text_color, $font, $word);
		//imagepng($this->_image, $img_file);
		imagedestroy($img);
		return $this->_image;
	}
/**
	 *
	 */
	public function render() {

	}

}
?>