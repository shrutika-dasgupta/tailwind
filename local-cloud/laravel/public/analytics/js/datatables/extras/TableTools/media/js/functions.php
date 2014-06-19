<?php

//xxxxxxxxxxxxxxxxxxxxxxxxx
// API CALL FUNCTIONS
//xxxxxxxxxxxxxxxxxxxxxxxxx
	function fromBoolToString($b) {
		if ($b) {
			return "TRUE";
		} else {
			return "FALSE";
		}
	}
	
	function processAPIBoardData($user_id, $track_type, $data) {
		$board = new Board($track_type);
		$board->loadFromAPIBlockWithUserId($user_id, $data);
		return $board;
	}
	
	function saveBoards($boards, $conn) {
		$inserts = "";
		
		foreach($boards as $board) {
			$board_id = mysql_real_escape_string($board->id);
			$user_id = mysql_real_escape_string($board->user_id);
			$url = mysql_real_escape_string($board->url);
			$is_collaborator = mysql_real_escape_string(fromBoolToString($board->is_collaborator));
			$is_owner = mysql_real_escape_string(fromBoolToString($board->is_owner));
			$collaborator_count = mysql_real_escape_string($board->collaborator_count);
			$image_cover_url = mysql_real_escape_string($board->image_cover_url);
			$name = mysql_real_escape_string($board->name);
			$description = mysql_real_escape_string($board->description);
			$category = mysql_real_escape_string($board->category);
			$pins = mysql_real_escape_string($board->pins);
		 	$followers = mysql_real_escape_string($board->followers);
			$created_at = mysql_real_escape_string($board->created_at);
			$last_pulled = time();
			$track_type = mysql_real_escape_string($board->track_type);
			$timestamp = time();
			
			if ($inserts == "") {
				$inserts = "INSERT into data_boards (
					board_id
					, user_id
					, url
					, is_collaborator
					, is_owner
					, collaborator_count
					, image_cover_url
					, name
					, description
					, category
					, pin_count
					, follower_count
					, created_at
					, last_pulled
					, track_type
					, timestamp)
					VALUES (
						'$board_id'
						, '$user_id'
						, '$url'
						, $is_collaborator
						, $is_owner
						, '$collaborator_count'
						, '$image_cover_url'
						, '$name'
						, '$description'
						, '$category'
						, '$pins'
						, '$followers'
						, $created_at
						, '$last_pulled'
						, '$track_type'
						, '$timestamp')";				
			} else {
				$inserts .= ",
			 		(
						'$board_id'
						, '$user_id'
						, '$url'
						, $is_collaborator
						, $is_owner
						, '$collaborator_count'
						, '$image_cover_url'
						, '$name'
						, '$description'
						, '$category'
						, '$pins'
						, '$followers'
						, $created_at
						, '$last_pulled'
						, '$track_type'
						, '$timestamp')";
			}
		}
		
		if ($inserts != "") {
			$inserts .= " 
			ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), url = VALUES(url), is_collaborator = VALUES(is_collaborator), is_owner = VALUES(is_owner), collaborator_count = VALUES(collaborator_count), image_cover_url = VALUES(image_cover_url), name = VALUES(name), description = VALUES(description), category = VALUES(category), pin_count = VALUES(pin_count), follower_count = VALUES(follower_count), created_at = VALUES(created_at), last_pulled = VALUES(last_pulled), timestamp = VALUES(timestamp)";
			$acc_res = mysql_query($inserts,$conn) or die(mysql_error());			
		}
	}
	
	
	function processAPIUserFollowerData($user_id, $track_type, $data) {
		$user = new User($track_type);
		$user->loadFromAPIBlock($data);
		$user->setFollowedUserId($user_id);
		return $user;
	}
	
	function saveFollowers($followers, $conn) {

		$inserts = "";
		foreach($followers as $follower) {
			$user_id = mysql_real_escape_string($follower->followed_user_id);
			$follower_user_id = mysql_real_escape_string($follower->id);
			$pin_count = mysql_real_escape_string($follower->pin_count);
			$follower_count = mysql_real_escape_string($follower->follower_count);
			$created_at = mysql_real_escape_string($follower->created_at);
			$facebook_url = mysql_real_escape_string($follower->facebook);
			$twitter_url = mysql_real_escape_string($follower->twitter);	
			$domain_url = mysql_real_escape_string($follower->domain_url);
			$domain_verified = mysql_real_escape_string(fromBoolToString($follower->domain_verified));								
			$location = mysql_real_escape_string($follower->location);			
			
			$time = time();
			
			if ($inserts == "") {
				$inserts = "INSERT into data_followers (
					user_id
					, follower_user_id
					, follower_pin_count
					, follower_followers
					, follower_created_at
					, follower_facebook
					, follower_twitter
					, follower_domain
					, follower_domain_verified
					, follower_location
					, timestamp
					)
					VALUES (
						'$user_id'
						, '$follower_user_id'
						, '$pin_count'
						, '$follower_count'
						, '$created_at'																		
						, '$facebook_url'						
						, '$twitter_url'						
						, '$domain_url'						
						, $domain_verified
						, '$location'						
						, '$time'
						)";				
			} else {
				$inserts .= ",
				 		(
						'$user_id'
						, '$follower_user_id'
						, '$pin_count'
						, '$follower_count'
						, '$created_at'																		
						, '$facebook_url'						
						, '$twitter_url'						
						, '$domain_url'						
						, $domain_verified
						, '$location'						
						, '$time'
						)";
			}
		}
		
		if ($inserts != "") {
			
			$inserts .= " 
			ON DUPLICATE KEY UPDATE follower_pin_count = VALUES(follower_pin_count), follower_followers = VALUES(follower_followers), follower_created_at = VALUES(follower_created_at), follower_facebook = VALUES(follower_facebook), follower_twitter = VALUES(follower_twitter), follower_domain = VALUES(follower_domain), follower_domain_verified = VALUES(follower_domain_verified), follower_location = VALUES(follower_location), timestamp = VALUES(timestamp)";
			$acc_res = mysql_query($inserts,$conn) or die(mysql_error());			
		}		
		
		saveUsers($followers, $conn);
	}
	
	function saveUsers($users, $conn) {
		$inserts = "";
		
		foreach($users as $user) {
			$user_id = mysql_real_escape_string($user->id);
			$username = mysql_real_escape_string($user->username);
			$first_name = mysql_real_escape_string($user->first_name);
			$last_name = mysql_real_escape_string($user->last_name);
			$email = mysql_real_escape_string($user->email);
			$image = mysql_real_escape_string($user->image);
			$about = mysql_real_escape_string($user->about);
			$domain_url = mysql_real_escape_string($user->domain_url);
			$domain_verified = mysql_real_escape_string(fromBoolToString($user->domain_verified));
			$website_url = mysql_real_escape_string($user->website_url);
			$facebook_url = mysql_real_escape_string($user->facebook);
			$twitter_url = mysql_real_escape_string($user->twitter);
			$location = mysql_real_escape_string($user->location);
			$board_count = mysql_real_escape_string($user->board_count);
			$pin_count = mysql_real_escape_string($user->pin_count);
			$like_count = mysql_real_escape_string($user->like_count);
			$follower_count = mysql_real_escape_string($user->follower_count);
			$following_count = mysql_real_escape_string($user->following_count);
			$created_at = mysql_real_escape_string($user->created_at);
			$last_pulled = time();
			$track_type = mysql_real_escape_string($user->track_type);
			$timestamp = time();
			
			if ($inserts == "") {
				$inserts = "INSERT into data_profiles_new (
					user_id
					, username
					, first_name
					, last_name
					, email
					, image
					, about
					, domain_url
					, domain_verified
					, website_url
					, facebook_url
					, twitter_url
					, location
					, board_count
					, pin_count
					, like_count
					, follower_count
					, following_count
					, created_at
					, last_pulled
					, track_type
					, timestamp)
					VALUES (
						'$user_id'
						, '$username'
						, '$first_name'
						, '$last_name'
						, '$email'
						, '$image'
						, '$about'
						, '$domain_url'
						, $domain_verified
						, '$website_url'
						, '$facebook_url'
						, '$twitter_url'
						, '$location'
						, '$board_count'
						, '$pin_count'
						, '$like_count'
						, '$follower_count'
						, '$following_count'
						, '$created_at'
						, '$last_pulled'
						, '$track_type'
						, '$timestamp')";				
			} else {
				$inserts .= ",
			 		(
					'$user_id'
					, '$username'
					, '$first_name'
					, '$last_name'
					, '$email'
					, '$image'
					, '$about'
					, '$domain_url'
					, $domain_verified
					, '$website_url'
					, '$facebook_url'
					, '$twitter_url'
					, '$location'
					, '$board_count'
					, '$pin_count'
					, '$like_count'
					, '$follower_count'
					, '$following_count'
					, '$created_at'
					, '$last_pulled'
					, '$track_type'
					, '$timestamp')";
			}
		}
		
		if ($inserts != "") {
			$inserts .= " 
			ON DUPLICATE KEY UPDATE username = VALUES(username), first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), image = VALUES(image), about = VALUES(about), domain_url = VALUES(domain_url), domain_verified = VALUES(domain_verified), website_url = VALUES(website_url), facebook_url = VALUES(facebook_url), twitter_url = VALUES(twitter_url), location = VALUES(location), board_count = VALUES(board_count), pin_count = VALUES(pin_count), like_count = VALUES(like_count), follower_count = VALUES(follower_count), following_count = VALUES(following_count), last_pulled = VALUES(last_pulled), timestamp = VALUES(timestamp)";
			$acc_res = mysql_query($inserts,$conn) or die(mysql_error());			
		}
	}
	
	function processAPIUserData($track_type, $data) {
		$user = new User($track_type);
		$user->loadFromAPIBlock($data);
		return $user;
	}
	

	function parsePinterestCreationDateToTimestamp($date) {
		if (!$date) {
			return 0;
		}
		
		$parsed = date_parse($date);
		return mktime($parsed['hour'], $parsed['minute'], $parsed['second'], $parsed['month'], $parsed['day'], $parsed['year']);
	}

	function savePins($pins, $conn) {
		$inserts = "";
		
		$queue_user_ids = array();
		foreach($pins as $pin) {
			$pin_id = mysql_real_escape_string($pin->id);
			$user_id = mysql_real_escape_string($pin->user_id);			
			$board_id = mysql_real_escape_string($pin->board_id);			
			$domain = mysql_real_escape_string($pin->domain);
			$method = mysql_real_escape_string($pin->method);
			$is_repin = mysql_real_escape_string($pin->is_repin);
			$parent_pin = mysql_real_escape_string($pin->parent_pin);
			$via_pinner = mysql_real_escape_string($pin->via_pinner);
			$image_url = mysql_real_escape_string($pin->image_url);														
			$image_square_url = mysql_real_escape_string($pin->image_square_url);
			$link = mysql_real_escape_string($pin->link);
			$description = mysql_real_escape_string($pin->description);
			$location = mysql_real_escape_string($pin->location);
			$dominant_color = mysql_real_escape_string($pin->dominant_color);
			$rich_product = mysql_real_escape_string($pin->rich_product);
			$repin_count = mysql_real_escape_string($pin->repin_count);
			$like_count = mysql_real_escape_string($pin->like_count);
			$comment_count = mysql_real_escape_string($pin->comment_count);
			$created_at = mysql_real_escape_string($pin->created_at);
			$image_id = mysql_real_escape_string($pin->image_id);
			$last_pulled = time();
			$track_type = mysql_real_escape_string($pin->track_type);																					
			$time = time();
			
			array_push($queue_user_ids, $user_id);
						
			if ($inserts == "") {
				$inserts = "INSERT into data_pins_new (
					pin_id
					, user_id
					, board_id
					, domain
					, method
					, is_repin
					, parent_pin
					, via_pinner
					, image_url
					, image_square_url
					, link
					, description
					, location
					, dominant_color
					, rich_product
					, repin_count
					, like_count
					, comment_count
					, created_at
					, image_id
					, last_pulled
					, track_type
					, timestamp)
					VALUES (
						'$pin_id'
						, '$user_id'
						, '$board_id'
						, '$domain'
						, '$method'
						, '$is_repin'
						, '$parent_pin'
						, '$via_pinner'
						, '$image_url'
						, '$image_square_url'
						, '$link'
						, '$description'
						, '$location'
						, '$dominant_color'
						, '$rich_product'
						, '$repin_count'
						, '$like_count'
						, '$comment_count'
						, '$created_at'
						, '$image_id'
						, '$last_pulled'
						, '$track_type'
						, '$time')";
				
				
			} else {
				$inserts .= ",
				 	(
					'$pin_id'
					, '$user_id'
					, '$board_id'
					, '$domain'
					, '$method'
					, '$is_repin'
					, '$parent_pin'
					, '$via_pinner'
					, '$image_url'
					, '$image_square_url'
					, '$link'
					, '$description'
					, '$location'
					, '$dominant_color'
					, '$rich_product'
					, '$repin_count'
					, '$like_count'
					, '$comment_count'
					, '$created_at'
					, '$image_id'
					, '$last_pulled'
					, '$track_type'
					, '$time')";
			}
		}
		
		if ($inserts != "") {
			$inserts .= " 
			ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), board_id = VALUES(board_id), domain = VALUES(domain), method = VALUES(method), is_repin = VALUES(is_repin), parent_pin = VALUES(parent_pin), via_pinner = VALUES(via_pinner), image_url = VALUES(image_url), image_square_url = VALUES(image_square_url), link = VALUES(link), description = VALUES(description), location = VALUES(location), dominant_color = VALUES(dominant_color), rich_product = VALUES(rich_product), repin_count = VALUES(repin_count), like_count = VALUES(like_count), comment_count = VALUES(comment_count), created_at = VALUES(created_at), image_id  = VALUES(image_id), last_pulled = VALUES(last_pulled), timestamp = VALUES(timestamp)";
			$acc_res = mysql_query($inserts,$conn) or die(mysql_error());			
		}
		
		queueUserIds($queue_user_ids, $conn);
	}
	
	function queueUserIds($queue_user_ids, $conn) {
		$inserts = "";
	
		foreach($queue_user_ids as $user_id) {
			$user_id = mysql_real_escape_string($user_id);
			$track_type = "track";																			
			$time = time();
						
			if ($inserts == "") {
				$inserts = "INSERT IGNORE into data_profiles_new (
					user_id
					, username
					, first_name
					, last_name
					, email
					, image
					, about
					, domain_url
					, domain_verified
					, website_url
					, facebook_url
					, twitter_url
					, location
					, board_count
					, pin_count
					, like_count
					, follower_count
					, following_count
					, created_at
					, last_pulled
					, track_type
					, timestamp)
					VALUES (
						'$user_id'
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, ''
						, '0'
						, '$track_type'
						, '$time')";				
			} else {
				$inserts .= ",
			 		(
					'$user_id'
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, ''
					, '0'
					, '$track_type'
					, '$time')";
			}
		}
		
		if ($inserts != "") {
			$acc_res = mysql_query($inserts,$conn) or die(mysql_error());			
		}		
	}

	function processAPIPinData($track_type, $pin_data) {
		$pin = new Pin($track_type);
		$pin->loadFromAPIBlock($pin_data);
		return $pin;
	}

	function getAPIDataFromCall($data) {
		if (array_key_exists("data", $data)) {
			return $data['data'];
		} else {
			return array();
		}
	}

	function getBookmarkFromAPIReturn($data) {
		if (array_key_exists("bookmark", $data)) {
			return $data["bookmark"];
		}
		return "";
	}

	function isValidAPIReturn($data) {
		if (is_array($data)) {
			if (array_key_exists("status", $data)) {
				if ($data['status'] == "success") {
					return true;
				}
			}
		}
		
		return false;
	}

	function popAPICalls($api_call, $track_type, $limit, $conn) {
		if (!$limit) {
			$limit = 1;
		}
	
		$calls = array();		
	
		$api_call = mysql_real_escape_string($api_call);
		$track_type = mysql_real_escape_string($track_type);		
		$acc = "select * from status_api_calls_queue where api_call = \"$api_call\" AND track_type = '$track_type' order by timestamp asc limit $limit";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$call = array();
			$call['api_call'] = $a['api_call'];
			$call['object_id'] = $a['object_id'];
			$call['parameters'] = $a['parameters'];
			$call['bookmark'] = $a['bookmark'];
			$call['track_type'] = $a['track_type'];
			$call['timestamp'] = $a['timestamp'];
			$call['data'] = array();
			
			array_push($calls, $call);
		}
	
		foreach($calls as $call) {
			$api_call = mysql_real_escape_string($call['api_call']);
			$object_id = mysql_real_escape_string($call['object_id']);
			$parameters = mysql_real_escape_string($call['parameters']);
			$bookmark = mysql_real_escape_string($call['bookmark']);
		
			$acc = "delete from status_api_calls_queue where api_call = '$api_call' AND object_id = '$object_id' AND parameters = '$parameters' AND bookmark = '$bookmark'";
			$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		}
	
		return $calls;
	}

	function queueAPICall($api_call, $object_id, $parameters, $bookmark, $track_type, $conn) {
		$time = time();
		$sql = "INSERT IGNORE INTO status_api_calls_queue 
		(api_call, object_id, parameters, bookmark, track_type, timestamp) 
		VALUES (\"$api_call\", \"$object_id\", \"$parameters\", \"$bookmark\", \"$track_type\", \"$time\")";

		mysql_query($sql,$conn) or die(mysql_error());
	}


//xxxxxxxxxxxxxxxxxxxxxxxxx
// BOARD PIN FUNCTIONS
//xxxxxxxxxxxxxxxxxxxxxxxxx

	function getBoardPins($content) {
		$pins = array();
	
		if (doesBoardExists($content)) {
			$start = 0;	
			$i = 0;
			while (hasBoardPinLeft($content, $start)) {
				$pinSpot = nextBoardPinSpot($content, $start);
				
				$pinId = getBoardPinId($content, $pinSpot);				
				$likes = getBoardPinLikes($content, $pinSpot);
				$repins = getBoardPinRepins($content, $pinSpot);
				$comments = getBoardPinComments($content, $pinSpot);
				$image = getBoardPinImage($content, $pinSpot);
				$source = getBoardPinSource($content, $pinSpot);
				
				$board_id = getBoardId($content);
				$username = getBoardUsername($content);
				//username can't figure this out yet...				
				//is_repin
				//repinned_from
				//URL LINK
				
				$pin = array();
				$pin['id'] = $pinId;
				$pin['likes'] = $likes;
				$pin['repins'] = $repins;
				$pin['comments'] = $comments;
																
				array_push($pins, $pin);
		
				$start = $pinSpot;
			}
		}
		
		return $pins;
	}
	
	function getBoardPinSource($content, $pinSpot) {
		$start = strpos($content, "<p class=\"stats colorless\">", $pinSpot);
		$start = strpos($content, "<p class=\"NoImage\">", $start);
		$start += strlen("<p class=\"NoImage\">");
		
		$complete_end = strpos($content, "</p>", $start);
		
		if (strpos($content, "<a", $start) < $complete_end) {
			$start = strpos($content, "<a", $start);
			$start += strlen("<a");
			
			$start = strpos($content, ">", $start);
			$start += strlen(">");
			
			$end = strpos($content, "<", $start);
			
			return mysql_real_escape_string(substr($content, $start, $end - $start));
		} else {
			return mysql_real_escape_string(substr($content, $start, $complete_end - $start));
		}
	}
	
	function getBoardPinDescription($content, $pinSpot) {
		$start = strpos($content, "<p class=\"description\">", $pinSpot);
		$start += strlen("<p class=\"description\">");
		$end = strpos($content, "</p>", $start);
		
		$description = mysql_real_escape_string(substr($content, $start, $end - $start));
		
		return $description;		
		
	}
	
	function getBoardPinImage($content, $pinSpot) {
		$start = strpos($content, "class=\"PinImage ImgLink\">", $pinSpot);
		$start += strlen("class=\"PinImage ImgLink\">");
		$start = strpos($content, "<img src=\"", $start);
		$start += strlen("<img src=\"");
		$end = strpos($content, "\"", $start);
		
		$image = substr($content, $start, $end - $start);
		return $image;
	}
	
	function getBoardPinLikes($content, $pinSpot) {
		$start = strpos($content, "<span class=\"Likes", $pinSpot);
		if ($start === false) {
			return 0;
		}
		$start += strlen("<span class=\"Likes");
		$start = strpos($content, ">", $start);
		$start += strlen(">");
		$end = strpos($content, "</", $start);
		
		$data = strtolower(trim(substr($content, $start, $end - $start)));
		$data = str_replace("likes", "", $data);
		$data = str_replace("like", "", $data);		
		$data = trim($data);
		
		if (($data == "") || (!$data)) {
			return 0;
		}
		
		return $data;
	}
	
	function getBoardPinRepins($content, $pinSpot) {
		$start = strpos($content, "<span class=\"Repins", $pinSpot);
		if ($start === false) {
			return 0;
		}
		$start += strlen("<span class=\"Repins");
		$start = strpos($content, ">", $start);
		$start += strlen(">");
		$end = strpos($content, "</", $start);
		
		$data = strtolower(trim(substr($content, $start, $end - $start)));
		$data = str_replace("repins", "", $data);
		$data = str_replace("repin", "", $data);		
		$data = trim($data);
		
		if (($data == "") || (!$data)) {
			return 0;
		}
		
		return $data;
	}
	
	function getBoardPinComments($content, $pinSpot) {
		$start = strpos($content, "<span class=\"Comments", $pinSpot);
		if ($start === false) {
			return 0;
		}
		$start += strlen("<span class=\"Comments");
		$start = strpos($content, ">", $start);
		$start += strlen(">");
		$end = strpos($content, "</", $start);
		
		$data = strtolower(trim(substr($content, $start, $end - $start)));
		$data = str_replace("comments", "", $data);
		$data = str_replace("comment", "", $data);		
		$data = trim($data);
		
		if (($data == "") || (!$data)) {
			return 0;
		}
		
		return $data;
	}

	function getBoardPinId($content, $pinSpot) {
		$startTag = "data-id=\"";
		$endTag = "\"";
	
		$pinStart = strpos($content, $startTag, $pinSpot) + strlen($startTag);
		$pinEnd = strpos($content, $endTag, $pinStart);
		$pinId = substr($content, $pinStart, $pinEnd - $pinStart);
	
		return $pinId;
	}	

	function nextBoardPinSpot($content, $start) {
		$start = strpos($content, "<div class=\"pin\"", $start);
		$start += strlen("<div class=\"pin\"");
		return $start;
	}

	function hasBoardPinLeft($content, $start) {
		if (strpos($content, "<div class=\"pin\"", $start) === false) {
			return false;			
		} else {
			return true;
		}
	}
	
	function getProfileFollowersURL($username) {
		return "http://pinterest.com/" . $username . "/followers/";
	}


	function getBoardPageContent($url, $page) {
		$url = "http://www.pinterest.com" . $url . "?page=$page";

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);		
		curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, "PinLeague-Bot");
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);		
		$page = curl_exec ($c);
		curl_close ($c);

		$content = formatContent($page);
	
		return $content;
	}

//GET BOARDS FUNCTIONS
	function getBoards($c) {
		$boards = array();
	
		$on = 0;
		while (!(strpos($c, "<div class=\"pin pinBoard\"", $on) === false)) {
			$on = strpos($c, "<div class=\"pin pinBoard\"", $on);
			$on += strlen("<div class=\"pin pinBoard\"");
		
			$start = $on;
			$start = strpos($c, "id=\"", $start);
			$start += strlen("id=\"");		
			$end = strpos($c, "\"", $start);
			$board_id = str_replacE("board", "", substr($c, $start, $end - $start));
		
			$start = strpos($c, "<h3 class=\"serif\">", $start);
			$start += strlen("<h3 class=\"serif\">");

			$start = strpos($c, "<a href=\"", $start);
			$start += strlen("<a href=\"");
			$end = strpos($c, "\"", $start);
			$board_url = substr($c, $start, $end - $start);
		
			$start = strpos($c, ">", $start);
			$start += strlen(">");
			$end = strpos($c, "<", $start);
			$board_name = substr($c, $start, $end - $start);
		
			$board = array();
			$board['board_id'] = $board_id;
			$board['board_url'] = $board_url;
			$board['board_name'] = $board_name;
		
			if ((strpos($board_id, "data.id") === false) && ($board_id != "") && ($board_id != "0")) {
				array_push($boards, $board);
			}
		}
	
		return $boards;
	}

//UPDATE BOARD FUNCTIONS
	
	function getBoardData($c) {		
		$data = array();
		$data['exists'] = doesBoardExists($c);
		$data['title'] = getBoardTitle($c);
		$data['board_id'] = getBoardId($c);
		$data['pins'] = getBoardPinsNumber($c);
		$data['followers'] = getBoardFollowers($c);
		$data['category'] = getBoardCategory($c);
		$data['description'] = getBoardDescription($c);
		$data['is_community'] = getBoardIsCommunity($c);
		$data['cover_photo'] = getBoardCoverPhoto($c);
						
		return $data;		
	}
	
	function getBoardCoverPhoto($c) {
		$start = strpos($c, "<meta property=\"og:image\"");
		
		if ($start === false) {
			return "";
		}
		
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		
		$end = strpos($c, "\"", $start);
		
		return substr($c, $start, $end - $start);
	}
	
	function getBoardIsCommunity($c) {
		if (strpos($c, "class=\"ImgLink collaborator\"") === false) {
			return false;
		} else {
			return true;
		}
	}
	
	function doesBoardExists($c) {
		if (strpos($c, "<title>Pinterest - 404</title>") === false) {
			return true;
		} else {
			return false;
		}
	}
	
	function getBoardId($c) {
		$start = strpos($c, "var board = ");
		$start += strlen("var board = ");
		$end = strpos($c, ";", $start);
		return substr($c, $start, $end - $start);
	}
	
	function getBoardPinsNumber($c) {
		$start = strpos($c, "<meta property=\"pinterestapp:pins");
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		$end = strpos($c, "\"", $start);
		
		return str_replace(",", "", substr($c, $start, $end - $start));		
	}
	
	function getBoardFollowers($c) {
		$start = strpos($c, "<meta property=\"pinterestapp:followers");
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		$end = strpos($c, "\"", $start);
		
		return str_replace(",", "", substr($c, $start, $end - $start));	
	}
	
	function getBoardCategory($c) {
		$start = strpos($c, "<meta property=\"pinterestapp:category\" content=\"");
		$start += strlen("<meta property=\"pinterestapp:category\" content=\"");
		$end = strpos($c, "\"", $start);
		
		return strtolower(substr($c, $start, $end - $start));
	}
	
	function getBoardDescription($c) {
		$start = strpos($c, "<div id=\"wrapper\" class=\"BoardLayout\">");
		$start = strpos($c, "<p id=\"BoardDescription\" class=\"serif\">", $start);
		if ($start === false) {
			return "";
		} else {
			$start += strlen("<p id=\"BoardDescription\" class=\"serif\">");
			$end = strpos($c, "</p>", $start);
			return substr($c, $start, $end - $start);
		}
	}
	
	function getBoardTitle($c) {
		$start = strpos($c, "<div id=\"wrapper\" class=\"BoardLayout\">");
		$start = strpos($c, "<div id=\"BoardTitle\">", $start);
		$start = strpos($c, "<h1 class=\"serif\">", $start);
		$start += strlen("<h1 class=\"serif\">");
		$start = strpos($c, "<strong>", $start);
		$start += strlen("<strong>");
		$end = strpos($c, "<", $start);
		
		return substr($c, $start, $end - $start);
	}
	
	
//UPDATE PINS FUNCTIONS

	function getSourceURL($s) {
		$s = strtolower($s);
		$s = str_replace("www.", "", $s);
		$s = str_replace("http://", "", $s);
		$end = strpos($s, "/");
		if ($end === false) {
		} else {
			$s = substr($s, 0, $end);
		}
		
		$on = 0;
		$periods = 0;
		while (!(strpos($s, ".", $on) === false)) {
			$periods++;
			$on = strpos($s, ".", $on);
			$on += strlen(".");
		}
		
		$start = 0;
		$on_period = 0;
		while ($on_period < $periods - 1) {
			$start = strpos($s, ".", $start);
			$start += strlen(".");
			$on_period++;
		}
		
		return strtolower(trim(substr($s, $start)));		
	}	
		
	function getPinData($c) {
		$data = array();
		$data['is_404'] = mysql_real_escape_string(isPage404($c));
		$data['board_pins'] = mysql_real_escape_string(getBoardPinsNumberOnPin($c));
 		$data['created_at'] = mysql_real_escape_string(getPinTimestamp($c));
 		$data['comments'] = mysql_real_escape_string(getPinComments($c));
 		$data['likes'] = mysql_real_escape_string(getPinLikes($c));
 		$data['repins'] = mysql_real_escape_string(getPinRepins($c));
		$data['source'] = mysql_real_escape_string(getPinSource($c));
 		$data['username'] = mysql_real_escape_string(getPinUsername($c));
 		$data['board_id'] = mysql_real_escape_string(getPinBoardID($c));
 		$data['is_repin'] = mysql_real_escape_string(getPinIsRepin($c));
 		$data['repinned_from'] = mysql_real_escape_string(getPinRepinnedFrom($c));
 		$data['photo_url'] = mysql_real_escape_string(getPinPhotoURL($c));
 		$data['description'] = mysql_real_escape_string(getPinDescription($c));		
 		$data['brand_mention'] = mysql_real_escape_string(getPinBrandMention($c));	
		return $data;
	}
	
	function isPage404($c) {
		if (strpos($c, "<title>Pinterest - 404</title>") === false) {
			return false;
		} else {
			return true;
		}
	}
	
	function getPinBrandMention($c) {
		$start = strpos($c, "<div id=\"PinActionButtons\">");
		if ($start === false) {	return ""; }		
		$start = strpos($c, "<p id=\"PinSource\" class=\"colorlight\">", $start);
		if ($start === false) {	return ""; }
		$start += strlen("<p id=\"PinSource\" class=\"colorlight\">");
		$start = strpos($c, "\">", $start);
		if ($start === false) {	return ""; }
		$start += strlen("\">");
		$end = strpos($c, "</a>", $start);
		
		return trim(substr($c, $start, $end - $start));
	}
	
	function getPinDescription($c) {
		$start = strpos($c, "<meta property=\"og:description\" content=\"");
		$start += strlen("<meta property=\"og:description\" content=\"");
		$end = strpos($c, "\"", $start);
		return trim(substr($c, $start, $end - $start));
	}
	
	function getPinPhotoURL($c) {		
		$start = strpos($c, "<div id=\"PinImageHolder\">");
		$start = strpos($c, "<div id=\"PinActionButtons\">", $start);		
		$start = strpos($c, "<img src=\"", $start);
		$start += strlen("<img src=\"");
		$end = strpos($c, "\"", $start);
		
		return substr($c, $start, $end - $start);
	}
	
	function getPinRepinnedFrom($c) {
		if (!getPinIsRepin($c)) {
			return "";
		} else {
			$start = strpos($c, "<div id=\"PinPinner\">");
			$start = strpos($c, "<p id=\"PinnerName\">", $start);
			$start = strpos($c, "</a>", $start);		
			$start = strpos($c, "&nbsp;via&nbsp;", $start);
			$start = strpos($c, "<a href=\"/", $start);
			$end = strpos($c, "/\"", $start);
			return substr($c, $start, $end - $start);			
		}
	}
	
	function getPinIsRepin($c) {
		$start = strpos($c, "<div id=\"PinPinner\">");
		$start = strpos($c, "<p id=\"PinnerName\">", $start);
		$start = strpos($c, "</a>", $start);		
		
		$there = strpos($c, "&nbsp;via&nbsp;", $start);		
		$end = strpos($c, "<p id=\"PinnerStats\"", $start);
		
		if ($there === false) {
			return false;
		} else {
			if ($there < $end) {
				return true;
			} else {
				return false;
			}
		}
		
	}
	
	function getPinBoardID($c) {
		$start = strpos($c, "<div class=\"pin pinBoard\" id=\"");
		$start += strlen("<div class=\"pin pinBoard\" id=\"");
		$end = strpos($c, "\"", $start);
		
		return str_replace("board", "", substr($c, $start, $end - $start));
	}
	
	function getPinUsername($c) {
		$start = strpos($c, "<div id=\"PinPinner\">");
		$start = strpos($c, "<a href=\"/", $start);
		$start += strlen("<a href=\"/");
		$end = strpos($c, "/\"", $start);
		return substr($c, $start, $end - $start);		
	}
	

	function getPinLikes($c) {
		
		$on = strpos($c, "pinterestapp:likes");
		$on = strpos($c, "content=\"", $on);
		$on += strlen("content=\"");
		
		$end = strpos($c, "\"", $on);
		
		return substr($c, $on, $end - $on);
	}
	
	function getPinSource($c) {
		$area_start = strpos($c, "<p id=\"PinSource\"");
		$start = strpos($c, "<a href=\"", $area_start) + strlen("<a href=\"");
		$end = strpos($c, "\"", $start);
		return substr($c, $start, $end-$start);
	}
	
	function getPinRepins($c) {
		$on = strpos($c, "pinterestapp:repins");
		$on = strpos($c, "content=\"", $on);
		$on += strlen("content=\"");
		
		$end = strpos($c, "\"", $on);
		
		return substr($c, $on, $end - $on);
	}
	
	function getPinComments($c) {
		$on = strpos($c, "pinterestapp:comments");
		$on = strpos($c, "content=\"", $on);
		$on += strlen("content=\"");
		
		$end = strpos($c, "\"", $on);
		
		return substr($c, $on, $end - $on);
	}
	
	
	function getPinTimestamp($c) {
		$area_start = strpos($c, "<p id=\"PinnerStats\" class=\"colorless\">");
		$start = strpos($c, "Pinned", $area_start);
		if (($start === false) || ((!(strpos($c, "Uploaded", $area_start) === false)) && (strpos($c, "Uploaded", $area_start) < $start))) {
			$start = strpos($c, "Uploaded", $area_start);
			$start += strlen("Uploaded");			
		} else {
			$start += strlen("Pinned");	
		}
		
		
		$end = strpos($c, "ago", $start);
		
		$t = strtotime("-" . substr($c, $start, $end-$start));
		$d = getFlatDate($t);
		
		if ($d <= 0) {
			$area_start = strpos($c, "<p id=\"PinnerStats\" class=\"colorless\">");
			$start = strpos($c, "Repinned", $area_start);
			$start += strlen("Repinned");

			$end = strpos($c, "ago", $start);

			$t = strtotime("-" . substr($c, $start, $end-$start));
			$d = $t;
		}
		
		return $d;
	}
	
	function getBoardPinsNumberOnPin($c) {
		$area_start = strpos($c, "<div class=\"pin pinBoard\"");
		$start = strpos($c, "<h4>", $area_start);
		$start += strlen("<h4>");
		$end = strpos($c, "<", $start);
		return intval(substr($c, $start, $end - $start));
	}

//UPDATE PROFILE FUNCTIONS
	function getProfileData($c) {
	
		$data = array();		
		$data['display_name'] = getProfileDisplayName($c);
		$data['website'] = getProfileWebsite($c);	
		$data['followers'] = getProfileFollowersNumber($c);	
		$data['following'] = getProfileFollowingNumber($c);	
		$data['likes'] = getProfileLikesNumber($c);	
		$data['pins'] = getProfilePinsNumber($c);	
		$data['boards'] = getProfileBoardsNumber($c);	
		$data['username'] = getProfileUsername($c);	
		$data['facebook'] = getProfileFacebook($c);
		$data['description'] = getProfileDescription($c);	
		$data['location'] = getProfileLocation($c);		
		$data['state'] = getProfileLocationState($c);	
		$data['city'] = getProfileLocationCity($c);	
		$data['twitter'] = getProfileTwitter($c);	
		$data['image'] = getProfileImage($c);
	
		return $data;		
	}

	function getProfileWebsite($c) {
		$start = strpos($c, "<div id=\"ProfileHeader\">");
		$start += strlen("<div id=\"ProfileHeader\">");
		$start = strpos($c, "<ul id=\"ProfileLinks\" class=\"icons\">", $start);
		$start += strlen("<ul id=\"ProfileLinks\" class=\"icons\">");
		if (strpos($c, "class=\"icon website\"", $start) === false) {
			return "";
		} else {
			$start = strpos($c, "class=\"icon website\"", $start);
			$new_string = substr($c, 0, $start);
			$start = strrpos($new_string, "href=\"");
			$start += strlen("href=\"");			
			$end = strpos($new_string, "\"", $start);
			$website = substr($new_string, $start, $end - $start);
			if ($website == "' + website + '") {
				return "";
			} else {
				if (strpos($website, "/following/") === false) {
					return $website;
				} else {
					return "";
				}
			}
		}
	}
	
	function getProfileFollowersNumber($c) {
		$start = strpos($c, "<meta property=\"pinterestapp:followers\"");
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		$end = strpos($c, "\"", $start);
		
		return substr($c, $start, $end - $start);
	}

	function getProfileFollowingNumber($c) {
		$start = strpos($c, "<meta property=\"pinterestapp:following\"");
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		$end = strpos($c, "\"", $start);
		
		return substr($c, $start, $end - $start);		
	}

	function getProfileLikesNumber($c) {
		$start = strpos($c, "<div id=\"ProfileHeader\">");
		$start += strlen("<div id=\"ProfileHeader\">");
		$start = strpos($c, "<div class=\"content\">", $start);
		$start += strlen("<div class=\"content\">");
		$start = strpos($c, "<ul class=\"links\">", $start);
		$start += strlen("<ul class=\"links\">");				
		$start = strpos($c, "?filter=likes", $start);
		$start = strpos($c, "<strong>", $start);
		$start += strlen("<strong>");
	
		$end = strpos($c, "</strong>", $start);
	
		return str_replace(",","",substr($c, $start, $end - $start));		
	}

	function getProfilePinsNumber($c) {
		$start = strpos($c, "<meta property=\"pinterestapp:pins\"");
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		$end = strpos($c, "\"", $start);
		
		return substr($c, $start, $end - $start);
	}

	function getProfileBoardsNumber($c) {
		$start = strpos($c, "<meta property=\"pinterestapp:boards\"");
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		$end = strpos($c, "\"", $start);
		
		return substr($c, $start, $end - $start);	
	}

	function getProfileUsername($c) {
		$start = strpos($c, "<meta property=\"og:url\"");
		$start = strpos($c, "content=\"", $start);
		$start += strlen("content=\"");
		$end = strpos($c, "\"", $start);
		$url = substr($c, $start, $end - $start);
		$url = str_replace("http://pinterest.com/", "", $url);
		$url = str_replace("/", "", $url);
		$url = strtolower($url);
		return $url;		
	}

	function getProfileFacebook($c) {
		$start = strpos($c, "<div id=\"ProfileHeader\">");
		$start += strlen("<div id=\"ProfileHeader\">");
		$start = strpos($c, "<ul id=\"ProfileLinks\" class=\"icons\">", $start);
		$start += strlen("<ul id=\"ProfileLinks\" class=\"icons\">");
		if (strpos($c, "class=\"icon facebook\"", $start) === false) {
			return "";
		} else {
			$start = strpos($c, "class=\"icon facebook\"", $start);
			$new_string = substr($c, 0, $start);
			$end = strrpos($new_string, "\"");
			$new_string = substr($new_string, 0, $end);
			$start = strrpos($new_string, "\"");
			$start += strlen("\"");
			$url = substr($new_string, $start);
			$url = strtolower($url);
			$url = str_replace("http://", "", $url);
			$url = str_replace("www.", "", $url);
			$url = str_replace("facebook.com/", "", $url);	
			
			$url = str_replace("profile.php?id=", "", $url);		
			return $url;
		}
	}

	function getProfileDescription($c) {
		$start = strpos($c, "<div id=\"ProfileHeader\">");
		$start += strlen("<div id=\"ProfileHeader\">");
		$start = strpos($c, "<div class=\"content\">", $start);
		$start += strlen("<div class=\"content\">");
		$start = strpos($c, "<p class=\"colormuted\">", $start);
		$start += strlen("<p class=\"colormuted\">");		
		$end = strpos($c, "<", $start);
		$description =  htmlspecialchars(substr($c, $start, $end - $start));		
	
		if ($description == "' + about + '") {
			return "";
		} else {
			return $description;
		}
	}
	
	function getProfileLocation($c) {
		$start = strpos($c, "<ul class=\"icons ProfileLinks\">");
		$start += strlen("<ul class=\"icons ProfileLinks\">");
		if (strpos($c, "class=\"icon location\"", $start) === false) {
			return "";
		} else {
			$start = strpos($c, "<span class=\"icon location\"></span>", $start);
			$start += strlen("<span class=\"icon location\"></span>");
			$end = strpos($c, "<", $start);
			$location = trim(substr($c, $start, $end-$start));
			
			if (strpos($location, '$addIcons') === false) {
				if ($location == "' + location + '") {
					return "";
				} else {
					return $location;
				}
			} else {
				return "";
			}
		}
	}

	function getProfileLocationCity($c) {
		$location = getProfileLocation($c);
		if ($location == "") {
			return "";
		} else {
			if (strpos($location,",") === false) {
				return $location;
			} else {
				$end = strpos($location, ",");
				$location = substr($location, 0, $end);
				return $location;
			}
		}
	}

	function getProfileLocationState($c) {
		$location = getProfileLocation($c);
		if ($location == "") {
			return "";
		} else {
			if (strpos($location,",") === false) {
				return "";
			} else {
				$start = strpos($location, ",");
				$start += strlen(",");
				return substr($location, $start);
			}
		}
	}

	function getProfileTwitter($c) {
		$start = strpos($c, "<div id=\"ProfileHeader\">");
		$start += strlen("<div id=\"ProfileHeader\">");
		$start = strpos($c, "<ul id=\"ProfileLinks\" class=\"icons\">", $start);
		$start += strlen("<ul id=\"ProfileLinks\" class=\"icons\">");
		if (strpos($c, "class=\"icon twitter\"", $start) === false) {
			return "";
		} else {
			$start = strpos($c, "class=\"icon twitter\"", $start);
			$new_string = substr($c, 0, $start);
			$end = strrpos($new_string, "\"");
			$new_string = substr($new_string, 0, $end);
			$start = strrpos($new_string, "\"");
			$start += strlen("\"");
			$url = substr($new_string, $start);
			$url = strtolower($url);
			$url = str_replace("http://", "", $url);
			$url = str_replace("www.", "", $url);
			$url = str_replace("twitter.com/", "", $url);			
			return $url;
		}
	}

	function getProfileImage($c) {
		$start = strpos($c, "og:image\" content=\"");
		if ($start === false) {
			return "";
		}
		$start += strlen("og:image\" content=\"");
		$end = strpos($c, "\"", $start);
		return substr($c, $start, $end-$start);
	}

	function getProfileDisplayName($c) {
		$start = strpos($c, "<div id=\"ProfileHeader\">");
		$start += strlen("<div id=\"ProfileHeader\">");
		$start = strpos($c, "<div class=\"content\">", $start);
		$start += strlen("<div class=\"content\">");
		$start = strpos($c,"<h1>", $start);
		$start += strlen("<h1>");
		$end = strpos($c, "<", $start);
		return substr($c, $start, $end-$start);
	}	
	
	
	function getURLContent($url) {
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, "$url");		
		curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_USERAGENT, "PinLeague-Bot");
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);		
		$page = curl_exec ($c);
		curl_close ($c);

		$content = formatContent($page);

		return $content;		
	}

	function formatContent($raw) {
		$newlines = array("\t","\n","\r","\x20\x20","\0","\x0B");
		$content = str_replace($newlines, "", html_entity_decode($raw));
	
		return $content;
	}

	function getContentFromCrawls($crawls) {
		$curls = array();

		foreach($crawls as $crawl) {
		    $c = curl_init($crawl->url);
			$crawl->setCurl($c);

			$header[] = "Connection: keep-alive"; 
			$header[] = "Keep-Alive: 300"; 
			
			curl_setopt($crawl->curl, CURLOPT_HTTPHEADER, $header);			
		   	curl_setopt($crawl->curl, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($crawl->curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($c, CURLOPT_USERAGENT, "PinLeague-Bot");
			curl_setopt($crawl->curl, CURLOPT_FOLLOWLOCATION, 1);
		}

	    $mh = curl_multi_init();
		foreach($crawls as $crawl) {
		    curl_multi_add_handle($mh, $crawl->curl);
		}

	    $running = null;
	    do {
	        curl_multi_exec($mh, $running);
	    } while ($running);

		foreach($crawls as $crawl) {
			$response = curl_multi_getcontent($crawl->curl);
			$crawl->setResponse(formatContent($response));
		}

		return $crawls;
	}
	
	function getFlatDate($t) {
		return mktime(0,0,0,date("n",$t),date("j",$t),date("Y",$t));
	}

	function getFlatDateHour($t) {
		return mktime(date("G",$t),0,0,date("n",$t),date("j",$t),date("Y",$t));
	}
	
	function getPinIdFromUrl($u) {
		if (strpos($u, "pin") === false) {
			return "";
		} else {
			$u = str_replace("pin", "", $u);
			$u = str_replace("/", "", $u);
			return $u;
		}
	}
	

        /**
        * Returns the status of given task as managed by the job database
        *
        * If the task is already running, the query will retur a value to indicate the
        * tasks current status ("Running")
        * 
        * 
        * @param string $task, connection $conn
        * @return string the status of the given task
        */
	function getEngineStatus($task, $conn) {
		$acc = "select * from status_engines where engine='$task'";
		$acc_res = mysql_query($acc,$conn) or die(mysql_error());
		while ($a = mysql_fetch_array($acc_res)) {
			$status = $a['status'];
			$timestamp = $a['timestamp'];
		}
		
		if (!$status) {
			return "None";
		} else if ((time() - $timestamp) > (60*20)) {
			return "Expired";
		} else {
			return $status;
		}			
	}

	function setEngineStatus($task, $status, $conn) {
            
		$time = time();
		$sql = "REPLACE into status_engines values (\"$task\", \"$status\", \"$time\")";
		$resu = mysql_query($sql, $conn);			

	}




	function formatAnyInput($input) {
		$input = htmlspecialchars($input);
		$input = strip_tags($input);
		$input = mysql_real_escape_string($input);
		return $input;
	}

	function formatTextUserInput($input) {
		$input = formatAnyInput($input);
		
		return $input;
	}

?>