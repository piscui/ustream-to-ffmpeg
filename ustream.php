<?php
error_reporting(0);

$amf_url = 'http://cgw.ustream.tv/Viewer/getStream/1/[[[ChannelID]]].amf';
$swf_url = "http://static-cdn1.ustream.tv/swf/live/viewer.rsl:96.swf";
$rtmp_url = "";
$result = "";
$error = false;

$url = urldecode(trim($_REQUEST['url']));

if ($url) {
	$p = explode('#', $url);
	if (count($p) > 1) $url = $p[0];
	$ustream = curl_grab_page($url);

	preg_match_all('/cid=(.+?)&amp/', $ustream, $matches, PREG_SET_ORDER);
	$channel_id = trim($matches[1][1]);
	
	if (strlen($channel_id) == 0) {
		$result = '<font color=red><b>Error: Unable to determine channel ID.</b></font><br/><br/>';
		$error = true;
	}
	else {
		preg_match_all('/<meta property="og:title" content="(.*)" \/>/', $ustream, $matches, PREG_SET_ORDER);
		$channel_title = trim($matches[0][1]);
		
		// 20120727 -- Ustream HTML was updated
		//preg_match_all('/<meta property="og:image" content="(.*)" \/>/', $ustream, $matches, PREG_SET_ORDER);
		//$channel_thumb = trim($matches[0][1]);
		preg_match_all('/<img class="image" alt="(.*)" width="66" height="66" src="(.*)" rel="(.*)" \/>/', $ustream, $matches, PREG_SET_ORDER);
		$channel_thumb = trim($matches[0][2]);
		
		$data = curl_grab_page(str_replace('[[[ChannelID]]]', $channel_id, $amf_url));

		preg_match_all('/streamName\W\W\W(.+?)\x00/', $data, $matches, PREG_SET_ORDER);
		$play_path = trim($matches[0][1]);

		preg_match_all('/cdnUrl\W\W\S(.+?)\x00/', $data, $matches, PREG_SET_ORDER);
		$tc_url = trim($matches[0][1]);

		preg_match_all('/fmsUrl\W\W\S(.+?)\x00/', $data, $matches, PREG_SET_ORDER);
		$tc_url2 = trim($matches[0][1]);
		
		if (strlen($tc_url) == 0) {
			if (strlen($tc_url2) == 0) {
				$result = '<font color=red><b>Error: Not a live feed.</b></font><br/><br/>';
				$error = true;
			}
			else {
				$new = str_replace('/ustreamVideo', ':1935/ustreamVideo', $tc_url2);
				$rtmp_url = $new . '/';
			}
		}
		else {
			$rtmp_url = $tc_url;
		}
	}

	if (!$error) {
		$rtmp = $rtmp_url . " playpath=" . $play_path . " swfUrl=" . $swf_url . " swfVfy=1 live=1";
		$ffmpeg = "ffmpeg -i \"{$rtmp_url} playpath={$play_path} swfUrl={$swf_url} swfVfy=1 live=1\"";
		$rtmpdump = "rtmpdump -r \"{$rtmp_url}\" -y \"{$play_path}\" -s \"{$swf_url}\" -W \"{$swf_url}\" --live --quiet";

		$result .= "<hr size='1'/><br/>\nRTMP URL (Serviio):<br/>\n<textarea rows=3 cols=70>$rtmp</textarea><br/><br/>\n";
		$result .= "Title: <b>$channel_title</b><br/><br/>\n";
		$result .= "<img src='$channel_thumb' /><br/><br/>\n";
		
		$result .= "<br/><br/><br/><br/>\n";
		$result .= "<p><b>ffmpeg command:</b><br/><textarea rows=3 cols=70>$ffmpeg</textarea></p>\n";
		$result .= "<p><b>rtmpdump command:</b><br/><textarea rows=3 cols=70>$rtmpdump</textarea></p>\n";
	}
}


function curl_grab_page($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, "http://www.ustream.tv");

    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US;rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729)");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_POST, FALSE);
    ob_start();
    return curl_exec ($ch); // execute the curl command
    ob_end_clean();
    curl_close ($ch);
    unset($ch);
}

?>
<html>
<head>
<title>USTREAM URL Converter</title>
<style>
html,body,input,textarea {
font-size:11px;
font-family:verdana,arial;
}
</style>
</head>
<body>
<form method="GET">
USTREAM URL:<br/>
<textarea rows=3 cols=70 name="url"><?php echo trim($_REQUEST['url']); ?></textarea><br/>
<input type="submit" /> <input type="button" value="Reset" onclick="location.href='./ustream.php';" />	
</form>

<?php echo $result; ?>
</body>
</html>