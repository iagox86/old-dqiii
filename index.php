<?php

	require_once('include/settings.php');
	require_once('cls/clsDB.php');

	/* Get the ingredients. */
	$arrAllIngredients = clsDB::getListStatic('ingredient');
	$arrIntIDs = array();
	foreach($arrAllIngredients as $objIngredient)
		$arrIntIDs[] = $objIngredient->get('item_required_id');
	$arrIntIDs = array_unique($arrIntIDs);
	$arrIngredients = array();
	foreach($arrIntIDs as $intID)
	{
		$objItem = new clsDB('item', $intID);
		$arrIngredients[$intID] = $objItem->get('name');
	}
	asort($arrIngredients);

	/* Set the cookie to remember ingredients. */
	if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'search')
	{
		/* Set cookies. */
		$arrIDs = array();
		foreach($arrIngredients as $id=>$name)
		{
			if(isset($_REQUEST['item'.$id]))
				$arrIDs[] = $id;
		}
		setcookie('ingredients', join(',', $arrIDs), time()+60*60*24*365*5);
	}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<title>Dragon Quest VIII Alchemy Calculator</title>

	<link href="include/DQVIII.css" rel="stylesheet" type="text/css">

	<script type="text/javascript" src="include/js/functions.js"></SCRIPT>

	<script type='text/javascript'>
	</script>
</head>

<body>
	<h2>Dragon Quest VIII Alchemy Generator</h2>
<?php

	$strAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

	print "<ul>";
	print "<li><a href='index.php'>Generator</a></li>";
	print "<li><a href='index.php?action=show'>All Items</a></li>";
	print "<ul>";
	$arrTypes = clsDB::getListStatic('type');
	foreach($arrTypes as $objType)
		print "<li><a href='index.php?action=show&" . $objType->getIDPair() . "'>" . $objType->get('name') . "</a></li>";
	print "</ul>";
	print "</ul>";

	if($strAction == '')
	{
		/* Parse the cookie. */
		$arrSetIDs = array();
		if(isset($_COOKIE['ingredients']))
			$arrSetIDs = split(',', $_COOKIE['ingredients']);

		print "<form action='index.php' method='get'>";
		print "<input type='hidden' name='action' value='search'>";
		foreach($arrIngredients as $id=>$name)
		{
			$set = array_search($id, $arrSetIDs) !== false;
			print "<input type='checkbox' name='item$id' " . ($set ? "CHECKED" : "") . ">$name<br>";
		}
		print "<input type='submit' value='Go'>";
		print "</form>";
	}
	else if($strAction == 'search')
	{
		$arrItems = clsDB::getListStatic('item', '', 'name');

		print "<table>";
		foreach($arrItems as $objItem)
		{
			$arrRequirements = clsDB::getListStatic('ingredient', "`ingredient_item_result_id`='" . $objItem->get('id') . "'");
			if(sizeof($arrRequirements) == 0)
				continue;

			$good = true;
			foreach($arrRequirements as $objRequirement)
				if(!isset($_REQUEST['item' . $objRequirement->get('item_required_id')]))
					$good = false;

			if($good)
			{
				$strTD = "width='150' align='center'";
				print "<tr>";

				print "<td $strTD style='font-weight: bold;'><a href='index.php?action=view&" . $objItem->getIDPair() . "'>" . $objItem->get('name') . "</td></td>";
				print "<td align='center'> = </td>";

				$arrNames = array();

				foreach($arrRequirements as $objIngredient)
					$arrNames[] = $objIngredient->getFrom('item', 'name', 'required');

				print "<td $strTD>" . join("</td><td $strTD>+</td><td $strTD>", $arrNames) . "</td>";
			}
		}
	}
	else if($strAction == 'show')
	{
		$objType = new clsDB('type');
		$objType->getFromRequest();

		$arrItems = clsDB::getListStatic('item', $objType->get('id') == 0 ? '' : "`<<foreign><item><type>>`='" . $objType->get('id') . "'", 'name');

		print "<table>";
		foreach($arrItems as $objItem)
		{
			$arrIngredients = clsDB::getListStatic('ingredient', "`ingredient_item_result_id`='" . $objItem->get('id') . "'");
			/* Items that are only ingredients will return no results. */
			if(sizeof($arrIngredients) == 0)
				continue;

			$strTD = "width='150' align='center'";
			print "<tr>";

			print "<td $strTD style='font-weight: bold;'><a href='index.php?action=view&" . $objItem->getIDPair() . "'>" . $objItem->get('name') . "</td></td>";
			print "<td align='center'> = </td>";

			$arrNames = array();

			foreach($arrIngredients as $objIngredient)
				$arrNames[] = $objIngredient->getFrom('item', 'name', 'required');

			print "<td $strTD>" . join("</td><td $strTD>+</td><td $strTD>", $arrNames) . "</td>";
		}
		print "</table>";
	}
	else if($strAction == 'view')
	{
		$objItem = new clsDB('item');
		$objItem->getFromRequest();
		$objItem->load();

		if($objItem->isNew())
			die("Item not found.");

		print "<table width='300'>";
		print "<tr><td>Name</td><td>" . $objItem->get('name') . "</td></tr>";
		print "<tr><td colspan='2'>" . nl2br($objItem->get('summary')) . "</td></tr>";
		print "<tr><td colspan='2'>" . $objItem->get('description') . "</td></tr>";
		print "</table>";
	}


?>
<h6>Dragon Quest VIII Alchemy Generator, version 1.0. Programmed by <a href='mailto:rondq8@skullsecurity.org'>Ron</a>. This page and code are public domain, although all copyrights used are property of their respective owners. Code is available upon request. No warranty or promises of any kind.</h6>
</body>
</html>

