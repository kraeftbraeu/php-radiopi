<!DOCTYPE html>
<?php
	include("local.inc");
	include("functions.php");
?>
<html lang="de">
<head>
	<meta charset="utf-8"/>
	<title>Raspberry Pi Internet Radio</title>
	<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css" />
	<script language="javascript" type="text/javascript" src="js/jquery-2.1.3.min.js"></script>
	<script language="javascript" type="text/javascript" src="js/script.js"></script>
	<script>
	$(document).ready(function()
	{
		toggle();
	});
	</script>
</head>
<body>
	<div class="container">
		<nav class="navbar navbar-dark bg-primary navbar-fill">
			<a class="navbar-brand" href="javascript:play();">play</a>
			<a class="navbar-brand" href="javascript:stop();">stop</a>
			<a class="navbar-brand" href="javascript:toggle();">toggle</a>
			<a class="navbar-brand" href="javascript:minus();">minus</a>
			<a class="navbar-brand" href="javascript:plus();">plus</a>
		</nav>
		
<?php
	$param = getParameter("do");
	if(isset($param) && $param != null)
	{
		$executionResult = doExecution($param, $filename);
		if(!empty($executionResult) && $executionResult != "<ul>\n</ul>\n")
		{
?>		<div id="status">
			<?php echo $executionResult; ?>	
		</div>
<?php
		}
	}
?>
		<table id="playlist" class="table table-hover table-striped table-primary">
			<tbody>
<?php	$file = fopen($filename, "r");
		$index = 0;
		while(!feof($file))
		{
			$sender = fgets($file, 1024);
			if(!empty($sender) && substr($sender, 0, 1) != "#")
			{
?>				<tr>
					<td onclick="javascript:play(<?php echo $index; ?>);" style="width:100%; cursor:pointer;"><?php echo $sender; ?></td>
					<td onclick="javascript:removeEntry(<?php echo $index ?>);" class="addhide" style="cursor:pointer;">-</td>
				</tr>
<?php			$index++;
			}
		}
		fclose($file);
?>				<tr id="add" class="addhide">
					<td>
						<input type="text" id="addurl" class="form-control" />
					</td>
					<td onclick="javascript:addEntry();" style="cursor:pointer;">+</td>
				</tr>
			</tbody>
		</table>
	</div>
</body>
</html>