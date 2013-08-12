<?php

// mailchimp_getlistIDs.php
// Quick script to get the list IDs for your MailChimp account

require_once('inc/MCAPI.class.php');
require_once('inc/mcapikey.php');

$returned_lists = $mc_api->lists(); // Modify as needed if you need to display more than 25 lists; see http://apidocs.mailchimp.com/api/1.3/lists.func.php for more details
foreach ($returned_lists as $returned_list)
{
	echo "Name: {$returned_list['name']}\tID: {$returned_list['id']}\n";
}

if ($mc_api->errorCode)
{
	echo $mc_api->errorMessage . "\n";
}

?>
