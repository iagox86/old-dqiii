<?php
	require_once('include/settings.php');
	require_once('cls/clsDB.php');

	define('STATE_START', 0);
	define('STATE_READ_RECIPE', 1);
	define('STATE_READ_SUMMARY', 2);

	$strFilename = $argv[1];

	if(!preg_match("/^[a-zA-Z0-9._]+$/", $strFilename))
		throw new Exception("Invalid filename!");

	$file = fopen($strFilename, 'r');

	if(!$file)
		throw new Exception("Couldn't open file: $file");

	$state = STATE_START;
	$objType = new clsDB('type');
	$objItem = null;
	$objItem2 = null;
	while($strLine = fgets($file))
	{
		$strLine = trim($strLine);
		if(strlen($strLine) == 0) /* Skip blank lines. */
			continue;

		/* A line of all -'s automatically resets to STATE_START. */
		if(preg_match('/^-*$/', $strLine))
		{
			$state = STATE_START;
			$objItem = null;
			$objItem2 = null;
			continue;
		}
		switch($state)
		{
			case STATE_START:
				if(preg_match('/^\+.*/', $strLine))
				{
					$strType = substr($strLine, 1);
					$objType = clsDB::getByName('type', $strType);
					if(!$objType)
					{
						$objType = new clsDB('type');
						$objType->set('name', $strType);
						$objType->save();
					}

					print "Category is now '$strType' [" . $objType->get('id') . "]\n";
				}
				else
				{
					list($strIngredients, $strItem) = split('=', $strLine);
					$arrIngredients = split('\+', $strIngredients);

					$strItem = trim($strItem);

					if($objItem2)
						$strItem .= " (2)";

					$objItem = clsDB::getByName('item', $strItem);
					if(!$objItem)
					{
						$objItem = new clsDB('item');
						$objItem->set('name', $strItem);
					}
					$objItem->set('type_id', $objType->get('id'));
					$objItem->save();
	
					print "To make '$strItem' [" . $objItem->get('id') . "]:\n";
					foreach($arrIngredients as $strIngredient)
					{
						$strIngredient = trim($strIngredient);
						$objItemIngredient = clsDB::getByName('item', $strIngredient);
						if(!$objItemIngredient)
						{
							$objItemIngredient = new clsDB('item');
							$objItemIngredient->set('name', $strIngredient);
							$objItemIngredient->save();
						}

						$objIngredient = new clsDB('ingredient');
						$objIngredient->set('item_result_id', $objItem->get('id'));
						$objIngredient->set('item_required_id', $objItemIngredient->get('id'));
						$objIngredient->save();
	
						print "- $strIngredient\n";
					}
					print "\n";

					$state = STATE_READ_RECIPE;
				}

			break;

			case STATE_READ_RECIPE:
				if($strLine == 'or')
				{
					$objItem2 = $objItem;

					$state = STATE_START;
				}
				else
				{
					$strLine = str_replace(' / ', "\n", $strLine);
					$objItem->set('summary', $strLine);
					$objItem->save();
					if($objItem2)
					{
						$objItem2->set('summary', $strLine);
						$objItem2->save();
					}
					$state = STATE_READ_SUMMARY;
				}
			break;

			case STATE_READ_SUMMARY:
				$objItem->set('description', $objItem->get('description') . $strLine . ' ');
				$objItem->save();
				if($objItem2)
				{
					$objItem2->set('description', $strLine);
					$objItem2->save();
				}
			break;
		}
	}
		
?>
