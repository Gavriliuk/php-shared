<?php

/*
[IMAGETYPE_UNKNOWN] => 0
[IMAGETYPE_GIF] => 1
[IMAGETYPE_JPEG] => 2
[IMAGETYPE_PNG] => 3
[IMAGETYPE_SWF] => 4
[IMAGETYPE_PSD] => 5
[IMAGETYPE_BMP] => 6
[IMAGETYPE_TIFF_II] => 7
[IMAGETYPE_TIFF_MM] => 8
[IMAGETYPE_JPC] => 9
[IMAGETYPE_JPEG2000] => 9
[IMAGETYPE_JP2] => 10
[IMAGETYPE_JPX] => 11
[IMAGETYPE_JB2] => 12
[IMAGETYPE_SWC] => 13
[IMAGETYPE_IFF] => 14
[IMAGETYPE_WBMP] => 15
[IMAGETYPE_XBM] => 16
[IMAGETYPE_ICO] => 17
[IMAGETYPE_COUNT] => 18
*/

class image
{
 private $image = null;
 private $type = IMAGETYPE_UNKNOWN;
 private $width = 0;
 private $height = 0;

 function __destruct()
 {
  if ($this->image)
   imagedestroy($this->image);
 }

 function loadFromFile($uri)
 {
  $info = getimagesize($uri);
  if ($info === false)
   return false;
  $this->type = $info[2];
  $this->width = $info[0];
  $this->height = $info[1];
  if ($this->type == IMAGETYPE_GIF)
   $this->image = imagecreatefromgif($uri);
  else if ($this->type == IMAGETYPE_JPEG)
   $this->image = imagecreatefromjpeg($uri);
  else if ($this->type == IMAGETYPE_PNG)
   $this->image = imagecreatefrompng($uri);
  else if ($this->type == IMAGETYPE_WBMP)
   $this->image = imagecreatefromwbmp($uri);
  if (!$this->image)
  {
   $this->type = IMAGETYPE_UNKNOWN;
   return false;
  }
  return true;
 }

 function loadFromData($data)
 {
  if (function_exists('getimagesizefromstring'))
  {
   $info = getimagesizefromstring($data);
   if ($info === false)
    return false;
   $this->type = $info[2];
   $this->width = $info[0];
   $this->height = $info[1];
  }
  else
  {
   $this->type = IMAGETYPE_PNG;
  }
  $this->image = imagecreatefromstring($data);
  if (!$this->image)
  {
   $this->type = IMAGETYPE_UNKNOWN;
   return false;
  }
  if (!$this->width || !$this->height)
  {
   $this->width = imagesx($this->image);
   $this->height = imagesy($this->image);
  }
  return true;
 }

 function writeToStream()
 {
  if (!$this->type)
   return false;
  if ($this->type == IMAGETYPE_GIF)
   return imagegif($this->image) !== false;
  if ($this->type == IMAGETYPE_JPEG)
   return imagejpeg($this->image) !== false;
  else if ($this->type == IMAGETYPE_PNG)
   return imagepng($this->image) !== false;
  else if ($this->type == IMAGETYPE_WBMP)
   return imagewbmp($this->image) !== false;
  return false;
 }

 function writeToFile($filename)
 {
  if (!$this->type)
   return false;
  if ($this->type == IMAGETYPE_GIF)
   return imagegif($this->image, $filename) !== false;
  if ($this->type == IMAGETYPE_JPEG)
   return imagejpeg($this->image, $filename) !== false;
  else if ($this->type == IMAGETYPE_PNG)
   return imagepng($this->image, $filename) !== false;
  else if ($this->type == IMAGETYPE_WBMP)
   return imagewbmp($this->image, $filename) !== false;
  return false;
 }

 function getData()
 {
  if (!$this->type)
   return false;
  ob_start();
  $this->writeToStream();
  $result = ob_get_contents();
  ob_end_clean();
  return $result;
 }

 function type($type = null)
 {
  if (!$this->image)
   return IMAGETYPE_UNKNOWN;
  if ($type)
  {
   if (array_search($type, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WBMP)) !== false)
    $this->type = $type;
   else
    $this->type = IMAGETYPE_UNKNOWN;
  }
  return $this->type;
 }
 
 function mimetype()
 {
  return image_type_to_mime_type($this->type);
 }

 function width($width = null)
 {
  if (!$this->type)
   return 0;
  if ($width)
   if (!$this->resize($width, $width * $this->height / $this->width))
    return false;
  return $this->width;
 }

 function height($height = null)
 {
  if (!$this->type)
   return 0;
  if ($height)
   if (!$this->resize($height * $this->width / $this->height, $height))
    return false;
  return $this->height;
 }

 function scale($scale)
 {
  if (!$this->type)
   return false;
  return $this->resize($this->width * $scale, $this->height * $scale);
 }

 function resize($width, $height)
 {
  if (!$this->type)
   return false;
  $image = imagecreatetruecolor($width, $height);
  if (!$image)
   return false;
  //exit('Here ' . $height . 'x' . $width);
  // imagecopyresized or imagecopyresampled?
  if (!imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height))
   return false;
  //exit('Here ' . $height . 'x' . $width);
  imagedestroy($this->image);
  $this->image = $image;
  $this->width = imagesx($this->image);
  $this->height = imagesy($this->image);
  //exit('Here ' . $this->height . 'x' . $this->width);
  return true;
 }

 static function resizeImageWidth($file_in, $file_out, $width, $bigOnly = true)
 {
  $image = new self();
  if (!$image->loadFromFile($file_in))
   return null;
  if (($image->width() != $width) && (!$bigOnly || ($image->width() > $width)))
   if (!$image->width($width))
    return null;
  if (!$image->writeToFile($file_out))
   return null;
  return $image;
 }

 static function resizeImageHeight($file_in, $file_out, $height, $bigOnly = true)
 {
  $image = new self();
  if (!$image->loadFromFile($file_in))
   return null;
  if (($image->height() != $height) && (!$bigOnly || ($image->height() > $height)))
   if (!$image->height($height))
    return null;
  if (!$image->writeToFile($file_out))
   return null;
  return $image;
 }

}

?>
