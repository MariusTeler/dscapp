<?php

class Thumbnail {
    public $errmsg	    = "";
    public $error	    = false;
    public $format	    = "";
    public $file	    = "";
    public $max_width  = 0;
    public $max_height = 0;
    public $percent    = 0;

    function Thumbnail($file, $max_width = 0, $max_height = 0, $percent = 0) {
	if (!file_exists($file)) {
	    $this->errmsg = "File doesn't exists";
	    $this->error  = true;
	}
	else if (!is_readable($file)) {
	    $this->errmsg = "File is not readable";
	    $this->error  = true;
	}

	if (strstr(strtolower($file), ".gif"))
	    $this->format = "GIF";
	else if (strstr(strtolower($file), ".jpg") ||
		 strstr(strtolower($file), ".jpeg"))
	    $this->format = "JPEG";
	else if (strstr(strtolower($file), ".png"))
	    $this->format = "PNG";
	else {
	    $this->errmsg = "Unknown file format";
	    $this->error  = true;
	}

	if ($max_width == 0 && $max_height == 0 && $percent == 0)
	    $percent = 100;

	$this->max_width  = $max_width;
	$this->max_height = $max_height;
	$this->percent	  = $percent;
	$this->file	  = $file;
    }

    function calc_width($width, $height) {
	$new_width  = $this->max_width;
	$new_wp     = (100 * $new_width) / $width;
	$new_height = ($height * $new_wp) / 100;
	return array($new_width, $new_height);
    }

    function calc_height($width, $height) {
	$new_height = $this->max_height;
	$new_hp     = (100 * $new_height) / $height;
	$new_width  = ($width * $new_hp) / 100;
	return array($new_width, $new_height);
    }

    function calc_percent($width, $height) {
	$new_width  = ($width * $this->percent) / 100;
	$new_height = ($height * $this->percent) / 100;
	return array($new_width, $new_height);
    }

    function return_value($array) {
	$array[0] = intval($array[0]);
	$array[1] = intval($array[1]);
	return $array;
    }

    function calc_image_size($width, $height) {
	$new_size = array($width, $height);

	if ($this->max_width > 0) {
	    $new_size = $this->calc_width($width, $height);

	    if ($this->max_height > 0) {
		if ($new_size[1] > $this->max_height)
		    $new_size = $this->calc_height($new_size[0], $new_size[1]);
	    }

	    return $this->return_value($new_size);
	}

	if ($this->max_height > 0) {
	    $new_size = $this->calc_height($width, $height);
	    return $this->return_value($new_size);
	}

	if ($this->percent > 0) {
	    $new_size = $this->calc_percent($width, $height);
	    return $this->return_value($new_size);
	}
    }

    function show_error_image() {
	header("Content-type: image/png");
	$err_img   = ImageCreate(220, 25);
	$bg_color  = ImageColorAllocate($err_img, 0, 0, 0);
	$fg_color1 = ImageColorAllocate($err_img, 255, 255, 255);
	$fg_color2 = ImageColorAllocate($err_img, 255, 0, 0);
	ImageString($err_img, 3, 6, 6, "ERROR:", $fg_color2);
	ImageString($err_img, 3, 55, 6, $this->errmsg, $fg_color1);
	ImagePng($err_img);
	ImageDestroy($err_img);
    }

    function show($text = '', $text_size = 10, $text_font = 'arial.ttf', $text_x = 0, $text_y = 0, $text_color1 = 0, $text_color2 = 0, $text_color3 = 0) {
	if ($this->error) {
	    $this->show_error_image();
	    return;
	}

	$size      = GetImageSize($this->file);
	$new_size  = $this->calc_image_size($size[0], $size[1]);
	#
	# Good idea from Mariano Cano P�rez
	# Requires GD 2.0.1 (PHP >= 4.0.6)
	#
	if (function_exists("ImageCreateTrueColor"))
	    $new_image = ImageCreateTrueColor($new_size[0], $new_size[1]);
	else
	    $new_image = ImageCreate($new_size[0], $new_size[1]);

	switch ($this->format) {
	    case "GIF":
		$old_image = ImageCreateFromGif($this->file);
		break;
	    case "JPEG":
		$old_image = ImageCreateFromJpeg($this->file);
		break;
	    case "PNG":
		$old_image = ImageCreateFromPng($this->file);
		break;
	}
	imagealphablending($new_image, false);
	imagesavealpha($new_image, true);

	ImageCopyResized($new_image, $old_image, 0, 0, 0, 0, $new_size[0], $new_size[1], $size[0], $size[1]);
	
	// write text on image
	if($text != '') {
		$black = imagecolorallocate($new_image, $text_color1, $text_color2, $text_color3);
		imagettftext($new_image, $text_size, 0, $text_x, $text_y, $black, 'fonts/' . $text_font, $text);
	}
	
	switch ($this->format) {
	    case "GIF":
		header("Content-type: image/gif");
		ImageGif($new_image);
		break;
	    case "JPEG":
		header("Content-type: image/jpeg");
		ImageJpeg($new_image);
		break;
	    case "PNG":
		header("Content-type: image/png");
		ImagePng($new_image);
		break;
	}

	ImageDestroy($new_image);
	ImageDestroy($old_image);
	return;
    }
}
?>
