<?php
	function doExecution($get, $filename)
	{	
		$return = "";
		if($get == "play")
		{
			$index = getParameter("index");
			if(!empty($index))
			{
				$return .= execute("mpc clear", false);
				$return .= execute("mpc load sender.m3u", false);
				$return .= execute("mpc playlist", false);
				$return .= execute("mpc play ".$index, true);
			}
		}
		if($get == "stop")
		{
			$return .= execute("mpc stop", false);
		}
		if($get == "plus")
		{
			$return .= execute("mpc volume +20", true);
		}
		if($get == "minus")
		{
			$return .= execute("mpc volume -20", true);
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
			$return .= execute("sudo shutdown -h now", true); // TODO: geht ned
			$return .= "<ul>\n\t<li>pi is shutting down</li></ul>\n";
		}
		return $return;
	}

	function execute($order, $doLog)
	{
		$result = "";
		exec($order, $outputArray);
		if($doLog)
		{
			//$result .= $order."<br/>\n";
			$result .= "<ul>\n";
			foreach ($outputArray as $entry)
			{
				$result .= "\t<li>".$entry."</li>\n";
			}
			$result .= "</ul>\n";
		}
		return $result;
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
?>