<?php
	/*// allow cross-origin request
	header('Access-Control-Allow-Origin: *'); 
	$method = $_SERVER['REQUEST_METHOD'];*/
	/*// return OPTIONS request
	if($method === 'OPTIONS')
	{
		header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS'); 
		header('Access-Control-Allow-Headers: Accept, Accept-CH, Accept-Charset, Accept-Datetime, Accept-Encoding, Accept-Ext, Accept-Features, Accept-Language, Accept-Params, Accept-Ranges, Access-Control-Allow-Credentials, Access-Control-Allow-Headers, Access-Control-Allow-Methods, Access-Control-Allow-Origin, Access-Control-Expose-Headers, Access-Control-Max-Age, Access-Control-Request-Headers, Access-Control-Request-Method, Age, Allow, Alternates, Authentication-Info, Authorization, C-Ext, C-Man, C-Opt, C-PEP, C-PEP-Info, CONNECT, Cache-Control, Compliance, Connection, Content-Base, Content-Disposition, Content-Encoding, Content-ID, Content-Language, Content-Length, Content-Location, Content-MD5, Content-Range, Content-Script-Type, Content-Security-Policy, Content-Style-Type, Content-Transfer-Encoding, Content-Type, Content-Version, Cookie, Cost, DAV, DELETE, DNT, DPR, Date, Default-Style, Delta-Base, Depth, Derived-From, Destination, Differential-ID, Digest, ETag, Expect, Expires, Ext, From, GET, GetProfile, HEAD, HTTP-date, Host, IM, If, If-Match, If-Modified-Since, If-None-Match, If-Range, If-Unmodified-Since, Keep-Alive, Label, Last-Event-ID, Last-Modified, Link, Location, Lock-Token, MIME-Version, Man, Max-Forwards, Media-Range, Message-ID, Meter, Negotiate, Non-Compliance, OPTION, OPTIONS, OWS, Opt, Optional, Ordering-Type, Origin, Overwrite, P3P, PEP, PICS-Label, POST, PUT, Pep-Info, Permanent, Position, Pragma, ProfileObject, Protocol, Protocol-Query, Protocol-Request, Proxy-Authenticate, Proxy-Authentication-Info, Proxy-Authorization, Proxy-Features, Proxy-Instruction, Public, RWS, Range, Referer, Refresh, Resolution-Hint, Resolver-Location, Retry-After, Safe, Sec-Websocket-Extensions, Sec-Websocket-Key, Sec-Websocket-Origin, Sec-Websocket-Protocol, Sec-Websocket-Version, Security-Scheme, Server, Set-Cookie, Set-Cookie2, SetProfile, SoapAction, Status, Status-URI, Strict-Transport-Security, SubOK, Subst, Surrogate-Capability, Surrogate-Control, TCN, TE, TRACE, Timeout, Title, Trailer, Transfer-Encoding, UA-Color, UA-Media, UA-Pixels, UA-Resolution, UA-Windowpixels, URI, Upgrade, User-Agent, Variant-Vary, Vary, Version, Via, Viewport-Width, WWW-Authenticate, Want-Digest, Warning, Width, X-Content-Duration, X-Content-Security-Policy, X-Content-Type-Options, X-CustomHeader, X-DNSPrefetch-Control, X-Forwarded-For, X-Forwarded-Port, X-Forwarded-Proto, X-Frame-Options, X-Modified, X-OTHER, X-PING, X-PINGOTHER, X-Powered-By, X-Requested-With'); 
		exit;
	}*/
	$playlistFile = "sender.m3u";
	$playlistUrl = "http://192.168.2.100/radiopi/"; // TODO: mpc load ohne url lauffähig machen!

	$param = getParameter("do");
	if(empty($param))
	{
		http_response_code(400);
		exit("parameter 'do' is missing");
	}

	if($param == "play")
	{
		$index = getParameter("index");
		if(!is_numeric($index))
		{
			http_response_code(400);
			exit("parameter 'index' is missing or not numeric");
		}
		exec("mpc clear");
		exec("mpc load ".$playlistUrl.$playlistFile);
		exec("mpc playlist");
		exec("mpc play ".$index);
	}
	elseif($param == "stop")
	{
		exec("mpc stop");
	}
	elseif($param == "plus")
	{
		exec("mpc volume +20");
	}
	elseif($param == "minus")
	{
		exec("mpc volume -20");
	}
	elseif($param == "volume")
	{
		$volume = getParameter("volume");
		if(!empty($volume) && is_int($volume)) {
			exec("mpc volume ".$volume);
		}
	}
	else if($param == "load")
	{
		echo json_encode(loadList());
		exit(0);
	}
	elseif($param == "save")
	{
		$senders = getParameter("senders");
		error_log("set senders: ".print_r($senders, true));
		echo json_encode(saveList());
		exit(0);
	}
	elseif($param == "shutdown")
	{
		exec("sudo shutdown -h 0"); // TODO: geht ned
		exit(0);
	}
	elseif($param == "status")
	{
	}
	else
	{
		http_response_code(400);
		exit("invalid request");
	}
	echo getStatus();
	exit(0);

	function getParameter($param)
	{
		$data = json_decode(file_get_contents('php://input'), true);
		$value = $data[$param];
		if(empty($value) && isset($_GET[$param]))
			$value = $_GET[$param];
		if(empty($value) && isset($_POST[$param]))
			$value = $_POST[$param];
		return $value;
	}

	function getStatus() {
		$result = [];
		exec("mpc", $outputArray);
		if(count($outputArray) > 1)
			$result['playing'] = $outputArray[0];
		foreach ($outputArray as $entry)
		{
			if(substr($entry, 0, 7) === "volume:")
			{
				$volume = substr($entry, 7, 3);
				if(strpos($volume, " ") === 0)
					$volume = substr($volume, 1);
				$result['volume'] = $volume;
			}
		}
		error_log("reload status: ".json_encode($result));
		return json_encode($result);
	}

	function loadList() {
		global $playlistFile;
		$file = fopen($playlistFile, "r");
		fgets($file, 1024); // ignore first row
		$senders = array();
		$i = 1000; // max. 1000 entries
		while(!feof($file) && $i-->0)
		{
			$senderName = fgets($file, 1024);
			$senderUrl = fgets($file, 1024);
			if(!empty($senderName) && substr($senderName, 0, 11) == "#EXTINF:-1," && !empty($senderUrl) && substr($senderUrl, 0, 1) != "#")
			{
				array_push($senders, array(
					"name" => trim(substr($senderName, 11)), 
					"url" => trim($senderUrl)
				));
			}
		}
		fclose($file);
		return $senders;
	}

	function saveList($sendersToSave) {
		global $playlistFile;
		$content = "#EXTM3U\n";
		$newSenders = array();
		foreach($sendersToSave as $sender)
		{
			if(!empty($sender["url"]))
			{
				$content .= "#EXTINF:-1,".$sender["name"]."\n".$sender["url"]."\n";
				array_push($newSenders, array(
					"name" => trim($sender["name"]), 
					"url" => trim($sender["url"])
				));
			}
		}
		$targetFile = fopen($playlistFile, "w") or die("Unable to open file ".$playlistFile);
		fwrite($targetFile, $content);
		fclose($targetFile);
		return $newSenders;
	}
?>