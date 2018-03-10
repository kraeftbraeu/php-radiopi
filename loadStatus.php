<?php
include("functions.php");
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

echo json_encode($result);
?>