<?php
use System\Exceptions\SystemException;
use System\Exceptions\IOException;
abstract class ImageUtils {
	
	public static function drawWatermark($image, $watermark, $dest = null, $padding = 0) {
		if (! extension_loaded ( 'Imagick' )) {
			throw new SystemException ( 'error.extension.notloaded', 'Imagick' );
		}
		if (! $watermark || ! $image) {
			throw new SystemException ( 'Invalid Parameter' );
		}
		if (is_string ( $image )) {
			$dest = $dest ? $dest : $image;
			if (! is_file ( $image )) {
				throw new IOException ( 'error.file.notfound', Logger::WriteDebug ( $image ) );
			}
			$image = new Imagick ( $image );
		}
		if (! $dest) {
			throw new SystemException ( 'Invalid Parameter' );
		}
		if (is_string ( $watermark )) {
			if (! $watermark || ! is_file ( $watermark )) {
				throw new IOException ( 'error.file.notfound', Logger::WriteDebug ( $watermark ) );
			}
			$watermark = new Imagick ( $watermark );
		}
		// Check if the watermark is bigger than the image
		$image_width = $image->getImageWidth ();
		$image_height = $image->getImageHeight ();
		$watermark_width = $watermark->getImageWidth ();
		$watermark_height = $watermark->getImageHeight ();
		
		if ($image_width < $watermark_width + $padding || $image_height < $watermark_height + $padding) {
			return false;
		}
		
		// Calculate each position
		$positions = array ();
		$positions [] = array (
				0 + $padding, 
				0 + $padding 
		);
		$positions [] = array (
				$image_width - $watermark_width - $padding, 
				0 + $padding 
		);
		$positions [] = array (
				$image_width - $watermark_width - $padding, 
				$image_height - $watermark_height - $padding 
		);
		$positions [] = array (
				0 + $padding, 
				$image_height - $watermark_height - $padding 
		);
		
		// Initialization
		$min = null;
		$min_colors = 0;
		
		// Calculate the number of colors inside each region
		// and retrieve the minimum
		foreach ( $positions as $position ) {
			$colors = $image->getImageRegion ( $watermark_width, $watermark_height, $position [0], $position [1] )->getImageColors ();
			
			if ($min === null || $colors <= $min_colors) {
				$min = $position;
				$min_colors = $colors;
			}
		}
		// $watermark->flatenImage();
		// ppd(get_class_methods(get_class($watermark)));
		// $watermark->setimageopacity( 1 );
		// $watermark->adaptiveResizeImage($image_width,$image_height,true);
		$watermark->setimageopacity ( 0.4 );
		// $image->comment('www.alkisahonline.com');
		// Draw the watermark
		$image->compositeImage ( $watermark, Imagick::COMPOSITE_OVER, $min [0], $min [1] );
		
		$image->setImageFormat ( "png" );
		$image->writeImage ( $dest );
		return true;
	}
}