<?php
  
  function imageCrop($image, $newcopy) {
                     
    //getting the image dimensions
    list($width, $height) = getimagesize($image); 
                     
    //create image from the jpeg
    $img = imagecreatefromjpeg($image); 
    
    //get black background of same width and height to copy image to
    $tci = imagecreatetruecolor($width, $height - 31);
    
    //$place image on background minus crop amount
    imagecopyresampled($tci, $img, 0, -31, 0, 0, $width, $height, $width, $height);
    
    //actually creates the new image blended together
    imagejpeg($tci, $newcopy, 100); 
    
    //since these are not needed anymore, destroy them
    imagedestroy($img); 
  }  
 
   if ($handle = opendir('uncropped')) {
    echo "Directory handle: $handle<br />";
    echo "Entries:<br />";
    
    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle))) {
        $path_suffix = pathinfo($entry);
        if (preg_match("/(gif|jpg|png|jpeg)$/i", $path_suffix['extension'])) {
            echo $entry . '<br />';
            $target_file = "uncropped/$entry";
            $cropped_file = "cropped/$entry"; 
            imageCrop($target_file, $cropped_file);
        }
    }
    
  }
?>