<?php
namespace System\Documents;
class Image extends \Object {
	private $_file;
	private $_watermark;
	private $_outputFile;
	private $_outputQuality = 80;
	private $_outputWidth;
	private $_outputHeight;
	private $_overwrite = false;
	private $_watermarkConfig = array(
			'sizePercent'=> 20,
			'top'=> null,
			'left'=> null,
			'position'=> 'bottom-right');

	function __construct($f) {
		$this->_file = $f;
	}

	function setOverwrite($value) {
		$this->_overwrite = $value;
	}

	function addWatermark($p) {
		$this->_watermark = Utils::ToDirectory($p);
		return $this;
	}

	function setWatermarkSizePercent($value) {
		$this->_watermarkConfig ['sizePercent'] = $value;
	}

	function setWatermarkPosition($pos) {
		$this->_watermarkConfig ['position'] = $pos;
	}

	function setWatermarkLocation($top, $left) {
		$this->_watermarkConfig ['top'] = $top;
		$this->_watermarkConfig ['left'] = $top;
	}

	function getImage($file) {
		$ext = Utils::getFileExt($file, false);
		switch ($ext) {
			case 'png' :
				$im = @imagecreatefrompng($file);
				break;
			case 'jpeg' :
			case 'jpg' :
				$im = @imagecreatefromjpeg($file);
				break;
			case 'gif' :
				$im = imagecreatefromgif($file);
				break;
			default :
				ppd($ext);

		}
		if (! $im) {
			throw new SystemException('unable to open image' . $file);
		}
		imagealphablending($im, true);
		return $im;

	}

	function ImageCopyResampleBicubic(&$dst_img, &$src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) {
		ImagePaletteCopy($dst_img, $src_img);
		$rX = $src_w / $dst_w;
		$rY = $src_h / $dst_h;
		$w = 0;
		for($y = $dst_y; $y < $dst_h; $y ++) {
			$ow = $w;
			$w = round(($y + 1) * $rY);
			$t = 0;
			for($x = $dst_x; $x < $dst_w; $x ++) {
				$r = $g = $b = 0;
				$a = 0;
				$ot = $t;
				$t = round(($x + 1) * $rX);
				for($u = 0; $u < ($w - $ow); $u ++) {
					for($p = 0; $p < ($t - $ot); $p ++) {
						$c = ImageColorsForIndex($src_img, ImageColorAt($src_img, $ot + $p, $ow + $u));
						$r += $c ['red'];
						$g += $c ['green'];
						$b += $c ['blue'];
						$a ++;
					}
				}
				ImageSetPixel($dst_img, $x, $y, ImageColorClosest($dst_img, $r / $a, $g / $a, $b / $a));
			}
		}
	}

	private function _outputImage($img, $fout, $q) {
		$ext = Utils::getFileExt($fout, false);
		switch (strtolower($ext)) {
			case 'png' :
				//imagealphablending($img,FALSE);
				return imagepng($img, $fout, 8);
			case 'gif' :
				return imagegif($img, $fout);
			case 'jpeg' :
			case 'jpg' :
				return imagejpeg($img, $fout, $q);
		}
		return false;
	}

	function resizeImage($file, $out, $w, $h) {
		$source = $this->getImage($file);
		$ori = getimagesize($file);
		$ws = $ori [0];
		$hs = $ori [1];
		if ($ws > $w && $hs > $h) {
			$aspect = $ws / $hs;
			if ($aspect <= 1.333333) {
				$hd = $h;
				$wd = floor($hd * $aspect);
			} else {
				$wd = $w;
				$hd = floor($wd / $aspect);
			}
			$Z = ceil(log(($ws * $hs) / (4 * $w * $h))) + 1;
			if (log(($ws * $hs) / (4 * $w * $h)) < 0)
				$Z = 1;
			$dx = $dy = 0;
			if ($Z > 1) {
				$dest = imagecreatetruecolor(round($ws / $Z), round($hs / $Z));
				for($i = 0; $i < $hs; $i += $Z) {
					for($j = 0; $j < $ws; $j += $Z) {
						$rgb = imagecolorat($source, $j, $i);
						$a_s = ($rgb >> 24) << 1; ## 7 to 8 bits. alpha
						if ($a_s < 253) {
							$r = ($rgb >> 16) & 0xFF;
							$g = ($rgb >> 8) & 0xFF;
							$b = $rgb & 0xFF;
							$pcol = imagecolorallocate($dest, $r, $g, $b);
							imagesetpixel($dest, $dx, $dy, $pcol);
						}
						$dx ++;
					}
					$dx = 0;
					$dy ++;
				}
			} else {
				$dest = imagecreatetruecolor($ws, $hs);
				imagecopy($dest, $source, 0, 0, 0, 0, $ws, $hs);
			}
			imagedestroy($source);
			$destrs = imagecreatetruecolor($wd, $hd);
			$this->ImageCopyResampleBicubic($destrs, $dest, 0, 0, 0, 0, $wd, $hd, round($ws / $Z), round($hs / $Z));
			if ($out) {
				$this->_outputImage($destrs, $out, 100);
			}
			return $dest;
		}
		return $source;
	}

	function setOutputQuality($value) {
		$this->_outputQuality = $value;
	}

	function setOutputSize($width, $height) {
		if ($width > 0 && $height > 0) {
			$this->_outputWidth = $width;
			$this->_outputHeight = $height;
		}
	}

	private function calculateWatermarkPosition($imageSource, $imgWater) {
		switch ($this->_watermarkConfig ['position']) {
			case 'top-left' :
				$x = 0;
				$y = 0;
				break;
			case 'top-center' :
				$x = (imagesx($imageSource) / 2) - (imagesx($imgWater) / 2);
				$y = 0;
				break;
			case 'top-right' :
				$x = imagesx($imageSource) - imagesx($imgWater);
				;
				$y = 0;
				break;
			case 'center-left' :
				$x = 0;
				$y = (imagesy($imageSource) / 2) - (imagesy($imgWater) / 2);
				break;
			case 'center-center' :
				$x = (imagesx($imageSource) / 2) - (imagesx($imgWater) / 2);
				$y = (imagesy($imageSource) / 2) - (imagesy($imgWater) / 2);
				break;
			case 'center-right' :
				$x = imagesx($imageSource) - imagesx($imgWater);
				$y = (imagesy($imageSource) / 2) - (imagesy($imgWater) / 2);
				break;
			case 'bottom-left' :
				$x = 0; //imagesx($imageSource) -  imagesx($imgWater);
				$y = imagesy($imageSource) - imagesy($imgWater);
				break;
			case 'bottom-center' :
				$x = (imagesx($imageSource) / 2) - (imagesx($imgWater) / 2);
				$y = imagesy($imageSource) - imagesy($imgWater);
				break;
			case 'bottom-right' :
				$x = imagesx($imageSource) - imagesx($imgWater);
				$y = imagesy($imageSource) - imagesy($imgWater);
				break;
			case 'absolute' :
				$x = $this->_watermarkConfig ['top'];
				$y = $this->_watermarkConfig ['left'];
				break;
			case 'bottom-right' :
			default :
				$x = (imagesx($imageSource) - (imagesx($imgWater))) - 5;
				$y = (imagesy($imageSource)) - (imagesy($imgWater)) - 5;
				break;
		}
		return array(
				$x,
				$y);
		;
	}

	function ImageRectangleWithRoundedCorners(&$im, $x1, $y1, $x2, $y2, $radius, $color) {
		// Draw rectangle without corners
		ImageFilledRectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
		ImageFilledRectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
		// Draw circled corners
		ImageFilledEllipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
		ImageFilledEllipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
		ImageFilledEllipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
		ImageFilledEllipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
	}

	protected function alpha_blending($dest, $source, $dest_x, $dest_y) {
		for($y = 0; $y < imagesy($source); $y ++) {
			for($x = 0; $x < imagesx($source); $x ++) {
				$argb_s = imagecolorat($source, $x, $y);
				$argb_d = imagecolorat($dest, $x + $dest_x, $y + $dest_y);

				$a_s = ($argb_s >> 24) << 1; // 7 to 8 bits.
				$r_s = $argb_s >> 16 & 0xFF;
				$g_s = $argb_s >> 8 & 0xFF;
				$b_s = $argb_s & 0xFF;

				$r_d = $argb_d >> 16 & 0xFF;
				$g_d = $argb_d >> 8 & 0xFF;
				$b_d = $argb_d & 0xFF;

				// source pixel 100% opaque (alpha == 0)
				if ($a_s == 0) {
					$r_d = $r_s;
					$g_d = $g_s;
					$b_d = $b_s;
				} else if ($a_s > 253) {
					// source pixel 100% transparent (alpha == 255)
				// using source alpha only, we have to mix (100-"some") percent
				// of source with "some" percent of destination.
				} else {
					$r_d = (($r_s * (0xFF - $a_s)) >> 8) + (($r_d * $a_s) >> 8);
					$g_d = (($g_s * (0xFF - $a_s)) >> 8) + (($g_d * $a_s) >> 8);
					$b_d = (($b_s * (0xFF - $a_s)) >> 8) + (($b_d * $a_s) >> 8);
				}

				$rgb_d = imagecolorallocatealpha($dest, $r_d, $g_d, $b_d, 0);
				imagesetpixel($dest, $x, $y, $rgb_d);
			}
		}
	}

	private function renderWatermark(&$imageSource) {
		if ($this->_watermark) {
			//$this->ImageRectangleWithRoundedCorners($imageSource, 0, imagesy($imageSource) - 10, imagesx($imageSource), imagesy($imageSource), 2, imagecolorallocatealpha($imageSource, 0, 0, 0, 20));
			if ($this->_watermarkConfig ['sizePercent']) {
				$ww = imagesx($imageSource) * $this->_watermarkConfig ['sizePercent'] / 100;
				$wh = imagesy($imageSource) * $this->_watermarkConfig ['sizePercent'] / 100;
				$imgWater = $this->resizeImage($this->_watermark, null, $ww, $wh);
			} else {
				$imgWater = $this->getImage($this->_watermark);
			}
			list($top, $left) = $this->calculateWatermarkPosition($imageSource, $imgWater);
			imagesavealpha($imgWater, true);
			//$this->alpha_blending($imageSource,$imgWater,$top,$left);
			imagecopy($imageSource, $imgWater, $top, $left, 0, 0, imagesx($imgWater), imagesy($imgWater));
			imagedestroy($imgWater);
		}
		return $imageSource;
	}

	function toOutput() {
		list($oriW, $oriH) = getimagesize($this->_file);
		if (! $this->_outputWidth) {
			$this->_outputWidth = $oriW;
		}
		if (! $this->_outputHeight) {
			$this->_outputWidth = $oriH;
		}
		//$this->_outputWidth = $oriW;
		//$this->_outputWidth = $oriH;
		$tmpFile = tempnam('', 'img') . '.jpg';
		$imageSource = $this->resizeImage($this->_file, $tmpFile, $this->_outputWidth, $this->_outputHeight);
		$this->renderWaterMark($imageSource);
		$retval = $this->_outputImage($imageSource, $this->_outputFile, $this->_outputQuality);
		imagedestroy($imageSource);
		if (is_readable($tmpFile)) {
			unlink($tmpFile);
		}
		return $retval ? $this->_outputFile : null;
	}

	function blur(&$image) {
		$gaussian = array(
				array(
						1.0,
						2.0,
						1.0),
				array(
						2.0,
						4.0,
						2.0),
				array(
						1.0,
						2.0,
						1.0));
		CGAF::isFunctionExist('imageconvolution',true);
		imageconvolution($image, $gaussian, 16, 0);
		return;
	}

	protected function frand() {
		return 0.0001 * rand(0, 9999);
	}

	protected function drawRandomLines($image, $level,$lcolor=null) {
		$width = imagesx($image);
		$height = imagesy($image);
		for($i = 0; $i < $level; $i ++) {
			$color = $lcolor ? $lcolor : imagecolorallocate($image, mt_rand(0, 254), mt_rand(0, 254), mt_rand(0, 254));
			imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
		}

		for($line = 0; $line < $level; ++ $line) {
			$x = $width * (1 + $line) / ($level + 1);
			$x += (0.5 - $this->frand()) * $width / $level;
			$y = rand($width * 0.1, $height * 0.9);

			$theta = ($this->frand() - 0.5) * M_PI * 0.7;
			$w = $width;
			$len = rand($w * 0.4, $w * 0.7);
			$lwid = rand(0, 2);

			$k = $this->frand() * 0.6 + 0.2;
			$k = $k * $k * 0.5;
			$phi = $this->frand() * 6.28;
			$step = 0.5;
			$dx = $step * cos($theta);
			$dy = $step * sin($theta);
			$n = $len / $step;
			$amp = 1.5 * $this->frand() / ($k + 5.0 / $len);
			$x0 = $x - 0.5 * $len * cos($theta);
			$y0 = $y - 0.5 * $len * sin($theta);

			$ldx = round(- $dy * $lwid);
			$ldy = round($dx * $lwid);

			for($i = 0; $i < $n; ++ $i) {
				$x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
				$y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
				$color = $lcolor ? $lcolor :imagecolorallocate($image, mt_rand(0, 254), mt_rand(0, 254), mt_rand(0, 254));
				imagefilledrectangle($image, $x, $y, $x + $lwid, $y + $lwid, $color);
			}
		}
	}

	function saveTo($file) {
		$this->_outputFile = $file;
		if (is_file($this->_outputFile)) {
			if ($this->_overwrite) {
				@unlink($this->_outputFile);
			} else {
				return $this->_outputFile;
			}
		}
		return $this->toOutput();
	}
}

?>