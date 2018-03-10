<?php
	function doExecution($get, $filename)
	{	
		$return = "";
		if($get == "play")
		{
			$index = getParameter("index");
			if(!empty($index))
			{
				exec("mpc clear");
				exec("mpc load sender.m3u");
				exec("mpc playlist");
				exec("mpc play ".$index);
			}
		}
		if($get == "stop")
		{
			exec("mpc stop");
		}
		if($get == "plus")
		{
			exec("mpc volume +20");
		}
		if($get == "minus")
		{
			exec("mpc volume -20");
		}
		if($get == "volume")
		{
			$volume = getParameter("volume");
			if(!empty($volume)) {
				exec("mpc volume ".$volume);
			}
		}
		if($get == "file")
		{
			$removeIndex = getParameter("index");
			if(empty($removeIndex) && $removeIndex != "0")
				$removeIndex = -1;
			$addUrl = getParameter("url");
			if(empty($addUrl))
				$addUrl = "";
			copyFile($filename, $filename, $removeIndex, $addUrl);
		}
		if($get == "shutdown")
		{
			exec("sudo shutdown -h now"); // TODO: geht ned
		}
		return $return;
	}

	function getParameter($param)
	{
		$value = "";
		if(isset($_GET[$param]))
			$value = $_GET[$param];
		if(empty($value) && isset($_POST[$param]))
			$value = $_POST[$param];
		return $value;
	}
	
	function copyFile($sourceName, $targetName, $removeIndex, $addUrl)
	{
		$content = "";
		$sourceFile = fopen($sourceName, "r");
		$index = 1;
		while(!feof($sourceFile))
		{
			$row = fgets($sourceFile, 1024);
			// remove old row
			if(!empty($row))
			{
				if(($removeIndex < 0 || $removeIndex != $index))
					$content .= $row;
				if(substr($row, 0, 1) != "#")
					$index++;
			}
		}
		fclose($sourceFile);

		// add new row
		if(!empty($addUrl))
			$content .= $addUrl."\r\n";

		$targetFile = fopen($targetName, "w") or die("Unable to open file!");
		fwrite($targetFile, $content);
		fclose($targetFile);
	}

	function changeVolume(is) {
		var volumeBar = $("#volume");
		volume(volumeBar.val());

		// may be called by onchange() and oninput(), shall be executed not more than once
		var other = is=="change" 
			? "input" 
			: "change";
		volumeBar.removeAttr("on" + other);
		setTimeout(function() {
			volumeBar.attr("on" + other, "changeVolume('on" + other + "')");
		}, 1000);
	}
?>