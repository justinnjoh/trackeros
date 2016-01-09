<?php
// GD2 graphics library ... mainly for resizing images

class GD2_Lib {

  //current image: these variables hold information about the image being procedded
  public $image_mime = "";
  public $image_width = 0;
  public $image_height = 0;
  public $image_bits = 0;

  public $image_info = array(); // orginal image info - all info from getimagesize() function
  public $new_image_info = array(); // new image info - all info from getimagesize() function

  private $image = null; // this is the current image handle
  // current image ends

  public $error = null; // non-null means there was an error


  public function __construct($file_name = null) {
    // full file path is expected
    is_null($file_name) && $file_name = "";

    if ( strlen($file_name) > 0 && is_readable($file_name) ) {
      $this->image_info = getimagesize($file_name);

      $count = count($this->image_info);
      $count > 0 && $this->image_width = $this->image_info[0];
      $count > 1 && $this->image_height = $this->image_info[1];
      array_key_exists('mime', $this->image_info) && $this->image_mime = $this->image_info['mime'];
      array_key_exists('bits', $this->image_info) && $this->image_bits = $this->image_info['bits'];

      $this->image = $this->open_image($file_name);
    }
    else {
      $this->error = "File [" . $file_name . "] could not be found"; 
    }
  }	

  public function resize ($width = null, $height = null, $quality = null, $save_to = null) {
    // if width and height not supplied then there's nothing to do;
    // if a supplied dimension if greater than the ACTUAL image dimension, set it to the ACTUAL image dimension (STOPS IMAGES FROM STRETCHING)
    // if only one dimension is supplied work out the other as a ratio of the given NEW dimension and the dimension of the original image 
    // ALWAYS work out one side, to maintain proportions
    is_null($width) && $width = 0; 
    is_null($height) && $height = 0; 

    // stop length from stretching
    $this->image_width < $width && $width = $this->image_width; 

    // stop height from stretching
    $this->image_height < $height && $height = $this->image_height; 


    if ($height > 0 && $width > 0) {
      // both supplied, so set shorter side to 0 so it can be worked out
      if ( $this->image_height > $this->image_width ) {
        $width = 0;
      }
      else {
        // square or landscape
        $height = 0;
      }
    }

    // work out one side from the other
    $width < 1 && $height > 0 && $width = $height * ($this->image_width / $this->image_height);
    $height < 1 && $width > 0 && $height = $width * ($this->image_height / $this->image_width);

    if ( $width > 0 && $height > 0 ) {
      // both sides given so, re-size
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->image_width, $this->image_height);
      $this->save_image($new_image, $save_to, $quality);

      imagedestroy($new_image);
    }
  }

  public function save_image ($image = null, $save_path = null, $quality = null) {
    // save path is the location and file name to save to, including full path
    is_null($save_path) && $save_path = "";

    $ext = ""; 
    strlen($save_path) > 0 && $ext = strtolower(strrchr($save_path, '.'));

    if ( strlen($ext) > 0 && !is_null($this->image) && !is_null($image) ) {
      // image can be saved
      switch ($ext) {

        case '.jpg':
        case '.jpeg':
          is_null($quality) && $quality = "90";

          if (imagetypes() & IMG_JPG) {
            imagejpeg($image, $save_path, $quality);
          }
          break;

        case '.gif':
          if (imagetypes() & IMG_GIF) {
            imagegif($image, $save_path);
          }
          break;

        case '.png':
          //is_null($quality) && $quality = "0";
          $quality = 0;

          if (imagetypes() & IMG_PNG) {
            imagepng($image, $save_path, $quality);
          }
          break;


        default:
          $this->error = "The file type you wish to save as is not supported [" . $save_path . "]";

      } // end swith

      // find new image size
      $this->new_image_info = getimagesize($save_path);

    }
  }




  private function open_image($file_name = null) {
    // opens the image ready for processing and returns the handle, or null 
    // it is presumed that $image_name has been validated and meta info about it already loaded into class variabls

    $image = null;
    switch ($this->image_mime) {
      case 'image/jpeg':
        $image = imagecreatefromjpeg($file_name);
        break;

      case 'image/gif':
        $image = imagecreatefromgif($file_name);
        break;

      case 'image/png':
        $image = imagecreatefrompng($file_name);
        break;

      case 'image/wbmp':
        $image = imagecreatefromwbmp($file_name);
        break;

      default:
        //$this->error = "This file type is not supported [" . $file_name . " - " . $this->image_mime . "]";
        $this->error = "This file type is not supported [" . $file_name . "]";

    } // end switch

    return ($image);
  }


}

?>
