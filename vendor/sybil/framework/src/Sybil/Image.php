<?php
/*
 * Image management class
 *
 * Loading, resizing/croping and saving
 *
 * (c) 2013 Eventviva
 * https://github.com/eventviva/php-image-resize
 */

namespace Sybil;

final class Image {
    protected $image;
    protected $image_type;

    /*
	 * Class constructor
	 *
	 * @param array $filename $_FILE object
	 * 
	 * @return object This object
	 */

    public function __construct($filename) {
        $this->load($filename);
    }
    
    /*
	 * Loading an image
	 *
	 * @param array $filename $_FILE object
	 * 
	 * @return object This object
	 */

    public function load($filename) {
	    $filename = isset($filename['tmp_name']) ? $filename['tmp_name'] : $filename;
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if ($this->image_type == IMAGETYPE_JPEG) {

            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->image_type == IMAGETYPE_GIF) {

            $this->image = imagecreatefromgif($filename);
        } elseif ($this->image_type == IMAGETYPE_PNG) {

            $this->image = imagecreatefrompng($filename);
        }
        return $this;
    }

    /*
	 * Saving an image
	 *
	 * @param object $filename $_FILE object
	 * @param string $image_type Image type
	 * @param integer $compression Compression value
	 * @param array $permissions Permissions for the image file
	 * 
	 * @return object This object
	 */

    public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null) {
        $image_type = $this->image_type;
        
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image, $filename, $compression);
        } elseif ($image_type == IMAGETYPE_GIF) {

            imagegif($this->image, $filename);
        } elseif ($image_type == IMAGETYPE_PNG) {

            imagealphablending($this->image, false);
            imagesavealpha($this->image, true);
            imagepng($this->image, $filename);
        }
        if ($permissions != null) {

            chmod($filename, $permissions);
        }
        return $this;
    }
    
    /*
	 * Outputing an image
	 *
	 * @param string $image_type Image type
	 */

    public function output($image_type = IMAGETYPE_JPEG) {

        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image);
        } elseif ($image_type == IMAGETYPE_GIF) {

            imagegif($this->image);
        } elseif ($image_type == IMAGETYPE_PNG) {

            imagepng($this->image);
        }
    }
    
    /*
	 * Getting image width
	 * 
	 * @return integer With of the image
	 */

    public function getWidth() {

        return imagesx($this->image);
    }
    
    /*
	 * Getting image height
	 * 
	 * @return integer Height of the image
	 */

    public function getHeight() {

        return imagesy($this->image);
    }
    
    /*
	 * Changing image height
	 * 
	 * @param integer $height New height
	 *
	 * @return object This object
	 */

    public function resizeToHeight($height) {

        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
        return $this;
    }
    
    /*
	 * Changing image width
	 * 
	 * @param integer $width New width
	 *
	 * @return object This object
	 */

    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);
        return $this;
    }
    
    /*
	 * Scaling the image
	 * 
	 * @param integer $scale The scale
	 *
	 * @return object This object
	 */

    public function scale($scale) {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        $this->resize($width, $height);
        return $this;
    }

    /*
	 * Resizing the image
	 * 
	 * @param integer $width The new width
	 * @param integer $height The new height
	 * @param bool $forcesize Force the resizing if image is smaller
	 *
	 * @return object This object
	 */

    public function resize($width, $height, $forcesize = false) {
        /* optional. if file is smaller, do not resize. */
        if ($forcesize === false) {
            if ($width > $this->getWidth() && $height > $this->getHeight()) {
                $width = $this->getWidth();
                $height = $this->getHeight();
            }
        }

        $new_image = imagecreatetruecolor($width, $height);
        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type == IMAGETYPE_GIF) || ($this->image_type == IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());

        $this->image = $new_image;
        return $this;
    }
    
    /*
	 * Center cropping
	 * 
	 * @param integer $width The new width
	 * @param integer $height The new height
	 *
	 * @return object This object
	 */
    
    public function crop($width,$height){
    	$aspect_o = $this->getWidth()/$this->getHeight();
    	$aspect_f = $width/$height;
    
    	if($aspect_o>=$aspect_f){
    		$width_n=$this->getWidth() / ($this->getHeight()/$height);
    		$height_n=$height;
    	}else{
    		$width_n=$width;
    		$height_n=$this->getHeight() / ($this->getWidth()/$width);
    	}
    
        $new_image = imagecreatetruecolor($width, $height);
        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type == IMAGETYPE_GIF) || ($this->image_type == IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }
        imagecopyresampled($new_image, $this->image, 0 - ($width_n - $width)*0.5, 0 - ($height_n - $height)*0.5, 0, 0, $width_n, $height_n, $this->getWidth(), $this->getHeight());
        
        $this->image = $new_image;
        return $this;
    }
 
}