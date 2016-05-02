<?php
function getLog() {
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	if ($log = fopen(LOGFILE,'r')) {
		while ($logLine = fgets($log)) {
			array_push($logLines, $logLine);
		}
		fclose($log);
	}
	return $logLines;
}

// 00000000001111111111222222222233333333334444444444555555555566666666667777777777888888888899999999990000000000111111111122
// 01234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901
// M: 2016-04-29 00:15:00.013 D-Star, received network header from DG9VH   /ZEIT to CQCQCQ   via DCS002 S
// M: 2016-04-29 19:43:21.839 DMR Slot 2, received network voice header from DL1ESZ to TG 9
// M: 2016-04-30 14:57:43.072 DMR Slot 2, received RF voice header from DG9VH to 5000
function getHeardList($logLines) {
	$heardList = array();
	foreach ($logLines as $logLine) {
		//removing invalid lines
		if(strpos($logLine,"BS_Dwn_Act")) {
			break;
		} else if(strpos($logLine,"invalid access")) {
			break;
		}
		
		$timestamp = substr($logLine, 3, 19);
		$mode = substr($logLine, 27, strpos($logLine,",") - 27);
		$callsign2 = substr($logLine, strpos($logLine,"from") + 5, strpos($logLine,"to") - strpos($logLine,"from") - 6);
		$callsign = $callsign2;
		if (strpos($callsign2,"/") > 0) {
			$callsign = substr($callsign2, 0, strpos($callsign2,"/"));
		}
		$callsign = trim($callsign);
		$id ="";
		if ($mode == "D-Star") {
			$id = substr($callsign2, strpos($callsign2,"/") + 1);
		}
		$target = substr($logLine, strpos($logLine, "to") + 3); 
		$source = "RF";
		if (strpos($logLine,"network") > 0 ) {
			$source = "Network";
		}
		
		if ( strlen($callsign <7) ) {
			array_push($heardList, array($timestamp, $mode, $callsign, $id, $target, $source));
		}
	}
	return $heardList;
}

function getLastHeard($logLines) {
	$lastHeard = array();
	$heardCalls = array();
	$heardList = getHeardList($logLines);
	array_multisort($heardList,SORT_DESC);
	foreach ($heardList as $listElem) {
		if ( ($listElem[1] == "D-Star") || (startsWith($listElem[1], "DMR")) ) {
			if(!(array_search($listElem[2]."#".$listElem[1].$listElem[3], $heardCalls) > -1)) {
				array_push($heardCalls, $listElem[2]."#".$listElem[1].$listElem[3]);
				array_push($lastHeard, $listElem);
			}
		}
	}
	return $lastHeard;
}

function getActualMode($logLines) {
	array_multisort($logLines,SORT_DESC);
	foreach ($logLines as $logLine) {
		if (strpos($logLine, "Mode set to")) {
			return substr($logLine, 39);
			//break;
		}	
	}
	return "Idle";
}

function getActualLink($logLines, $mode) {
//M: 2016-05-02 07:04:10.504 D-Star link status set to "Verlinkt zu DCS002 S"
	array_multisort($logLines,SORT_DESC);
	switch ($mode) {
    case "D-Star":
        foreach ($logLines as $logLine) {
			if (strpos($logLine, "D-Star link status set to")) {
				return substr($logLine, 54, strlen($logLine) - 56);
			}	
		}
        break;
    case "DMR Slot 1":
        return "still to be implemented";
        break;
    case "DMR Slot 2":
        return "still to be implemented";
        break;
}
	
	return "still to be implemented";
}

//Some basic inits
$logLines = getLog();
?>