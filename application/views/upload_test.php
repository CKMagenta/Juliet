<?php 
$t = imagecreatefromjpeg("/opt/hpws/apache/htdocs/juliet/uploaded/pic/profile/13efece87c8ac67683dcb86c7a833eed.jpg");
echo "<meta charset='utf-8'><pre>";
print_r($t);
echo "</pre>";
die();
?>