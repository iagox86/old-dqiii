<?php

function nl2brStrict($text, $replacement = '<br />')
{
	$text = preg_replace("((\r\n)+)", trim($replacement), $text);
	$text = preg_replace("((\n)+)",   trim($replacement), $text);
	$text = preg_replace("((\r)+)",   trim($replacement), $text);

	return $text;
}

/** This will take the query string from the server and replace a parameter in it with another
 * value.  This is useful for changing which column is being searched or which page is being 
 * displayed or something like that. */
function replaceParameterURL($strFieldName, $strNewValue, $strQueryString = null)
{
	return $_SERVER['PHP_SELF'] . '?' . replaceParameterQuery($strFieldName, $strNewValue, $strQueryString);
}

function replaceParameterQuery($strFieldName, $strNewValue, $strQueryString = null)
{
	if(!$strQueryString)
		$strQueryString = $_SERVER['QUERY_STRING'];

	$strQueryString = preg_replace("/$strFieldName=.*?&/", "&", $strQueryString);
	$strQueryString = preg_replace("/$strFieldName=.*?&/", "&", $strQueryString);
	$strQueryString = preg_replace("/$strFieldName=.*?$/", "&", $strQueryString);
	$strQueryString = preg_replace("/[&]+/",               "&", $strQueryString);

	return "$strQueryString&$strFieldName=$strNewValue";
}

function array_merge_keep_keys()
{
	$args = func_get_args();
	$result = array();
	foreach($args as &$array)
	{
		if(!is_array($array))
			throw new Exception('exception_internalerror');

		foreach($array as $key=>&$value)
		{
			$result[$key] = $value;
		}
	}
	return $result;
}

function time_to_text($timestamp,$detailed=false, $max_detail_levels=8, $precision_level='second')
{
	if($timestamp == 0)
		return "never";

	$now = time();

	#If the difference is positive "ago" - negative "away"
	($timestamp >= $now) ? $action = 'away' : $action = 'ago';
  
	# Set the periods of time
	$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);

	$diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);
  
	$prec_key = array_search($precision_level,$periods);
  
	# round diff to the precision_level
	$diff = round(($diff/$lengths[$prec_key]))*$lengths[$prec_key];
  
	# if the diff is very small, display for ex "just seconds ago"
	if ($diff <= 10) 
	{
		$periodago = max(0,$prec_key-1);
		$agotxt = $periods[$periodago].'s';
		return "just $agotxt $action";
	}
  
	# Go from decades backwards to seconds
	$time = "";
	for ($i = (sizeof($lengths) - 1); $i>0; $i--) 
	{
		if($diff > $lengths[$i-1] && ($max_detail_levels > 0)) 		# if the difference is greater than the length we are checking... continue
		{
			$val = floor($diff / $lengths[$i-1]);	# 65 / 60 = 1.  That means one minute.  130 / 60 = 2. Two minutes.. etc
			$time .= $val ." ". $periods[$i-1].($val > 1 ? 's ' : ' ');  # The value, then the name associated, then add 's' if plural
			$diff -= ($val * $lengths[$i-1]);	# subtract the values we just used from the overall diff so we can find the rest of the information
			if(!$detailed) { $i = 0; }	# if detailed is turn off (default) only show the first set found, else show all information
				$max_detail_levels--;
		}
	}
 
	# Basic error checking.
	if($time == "") {
		return "Error-- Unable to calculate time.";
	} else {
		return $time.$action;
	}
}

?>
