<?php
namespace System\Documents;

use System\Exceptions\SystemException;
use Utils;

class Image extends \BaseObject implements IDocument
{
    private $_file;
    private $_watermark;
    private $_outputFile;
    private $_outputQuality = 80;
    private $_outputWidth;
    private $_outputHeight;
    private $_overwrite = false;
    private $_watermarkConfig = array(
        'sizePercent' => 20,
        'top' => null,
        'left' => null,
        'position' => 'bottom-right');

    function __construct($f = null)
    {
        parent::__construct();
        \System::loadExtenstion('gd');
        $this->_file = $f;
    }

    function getXmpData($chunk_size = 50000)
    {
        $buffer = NULL;

        if (($file_pointer = fopen($this->_file, 'r')) === FALSE) {
            throw new SystemException('Could not open file for reading');
        }
        $chunk = fread($file_pointer, $chunk_size);
        if (($posStart = strpos($chunk, '<x:xmpmeta')) !== FALSE) {
            $buffer = substr($chunk, $posStart);
            $posEnd = strpos($buffer, '</x:xmpmeta>');
            $buffer = substr($buffer, 0, $posEnd + 12);
        }
        fclose($file_pointer);
        return $buffer;
    }

    function setOverwrite($value)
    {
        $this->_overwrite = $value;
    }

    function addWatermark($p)
    {
        $this->_watermark = Utils::ToDirectory($p);
        return $this;
    }

    function setWatermarkSizePercent($value)
    {
        $this->_watermarkConfig['sizePercent'] = $value;
    }

    function setWatermarkPosition($pos)
    {
        $this->_watermarkConfig['position'] = $pos;
    }

    function setWatermarkLocation($top, $left)
    {
        $this->_watermarkConfig['top'] = $top;
        $this->_watermarkConfig['left'] = $left;
    }

    private function createImage($width, $height, $type)
    {
        $retval = imagecreatetruecolor($width, $height); // original image

        switch ($type) {
            case 'png':
                imagealphablending($retval, false);
                imagesavealpha($retval, true);
                $transparent = imagecolorallocatealpha($retval, 255, 255, 255, 127);
                imagefilledrectangle($retval, 0, 0, $width, $height, $transparent);
                break;
        }
        return $retval;
    }

    function getImage($file, $w = 0, $h = 0)
    {
        $ext = Utils::getFileExt($file, false);
        $exists = is_file($file);
        $im = null;
        switch ($ext) {
            case 'png':
                if ($exists) {
                    $im = @imagecreatefrompng($file);
                    imagesavealpha($im, true);
                } elseif ($w && $h) {
                    $im = imagecreatetruecolor($w, $h);
                    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
                    //imagealphablending($im, true);
                    imagesavealpha($im, true);
                    imagefill($im, 0, 0, $transparent);
                }

                break;
            case 'jpeg':
            case 'jpg':
                $im = @imagecreatefromjpeg($file);
                break;
            case 'gif':
                $im = imagecreatefromgif($file);
                break;
            default:
                ppd($ext);
        }
        if (!$im) {
            throw new SystemException('unable to open image' . $file);
        }
        return $im;
    }

    function imageCopyResampleBicubic(&$dst_img, &$src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
    {
        $scaleX = ($src_w - 1) / $dst_w;
        $scaleY = ($src_h - 1) / $dst_h;

        $scaleX2 = $scaleX / 2.0;
        $scaleY2 = $scaleY / 2.0;

        $tc = imageistruecolor($src_img);

        for ($y = $src_y; $y < $src_y + $dst_h; $y++) {
            $sY = $y * $scaleY;
            $siY = (int)$sY;
            $siY2 = (int)$sY + $scaleY2;

            for ($x = $src_x; $x < $src_x + $dst_w; $x++) {
                $sX = $x * $scaleX;
                $siX = (int)$sX;
                $siX2 = (int)$sX + $scaleX2;

                if ($tc) {
                    $c1 = imagecolorat($src_img, $siX, $siY2);
                    $c2 = imagecolorat($src_img, $siX, $siY);
                    $c3 = imagecolorat($src_img, $siX2, $siY2);
                    $c4 = imagecolorat($src_img, $siX2, $siY);

                    $r = (($c1 + $c2 + $c3 + $c4) >> 2) & 0xFF0000;
                    $g = ((($c1 & 0xFF00) + ($c2 & 0xFF00) + ($c3 & 0xFF00) + ($c4 & 0xFF00)) >> 2) & 0xFF00;
                    $b = ((($c1 & 0xFF) + ($c2 & 0xFF) + ($c3 & 0xFF) + ($c4 & 0xFF)) >> 2);

                    imagesetpixel($dst_img, $dst_x + $x - $src_x, $dst_y + $y - $src_y, $r + $g + $b);
                } else {
                    $c1 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX, $siY2));
                    $c2 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX, $siY));
                    $c3 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX2, $siY2));
                    $c4 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX2, $siY));

                    $r = ($c1['red'] + $c2['red'] + $c3['red'] + $c4['red']) << 14;
                    $g = ($c1['green'] + $c2['green'] + $c3['green'] + $c4['green']) << 6;
                    $b = ($c1['blue'] + $c2['blue'] + $c3['blue'] + $c4['blue']) >> 2;

                    imagesetpixel($dst_img, $dst_x + $x - $src_x, $dst_y + $y - $src_y, $r + $g + $b);
                }
            }
        }
    }

    private function _outputImage($img, $fout, $q)
    {
        $ext = Utils::getFileExt($fout, false);
        switch (strtolower($ext)) {
            case 'png':
                imagealphablending($img, true);
                return imagepng($img, $fout, ($q - 100) / 11.111111);
            case 'gif':
                return imagegif($img, $fout);
            case 'jpeg':
            case 'jpg':
                return imagejpeg($img, $fout, $q);
        }
        return false;
    }

    function resize($size, $f)
    {

        if (strpos($size, 'x') !== false) {
            $s = explode('x', $size);
            $w = $s[0];
            $h = $s[1];
        } else {
            $size = getimagesize($this->_file);
            $w = $size[0];
            $h = $size[1];
        }
        return $this->resizeImage($this->_file, $f, $w, $h);
    }


    function resizeImage($file, $out, $w, $h)
    {
        $source = $this->getImage($file);

        $ori = getimagesize($file);
        $ws = $ori[0];
        $hs = $ori[1];
        if ($w == $ws && $h == $hs) {
            if ($out) {
                return $this->_outputImage($source, $out, 100);
            }
            return $source;
        }
        \Utils::makeDir(dirname($out), 0750);
        \Utils::makeDir(dirname($out));
        $dest = $this->createImage($w, $h, \Utils::getFileExt($file, false));
        ImageCopyResampled($dest, $source, 0, 0, 0, 0, $w, $h, $ws, $hs); // do the resize in memory
        if ($out) {
            return $this->_outputImage($dest, $out, 100);
        }
        return $source;
    }

    function setOutputQuality($value)
    {
        $this->_outputQuality = $value;
    }

    function setOutputSize($width, $height)
    {
        if ($width > 0 && $height > 0) {
            $this->_outputWidth = $width;
            $this->_outputHeight = $height;
        }
    }

    private function calculateWatermarkPosition($imageSource, $imgWater)
    {
        switch ($this->_watermarkConfig['position']) {
            case 'top-left':
                $x = 0;
                $y = 0;
                break;
            case 'top-center':
                $x = (imagesx($imageSource) / 2) - (imagesx($imgWater) / 2);
                $y = 0;
                break;
            case 'top-right':
                $x = imagesx($imageSource) - imagesx($imgWater);;
                $y = 0;
                break;
            case 'center-left':
                $x = 0;
                $y = (imagesy($imageSource) / 2) - (imagesy($imgWater) / 2);
                break;
            case 'center-center':
                $x = (imagesx($imageSource) / 2) - (imagesx($imgWater) / 2);
                $y = (imagesy($imageSource) / 2) - (imagesy($imgWater) / 2);
                break;
            case 'center-right':
                $x = imagesx($imageSource) - imagesx($imgWater);
                $y = (imagesy($imageSource) / 2) - (imagesy($imgWater) / 2);
                break;
            case 'bottom-left':
                $x = 0; //imagesx($imageSource) -  imagesx($imgWater);
                $y = imagesy($imageSource) - imagesy($imgWater);
                break;
            case 'bottom-center':
                $x = (imagesx($imageSource) / 2) - (imagesx($imgWater) / 2);
                $y = imagesy($imageSource) - imagesy($imgWater);
                break;
            case 'bottom-right':
                $x = imagesx($imageSource) - imagesx($imgWater);
                $y = imagesy($imageSource) - imagesy($imgWater);
                break;
            case 'absolute':
                $x = $this->_watermarkConfig['top'];
                $y = $this->_watermarkConfig['left'];
                break;
            default:
                $x = (imagesx($imageSource) - (imagesx($imgWater))) - 5;
                $y = (imagesy($imageSource)) - (imagesy($imgWater)) - 5;
                break;
        }
        return array($x, $y);
    }

    function ImageRectangleWithRoundedCorners(&$im, $x1, $y1, $x2, $y2, $radius, $color)
    {
        // Draw rectangle without corners
        ImageFilledRectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        ImageFilledRectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        // Draw circled corners
        ImageFilledEllipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        ImageFilledEllipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        ImageFilledEllipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        ImageFilledEllipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity)
    {
        //$w = imagesx($src_im);
        //$h = imagesy($src_im);
        //$cut =  imagecreatetruecolor($src_w, $src_h);
        imagecopymerge($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
    }


    protected function alpha_blending($dest, $source, $dest_x, $dest_y)
    {
        for ($y = 0; $y < imagesy($source); $y++) {
            for ($x = 0; $x < imagesx($source); $x++) {
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

    private function renderWatermark(&$imageSource)
    {
        if ($this->_watermark) {
            //$this->ImageRectangleWithRoundedCorners($imageSource, 0, imagesy($imageSource) - 10, imagesx($imageSource), imagesy($imageSource), 2, imagecolorallocatealpha($imageSource, 0, 0, 0, 20));
            if ($this->_watermarkConfig['sizePercent']) {
                $ww = imagesx($imageSource) * $this->_watermarkConfig['sizePercent'] / 100;
                $wh = imagesy($imageSource) * $this->_watermarkConfig['sizePercent'] / 100;
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

    function loadFile($f)
    {
        $this->_file = $f;
    }

    function Render($return = false)
    {
        return $this->toOutput($return);
    }

    function toOutput($returnSource = false)
    {
        if ($this->_file && is_file($this->_file)) {
            list($oriW, $oriH) = getimagesize($this->_file);
            if (!$this->_outputWidth) {
                $this->_outputWidth = $oriW;
            }
            if (!$this->_outputHeight) {
                $this->_outputHeight = $oriH;
            }
        }
        //$this->_outputWidth = $oriW;
        //$this->_outputWidth = $oriH;
        $tmpFile = tempnam('', 'img') . \Utils::getFileExt($this->_file, true);
        $imageSource = $this->resizeImage($this->_file, null, $this->_outputWidth, $this->_outputHeight);
        $this->renderWaterMark($imageSource);
        $retval = $this->_outputImage($imageSource, $this->_outputFile ? $this->_outputFile : $tmpFile, $this->_outputQuality);
        if ($returnSource) {
            return $imageSource;
        }
        imagedestroy($imageSource);
        if (is_readable($tmpFile)) {
            unlink($tmpFile);
        }
        return $retval ? $this->_outputFile : null;
    }

    function toBase64()
    {
        /*$ou = $this->getImage($this->_file);
        ppd($this->_file);
        if ($ou) {
            ob_start ();
            imagepng ($ou);
            $image_data = ob_get_contents ();
            ob_end_clean ();
            imagedestroy($ou);

        }
        ppd($ou);*/
        if (!$this->_file) ppd($this);
        return 'data:image/' . \Utils::getFileExt($this->_file, false) . ';base64,' . base64_encode(file_get_contents($this->_file));
    }

    function blur(&$image)
    {
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
        imageconvolution($image, $gaussian, 16, 0);
        return;
    }

    protected function frand()
    {
        return 0.0001 * rand(0, 9999);
    }

    protected function drawRandomLines($image, $level, $lcolor = null)
    {
        if (is_object($lcolor)) {
            $lcolor = imagecolorallocate($image, $lcolor->r, $lcolor->g, $lcolor->b);
        }
        $width = imagesx($image);
        $height = imagesy($image);
        for ($i = 0; $i < $level; $i++) {
            $color = $lcolor ? $lcolor : imagecolorallocate($image, mt_rand(0, 254), mt_rand(0, 254), mt_rand(0, 254));
            imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $color);
        }
        for ($line = 0; $line < $level; ++$line) {
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
            //$ldx = round(-$dy * $lwid);
            //$ldy = round($dx * $lwid);
            for ($i = 0; $i < $n; ++$i) {
                $x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
                $y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
                $color = $lcolor ? $lcolor : imagecolorallocate($image, mt_rand(0, 254), mt_rand(0, 254), mt_rand(0, 254));
                imagefilledrectangle($image, $x, $y, $x + $lwid, $y + $lwid, $color);
            }
        }
    }

    function getSize()
    {
        return getimagesize($this->_file);
    }

    function saveTo($file)
    {
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