<?php
use System\Exceptions\SystemException;
use System\Exceptions\IOException;

abstract class ImageUtils
{
    public static function toBase64($file)
    {
        $finfo = new FileInfo($file);
        $mime = $finfo->Mime;
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }

    public static function drawWatermark($image, $watermark, $dest = null, $padding = 0)
    {
        if (!extension_loaded('Imagick')) {
            throw new SystemException ('error.extension.notloaded', 'Imagick');
        }
        if (!$watermark || !$image) {
            throw new SystemException ('Invalid Parameter');
        }
        if (is_string($image)) {
            $dest = $dest ? $dest : $image;
            if (!is_file($image)) {
                throw new IOException ('error.file.notfound', Logger::WriteDebug($image));
            }
            $image = new \Imagick ($image);
        }
        if (!$dest) {
            throw new SystemException ('Invalid Parameter');
        }
        if (is_string($watermark)) {
            if (!$watermark || !is_file($watermark)) {
                throw new IOException ('error.file.notfound', Logger::WriteDebug($watermark));
            }
            $watermark = new \Imagick ($watermark);
        }
        // Check if the watermark is bigger than the image
        $image_width = $image->getImageWidth();
        $image_height = $image->getImageHeight();
        $watermark_width = $watermark->getImageWidth();
        $watermark_height = $watermark->getImageHeight();

        if ($image_width < $watermark_width + $padding || $image_height < $watermark_height + $padding) {
            return false;
        }

        // Calculate each position
        $positions = array();
        $positions [] = array(
            0 + $padding,
            0 + $padding
        );
        $positions [] = array(
            $image_width - $watermark_width - $padding,
            0 + $padding
        );
        $positions [] = array(
            $image_width - $watermark_width - $padding,
            $image_height - $watermark_height - $padding
        );
        $positions [] = array(
            0 + $padding,
            $image_height - $watermark_height - $padding
        );

        // Initialization
        $min = null;
        $min_colors = 0;

        // Calculate the number of colors inside each region
        // and retrieve the minimum
        foreach ($positions as $position) {
            $colors = $image->getImageRegion($watermark_width, $watermark_height, $position [0], $position [1])->getImageColors();

            if ($min === null || $colors <= $min_colors) {
                $min = $position;
                $min_colors = $colors;
            }
        }
        $watermark->setimageopacity(0.2);
        // Draw the watermark
        $image->compositeImage($watermark, Imagick::COMPOSITE_OVERLAY, $min [0], $min [1]);
        $image->setImageFormat("png");
        $image->writeImage($dest);
        return true;
    }

    public static function ImageError($message,$w=800,$h=300,$showdebug = true)
    {

        /* get all of the required data from the HTTP request */
        $document_root = $_SERVER['DOCUMENT_ROOT'];
        $requested_uri = parse_url(urldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        $requested_file = basename($requested_uri);
        $source_file = $document_root . $requested_uri;

        if (!\Request::isMobile()) {
            $is_mobile = "FALSE";
        } else {
            $is_mobile = "TRUE";
        }

        $im = ImageCreateTrueColor($w, $h);
        $colourBlack = imagecolorallocate($im, 0, 0, 0);
        imagecolortransparent($im, $colourBlack);

        $text_color = ImageColorAllocate($im, 233, 14, 91);
        $message_color = ImageColorAllocate($im, 91, 112, 233);
        $l = 20;
        $font =3;
        if (!$showdebug) {
            $font_width = ImageFontWidth($font);
            $font_height = ImageFontHeight($font);
            $text_width = $font_width * strlen($message);
            $position_center = ceil(($w - $text_width) / 2);
            $text_height = $font_height;
            $position_middle = ceil(($h - $text_height) / 2);
            ImageString($im, 3, $position_center, $position_middle, $message, $message_color);
        }else{
            ImageString($im, 3, 5, 25, $message, $message_color);
        }
        if (CGAF_DEBUG && $showdebug) {
            $msgs = array("Potentially useful information:",
                "DOCUMENT ROOT IS: $document_root",
                "REQUESTED URI WAS: $requested_uri",
                "REQUESTED FILE WAS: $requested_file",
                "SOURCE FILE IS: $source_file",
                "DEVICE IS MOBILE? $is_mobile"
            );
            foreach($msgs as $m){
                $l+=20;
                ImageString($im, 5, 5, $l, $m, $text_color);

            }
        }
        header("Cache-Control: no-store");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 1000) . ' GMT');
        header('Content-Type: image/png');
        imagepng($im);
        ImageDestroy($im);
        \CGAF::doExit();

    }

    public static function resizeImage($image, $size, $outputPath,$force=false)
    {
        $img = new \System\Documents\Image($image);
        $fname= $outputPath .DS.\Utils::getFileName($image).'_'.$size.\Utils::getFileExt($image);
        if (!$force && is_file($fname)){
            return $fname;
        }
        if ($img->resize($size,$fname)) {
            return $fname;
        }
        return null;
    }

    public static function WatermarkPath($dir, $watermarkFile)
    {
        $files = \Utils::getDirFiles($dir,$dir);
        foreach($files as $file){
            self::drawWatermark($file,$watermarkFile);
        }
    }
}