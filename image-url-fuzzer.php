<?php
	// Track total execution time
	$time_start = microtime(true); 
	
	echo "\nBEGIN PROBE\n";
	
	/* Variables */
	
	// $threads determines our max amount of threads, this can be tuned with your source server strength to run the script faster
	$threads = 10;
	
	// The base url to check
	$base_url = 'https://www.acererak.com/';
	// The file extension to search for
	$file_type = '.jpg';
	
	// Data for what urls to check on the domain
	// $charset controls the pool of characters to check
	$charset = 'abcdefghijklmnopqrstuvwxyz';
	// $str controls the format of strings to check, in this case we are checking all 2 character strings with our given charset. 'aaa' would check three character long strings, and so on
	$str = 'aa';
	
	/* Code Body */
	
	echo "\nChecking all urls on: " . $base_url;
	echo "\nContaining any of these characters: " . $charset;
	echo "\nWith this string configuration: " . $str;
	
	// Our array of urls to check
	$urls = array();
	
	// Build array of urls to check
	echo "\n\nBuilding url array...";
	do {
	    $file_name = $str;
		$built_url = $base_url . $file_name . $file_type;
		array_push($urls,$built_url);
	} while (($str = next_iteration($str, $charset)) !== false);
	echo "DONE";

	// Check that array with our runRequests function
	echo "\nChecking array...";
	$capture = runRequests($urls);
	echo "DONE";
	
	// Format the outputted array by removing all non-hits
	echo "\nFormatting array...";
	$total_checked =  sizeof($capture);
	$capture = removeElementWithValue($capture, "result", 2);
	echo "DONE\n\n";
	
	echo "Successful Results:\n";
	print_r($capture);
	
	// Ending data
	echo "\n\nCheck Completed.\n";
	echo "\nNumber of Urls Checked: " . $total_checked . "\n";
	echo 'Total execution time in seconds: ' . (microtime(true) - $time_start) . "\n\n";

	/* Functions */

	// Function to run through strings
	function next_iteration($str, $charset) {
	    // last character in charset that requires a carry-over
	    $copos = strlen($charset)-1;
	    // starting with the least significant digit
	    $i = strlen($str)-1;
	    do {
	        // reset carry-over flag
	        $co = false;
	        // find position of digit in charset
	        $pos = strpos($charset, $str[$i]);
	        if ($pos === false) {
	            // invalid input char at position $i
	            return false;
	        }
	        // check whether it’s the last character in the charset
	        if ($pos === $copos) {
	            // we need a carry-over to the next higher digit
	            $co = true;
	            // check whether we’ve already reached the highest digit
	            if ($i === 0) {
	                // no next iteration possible due to fixed string length
	                return false;
	            }
	            // set current digit to lowest charset digit
	            $str[$i] = $charset[0];
	        } else {
	            // if no carry-over is required, simply use the next higher digit
	            // from the charset
	            $str[$i] = $charset[$pos+1];
	        }
	        // repeat for each digit until there is no carry-over
	        $i--;
	    } while ($co);
	    return $str;
	}
	
	// Function to multi-thread the curl requests
	function runRequests($url_array, $thread_width = 10) {
	    $threads = 0;
	    $master = curl_multi_init();
	    $curl_opts = array(CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_MAXREDIRS => 5,
	        CURLOPT_CONNECTTIMEOUT => 15,
	        CURLOPT_TIMEOUT => 15,
	        CURLOPT_RETURNTRANSFER => TRUE);
	    $results = array();
	
	    $count = 0;
	    foreach($url_array as $url) {
	        $ch = curl_init();
	        $curl_opts[CURLOPT_URL] = $url;
	
	        curl_setopt_array($ch, $curl_opts);
	        curl_multi_add_handle($master, $ch); //push URL for single rec send into curl stack
	        $results[$count] = array("url" => $url, "handle" => $ch);
	        $threads++;
	        $count++;
	        if($threads >= $thread_width) { //start running when stack is full to width
	            while($threads >= $thread_width) {
	                usleep(100);
	                while(($execrun = curl_multi_exec($master, $running)) === -1){}
	                curl_multi_select($master);
	                // a request was just completed - find out which one and remove it from stack
	                while($done = curl_multi_info_read($master)) {
	                    foreach($results as &$res) {
	                        if($res['handle'] == $done['handle']) {
		                        if (strlen(curl_multi_getcontent($done['handle'])) >= 1000) {
			                        $res['result'] = "1";
		                        } else {
			                        $res['result'] = "2";
		                        }
	                        }
	                    }
	                    curl_multi_remove_handle($master, $done['handle']);
	                    curl_close($done['handle']);
	                    $threads--;
	                }
	            }
	        }
	    }
	    do { //finish sending remaining queue items when all have been added to curl
	        usleep(100);
	        while(($execrun = curl_multi_exec($master, $running)) === -1){}
	        curl_multi_select($master);
	        while($done = curl_multi_info_read($master)) {
	            foreach($results as &$res) {
	                if($res['handle'] == $done['handle']) {
		                if (strlen(curl_multi_getcontent($done['handle'])) >= 1000) {
			                $res['result'] = "1";
		                } else {
			                $res['result'] = "2";
		                }
	                }
	            }
	            curl_multi_remove_handle($master, $done['handle']);
	            curl_close($done['handle']);
	            $threads--;
	        }
	    } while($running > 0);
	    curl_multi_close($master);
	    return $results;
	}
	
	// Function to format the array
	function removeElementWithValue($array, $key, $value){
	     foreach($array as $subKey => $subArray){
	          if($subArray[$key] == $value){
	               unset($array[$subKey]);
	          }
	     }
	     return $array;
	}

?>
