<?php
	$dir = dirname($_SERVER["PHP_SELF"])."/";
	$icons = "/icons/";
	$icons_dir = $_SERVER["DOCUMENT_ROOT"]."/icons/";
	$icons_extension = ".gif";

	$_GET = array();
	foreach(preg_split("/[;&]/", $_SERVER["QUERY_STRING"]) as $a)
	{
		$a = explode("=", $a, 2);
		if(count($a) < 2) continue;
		$_GET[urldecode($a[0])] = urldecode($a[1]);
	}

	$sortby = (isset($_GET["C"]) && in_array($_GET["C"], array("N", "M", "S", "D"))) ? $_GET["C"] : "N";
	$sorto = (isset($_GET["O"]) && $_GET["O"] == "D"); # Order: descending

	$size_units = array(
		" " => 1,
		"K" => 1024,
		"M" => 1024*1024,
		"G" => 1024*1024*1024
	);

	if(!function_exists('mime_content_type'))
	{
		function mime_content_type($f)
		{
			return exec('file -bi '.escapeshellarg($f));
		}
	}

	function sort_callback($a, $b)
	{
		global $sortby, $sorto;

		$result = 0;

		switch($sortby)
		{
			case 'M': # Last modified
				if($a[1] > $b[1]) $result = 1;
				elseif($a[1] < $b[1]) $result = -1;
				else $result = 0;
				break;
			case 'S': # Size
				if($a[2] > $b[2]) $result = 1;
				elseif($a[2] < $b[2]) $result = -1;
				else $result = 0;
				break;
			case 'D': # Description
				$result = strcmp($a[3], $b[3]);
				break;
			default: # Name
				$result = strcmp($a[0], $b[0]);
				break;
		}

		if($sorto) # Order: descending
			$result *= -1;

		return $result;
	}

	function format_name($name)
	{
		$cols = 23;

		$print_name = $name;
		if(strlen($print_name) > $cols)
			$print_name = substr($print_name, 0, $cols-3)."...>";

		$text = get_image($name)." <a href=\"".htmlspecialchars($name)."\">".htmlspecialchars($print_name)."</a>";
		if(strlen($print_name) < $cols)
			$text .= str_repeat(" ", $cols-strlen($print_name));
		return $text;
	}

	function format_last_modified($last_modified)
	{
		return date("d-M-Y H:i", $last_modified);
	}

	function format_size($size)
	{
		global $size_units;

		$cols = 5;
		$cols_number = $cols-1; # One col for the unit

		$unit = " ";
		foreach($size_units as $k=>$u)
		{
			if($size >= $u) $unit = $k;
		}

		$united_size = $size/$size_units[$unit];
		$united_size_f = $united_size - floor($united_size);
		$united_size_i = floor($united_size);

		$size_string = "".$united_size_i;
		$size_length = strlen($size_string);
		if($size_length-$cols_number > 2)
		{
			$size_string .= ".".substr($united_size_f, 0, $size_length-$cols_number-1);
			$size_length = strlen($size_string);
		}
		$size_string = sprintf("%".$cols_number."s%s", $size_string, $unit);
		return $size_string;
	}

	function format_description($description)
	{
		return $description;
	}

	function get_image($file_name, $alt_scheme="[%3s]")
	{
		global $icons,$icons_dir,$icons_extension;

		if($file_name == null)
		{
			$image_name = "blank";
			$image_alt = "";
		}
		elseif(substr($file_name, -1) == "/")
		{
			$image_name = "folder";
			$image_alt = "DIR";
		}
		else
		{
			$mimetype = mime_content_type($file_name);
			if($mimetype)
				list($mimetype) = explode("/", $mimetype);
			if(file_exists($icons_dir.$mimetype.$icons_extension))
			{
				$image_name = $mimetype;
				switch($image_name)
				{
					case "text": $image_alt = "TXT"; break;
					case "image": $image_alt = "IMG"; break;
					default: $image_alt = strtoupper(substr($image_name, 0, 3));
				}
			}
			else
			{
				$image_name = "unknown";
				$image_alt = "";
			}
		}


		$image = "<img src=\"".htmlspecialchars($icons.$image_name.$icons_extension)."\" alt=\"".sprintf($alt_scheme, $image_alt)."\">";
		return $image;
	}

	function expand_column($column_text, $cols)
	{
		$len = strlen($column_text);
		$spaces = $cols-$len;
		if($spaces > 0)
			$column_text .= str_repeat(" ", $spaces);
		return $column_text;
	}

	$files = array();
	$dh = opendir(".");
	while(($fname = readdir($dh)) !== false)
	{
		if($fname[0] == '.') continue;
		if(is_dir($fname)) $fname .= "/";
		$files[] = array($fname, filemtime($fname), filesize($fname), "");
	}
	closedir($dh);

	usort($files, "sort_callback");

	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 3.2 Final//EN\">\n";
	echo "<html>\n";
	echo " <head>\n";
	echo "  <title>Index of ".htmlspecialchars($dir)."</title>\n";
	echo " </head>\n";
	echo " <body>\n";
	echo "<h1>Index of ".htmlspecialchars($dir)."</h1>\n";
	echo "<pre>".get_image(null, "Icon %s")." <a href=\"?C=N;O=".(($sortby != "N" || $sorto) ? "A" : "D")."\">Name</a>                    <a href=\"?C=M;O=".(($sortby != "M" || $sorto) ? "A" : "D")."\">Last modified</a>      <a href=\"?C=S;O=".(($sortby != "S" || $sorto) ? "A" : "D")."\">Size</a>  <a href=\"?C=D;O=".(($sortby != "D" || $sorto) ? "A" : "D")."\">Description</a><hr>";
	if($dir != "/")
	{
		$parent = dirname($dir);
		if($parent != "/") $parent .= "/";
		echo "<img src=\"".htmlspecialchars($icons."back".$icons_extension)."\" alt=\"[DIR]\"> <a href=\"".htmlspecialchars($parent)."\">Parent Directory</a>                             -   \n";
	}

	foreach($files as $file)
		echo format_name($file[0])." ".format_last_modified($file[1])." ".format_size($file[2])." ".format_description($file[3])."\n";
	echo "<hr></pre>\n";
	echo $_SERVER["SERVER_SIGNATURE"];
	echo "</body></html>\n";
?>
