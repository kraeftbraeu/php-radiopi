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
			$addName = getParameter("addName");
			if(empty($addName))
				$addName = "";
			$addUrl = getParameter("addUrl");
			if(empty($addUrl))
				$addUrl = "";
			copyFile($filename, $filename, $removeIndex, $addName, $addUrl);
		}
		if($get == "shutdown")
		{
			exec("sudo shutdown -h 0"); // TODO: geht ned
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
	
	function copyFile($sourceName, $targetName, $removeIndex, $addName, $addUrl)
	{
		$content = "";
		$sourceFile = fopen($sourceName, "r");
		$rowIndex = 1;
		while(!feof($sourceFile))
		{
			$row = fgets($sourceFile, 1024);
			if(!empty($row))
			{
				if($removeIndex < 0)
					// no index to remove
					$content .= $row;
				else if($rowIndex == 1)
					// first row "#EXTM3U" is neccessary
					$content .= $row;
				else if ($rowIndex != 2 * $removeIndex
					  && $rowIndex != 2 * $removeIndex + 1)
					// keep everything except row to remove
					$content .= $row;
				$rowIndex++;
			}
		}
		fclose($sourceFile);

		// add new row
		if(!empty($addUrl))
			$content .= "#EXTINF:-1,".$addName."\r\n".$addUrl."\r\n";

		$targetFile = fopen($targetName, "w") or die("Unable to open file!");
		fwrite($targetFile, $content);
		fclose($targetFile);
	}
?>
