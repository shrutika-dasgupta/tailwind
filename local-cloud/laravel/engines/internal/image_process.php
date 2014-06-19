<?php
/**
 * @author Alex
 * Date: 9/4/13 2:37 PM
 * 
 */

ini_set('memory_limit', '5000M');
ini_set('max_execution_time', '180');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';
$conn = DatabaseInstance::mysql_connect();


	$arguments = getopt("a:b:");


	$domain = $arguments['a'];
	$start = $arguments['b'];


	if(!isset($start)){
        $start = 0;
    }


//	$start = 0;
//	$domain = "theoutnet.com";

	if(isset($domain)) {



        $pin_images = array();
        $acc2 = "select pin_id, image_square_url, image_id from data_pins_new where domain=\"$domain\" order by created_at desc limit $start, 50";

        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error());

        while ($p = mysql_fetch_array($acc2_res)) {

            $pin_id = $p['pin_id'];

            $pin_images["$pin_id"] = array();
            $pin_images["$pin_id"]['pin_id'] = $pin_id;
            $pin_images["$pin_id"]['image'] = $p['image_square_url'];
            $pin_images["$pin_id"]['image_id'] = $p['image_id'];

        }

        sleep(1);

        $process_time = 0;
        $process_count = 0;
        $no_image_count = 0;

        foreach($pin_images as $p){

            if(!($p['image_id'] > 0)){
                $img = $p['image'];
                $pin_id = $p['pin_id'];

                //check if there's a square image url given for this pin
                if($img){

                    print $pin_id . "<br>";
                    $start1 = microtime(true);
                    $end1 = microtime(true);
                    $create_time += ($end1 - $start1);

                    //process the pin image and return the image key
                    $img_key = processImageAlt($img);

                    $time = time();

                    //write image_id to the database
                    $sql = "UPDATE data_pins_new set image_id = \"$img_key\" WHERE pin_id = \"$pin_id\"";
                    $resu = mysql_query($sql, $conn);

                    if($img_key==3){
                        $no_image_count++;
                    }

                    $end1 = microtime(true);
                    $create_time = ($end1 - $start1);
                    $process_count++;
                    $process_time += $create_time;
                }
            }
        }
    }


	$process_avg = number_format(($process_time / $process_count),5);

		$sql = "INSERT into status_image_process_log (domain, total_time, count, avg_time, missing_count, timestamp) values
			(\"$domain\", \"$process_time\", \"$process_count\", \"$process_avg\", \"$no_image_count\", \"$time\")";
		$resu = mysql_query($sql, $conn);





function processImageAlt($img){

    list($width1, $height1) = getimagesize($img);

    if(!isset($width1) || !isset($height1)){
        return 3;
    } else {


        $img_im1 = ImageCreateFromJpeg($img);

        $height = 16;
        $width = 16;

        if($height==240){
            $temp_image = imagecreatetruecolor($width1, 239);
            imagecopyresampled($temp_image, $img_im1, 0, 0, 0, 0, $width1, 239, $width1, $height1);

            $temp_image2 = imagecreatetruecolor($width, $height);
            imagecopyresampled($temp_image2, $temp_image, 0, 0, 0, 0, $width, $height, $width1, 239);

            $img_im = imagecreatetruecolor($width, $height);
            imagecopyresampled($img_im, $temp_image, 0, 0, 0, 0, $width, $height, $width, $height);
        } else {
            $temp_image = imagecreatetruecolor($width, $height);
            imagecopyresampled($temp_image, $img_im1, 0, 0, 0, 0, $width, $height, $width1, $height1);
            $img_im = imagecreatetruecolor($width, $height);
            imagecopyresampled($img_im, $temp_image, 0, 0, 0, 0, $width, $height, $width, $height);
        }



        $threshold = 26;

        $rgb1 = imagecolorat($img_im, 1, 1);
        $r = ($rgb1 >> 16) & 0xFF;
        $g = ($rgb1 >> 8) & 0xFF;
        $b = $rgb1 & 0xFF;
        $rgb1a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb2 = imagecolorat($img_im, 3, 3);
        $r = ($rgb2 >> 16) & 0xFF;
        $g = ($rgb2 >> 8) & 0xFF;
        $b = $rgb2 & 0xFF;
        $rgb2a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb3 = imagecolorat($img_im, 5, 5);
        $r = ($rgb3 >> 16) & 0xFF;
        $g = ($rgb3 >> 8) & 0xFF;
        $b = $rgb3 & 0xFF;
        $rgb3a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb4 = imagecolorat($img_im, 7, 7);
        $r = ($rgb4 >> 16) & 0xFF;
        $g = ($rgb4 >> 8) & 0xFF;
        $b = $rgb4 & 0xFF;
        $rgb4a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb5 = imagecolorat($img_im, 9, 9);
        $r = ($rgb5 >> 16) & 0xFF;
        $g = ($rgb5 >> 8) & 0xFF;
        $b = $rgb5 & 0xFF;
        $rgb5a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb6 = imagecolorat($img_im, 11, 11);
        $r = ($rgb6 >> 16) & 0xFF;
        $g = ($rgb6 >> 8) & 0xFF;
        $b = $rgb6 & 0xFF;
        $rgb6a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb7 = imagecolorat($img_im, 13, 13);
        $r = ($rgb7 >> 16) & 0xFF;
        $g = ($rgb7 >> 8) & 0xFF;
        $b = $rgb7 & 0xFF;
        $rgb7a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb8 = imagecolorat($img_im, 15, 15);
        $r = ($rgb8 >> 16) & 0xFF;
        $g = ($rgb8 >> 8) & 0xFF;
        $b = $rgb8 & 0xFF;
        $rgb8a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb8 = imagecolorat($img_im, 1, 15);
        $r = ($rgb8 >> 16) & 0xFF;
        $g = ($rgb8 >> 8) & 0xFF;
        $b = $rgb8 & 0xFF;
        $rgb9a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb9 = imagecolorat($img_im, 3, 13);
        $r = ($rgb9 >> 16) & 0xFF;
        $g = ($rgb9 >> 8) & 0xFF;
        $b = $rgb9 & 0xFF;
        $rgb9a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb10 = imagecolorat($img_im, 5, 11);
        $r = ($rgb10 >> 16) & 0xFF;
        $g = ($rgb10 >> 8) & 0xFF;
        $b = $rgb10 & 0xFF;
        $rgb10a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb11 = imagecolorat($img_im, 7, 9);
        $r = ($rgb11 >> 16) & 0xFF;
        $g = ($rgb11 >> 8) & 0xFF;
        $b = $rgb11 & 0xFF;
        $rgb11a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb12 = imagecolorat($img_im, 9, 7);
        $r = ($rgb12 >> 16) & 0xFF;
        $g = ($rgb12 >> 8) & 0xFF;
        $b = $rgb12 & 0xFF;
        $rgb12a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb13 = imagecolorat($img_im, 11, 5);
        $r = ($rgb13 >> 16) & 0xFF;
        $g = ($rgb13 >> 8) & 0xFF;
        $b = $rgb13 & 0xFF;
        $rgb13a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb14 = imagecolorat($img_im, 13, 3);
        $r = ($rgb14 >> 16) & 0xFF;
        $g = ($rgb14 >> 8) & 0xFF;
        $b = $rgb14 & 0xFF;
        $rgb14a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        $rgb15 = imagecolorat($img_im, 15, 1);
        $r = ($rgb15 >> 16) & 0xFF;
        $g = ($rgb15 >> 8) & 0xFF;
        $b = $rgb15 & 0xFF;
        $rgb15a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        //				$rgb16 = imagecolorat($img_im, 3, 8);
        //					$r = ($rgb16 >> 16) & 0xFF;
        //				    $g = ($rgb16 >> 8) & 0xFF;
        //				   	$b = $rgb16 & 0xFF;
        //				$rgb16a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        //				$rgb17 = imagecolorat($img_im, 8, 3);
        //					$r = ($rgb17 >> 16) & 0xFF;
        //				    $g = ($rgb17 >> 8) & 0xFF;
        //				   	$b = $rgb17 & 0xFF;
        //				$rgb17a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        //				$rgb18 = imagecolorat($img_im, 8, 13);
        //					$r = ($rgb18 >> 16) & 0xFF;
        //				    $g = ($rgb18 >> 8) & 0xFF;
        //				   	$b = $rgb18 & 0xFF;
        //				$rgb18a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
        //				$rgb19 = imagecolorat($img_im, 13, 8);
        //					$r = ($rgb19 >> 16) & 0xFF;
        //				    $g = ($rgb19 >> 8) & 0xFF;
        //				   	$b = $rgb19 & 0xFF;
        //				$rgb19a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);


        $img_key = $rgb1a . $rgb2a . $rgb3a . $rgb4a . $rgb5a . $rgb6a . $rgb7a . $rgb8a . $rgb9a . $rgb10a . $rgb11a . $rgb12a . $rgb13a . $rgb14a . $rgb15a;

        // . $rgb8a . $rgb9a . $rgb10a;


        imagedestroy($img_im1);
        imagedestroy($img_im);
        imagedestroy($temp_image);

        if($img_key == 0){
            $img_key = 2;
        }

        return $img_key;
    }
}


    //function processImage($img){
    //
    //	$img_im = ImageCreateFromJpeg($img);
    //
    //	$threshold = 26;
    //
    //	$start2 = microtime(true);
    //	$rgb1 = imagecolorat($img_im, 5, 5);
    //		$r = ($rgb1 >> 16) & 0xFF;
    //	    $g = ($rgb1 >> 8) & 0xFF;
    //	    $b = $rgb1 & 0xFF;
    //	$rgb1a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb2 = imagecolorat($img_im, 10, 10);
    //		$r = ($rgb2 >> 16) & 0xFF;
    //	    $g = ($rgb2 >> 8) & 0xFF;
    //	    $b = $rgb2 & 0xFF;
    //	$rgb2a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb3 = imagecolorat($img_im, 15, 15);
    //		$r = ($rgb3 >> 16) & 0xFF;
    //	    $g = ($rgb3 >> 8) & 0xFF;
    //	    $b = $rgb3 & 0xFF;
    //	$rgb3a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb4 = imagecolorat($img_im, 20, 20);
    //		$r = ($rgb4 >> 16) & 0xFF;
    //	    $g = ($rgb4 >> 8) & 0xFF;
    //	    $b = $rgb4 & 0xFF;
    //	$rgb4a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb5 = imagecolorat($img_im, 25, 25);
    //		$r = ($rgb5 >> 16) & 0xFF;
    //	    $g = ($rgb5 >> 8) & 0xFF;
    //	   	$b = $rgb5 & 0xFF;
    //	$rgb5a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb6 = imagecolorat($img_im, 30, 30);
    //		$r = ($rgb6 >> 16) & 0xFF;
    //	    $g = ($rgb6 >> 8) & 0xFF;
    //	   	$b = $rgb6 & 0xFF;
    //	$rgb6a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb7 = imagecolorat($img_im, 35, 35);
    //		$r = ($rgb7 >> 16) & 0xFF;
    //	    $g = ($rgb7 >> 8) & 0xFF;
    //	   	$b = $rgb7 & 0xFF;
    //	$rgb7a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb8 = imagecolorat($img_im, 40, 40);
    //		$r = ($rgb8 >> 16) & 0xFF;
    //	    $g = ($rgb8 >> 8) & 0xFF;
    //	   	$b = $rgb8 & 0xFF;
    //	$rgb8a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb8 = imagecolorat($img_im, 5, 40);
    //		$r = ($rgb8 >> 16) & 0xFF;
    //	    $g = ($rgb8 >> 8) & 0xFF;
    //	   	$b = $rgb8 & 0xFF;
    //	$rgb9a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb9 = imagecolorat($img_im, 10, 35);
    //		$r = ($rgb9 >> 16) & 0xFF;
    //	    $g = ($rgb9 >> 8) & 0xFF;
    //	   	$b = $rgb9 & 0xFF;
    //	$rgb9a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb10 = imagecolorat($img_im, 15, 30);
    //		$r = ($rgb10 >> 16) & 0xFF;
    //	    $g = ($rgb10 >> 8) & 0xFF;
    //	   	$b = $rgb10 & 0xFF;
    //	$rgb10a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb11 = imagecolorat($img_im, 20, 25);
    //		$r = ($rgb11 >> 16) & 0xFF;
    //	    $g = ($rgb11 >> 8) & 0xFF;
    //	   	$b = $rgb11 & 0xFF;
    //	$rgb11a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb12 = imagecolorat($img_im, 25, 20);
    //		$r = ($rgb12 >> 16) & 0xFF;
    //	    $g = ($rgb12 >> 8) & 0xFF;
    //	   	$b = $rgb12 & 0xFF;
    //	$rgb12a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb13 = imagecolorat($img_im, 30, 15);
    //		$r = ($rgb13 >> 16) & 0xFF;
    //	    $g = ($rgb13 >> 8) & 0xFF;
    //	   	$b = $rgb13 & 0xFF;
    //	$rgb13a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb14 = imagecolorat($img_im, 35, 10);
    //		$r = ($rgb14 >> 16) & 0xFF;
    //	    $g = ($rgb14 >> 8) & 0xFF;
    //	   	$b = $rgb14 & 0xFF;
    //	$rgb14a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb15 = imagecolorat($img_im, 40, 5);
    //		$r = ($rgb15 >> 16) & 0xFF;
    //	    $g = ($rgb15 >> 8) & 0xFF;
    //	   	$b = $rgb15 & 0xFF;
    //	$rgb15a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb16 = imagecolorat($img_im, 5, 25);
    //		$r = ($rgb16 >> 16) & 0xFF;
    //	    $g = ($rgb16 >> 8) & 0xFF;
    //	   	$b = $rgb16 & 0xFF;
    //	$rgb16a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb17 = imagecolorat($img_im, 25, 5);
    //		$r = ($rgb17 >> 16) & 0xFF;
    //	    $g = ($rgb17 >> 8) & 0xFF;
    //	   	$b = $rgb17 & 0xFF;
    //	$rgb17a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb18 = imagecolorat($img_im, 20, 40);
    //		$r = ($rgb18 >> 16) & 0xFF;
    //	    $g = ($rgb18 >> 8) & 0xFF;
    //	   	$b = $rgb18 & 0xFF;
    //	$rgb18a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //	$rgb19 = imagecolorat($img_im, 40, 20);
    //		$r = ($rgb19 >> 16) & 0xFF;
    //	    $g = ($rgb19 >> 8) & 0xFF;
    //	   	$b = $rgb19 & 0xFF;
    //	$rgb19a = floor((floor($r/$threshold) + floor($g/$threshold) + floor($b/$threshold))/3);
    //
    //
    //	$img_key = $rgb1a . $rgb2a . $rgb3a . $rgb4a . $rgb5a . $rgb6a . $rgb7a . $rgb8a . $rgb9a . $rgb10a . $rgb11a . $rgb12a . $rgb13a . $rgb14a . $rgb15a . $rgb16a . $rgb17a . $rgb18a . $rgb19a;
    //
    // 	imagedestroy($img_im);
    //
    //	return $img_key;
    //}
