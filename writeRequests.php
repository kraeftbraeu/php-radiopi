<?php

$params = array();

$json = json_decode(file_get_contents('php://input'), true);
if($json) {
    $params['json'] = $json;
}

if($_GET) {
    $params['get'] = $_GET;
}

if($_POST) {
    $params['post'] = $_POST;
}

$now = new DateTime();
$content = ($now->format('Y-m-d H:i:s'))."\t".json_encode($params)."\r\n";
echo $content;

$targetFile = fopen("requests.txt", "w") or die("Unable to open file!");
fwrite($targetFile, $content);
fclose($targetFile);
?>