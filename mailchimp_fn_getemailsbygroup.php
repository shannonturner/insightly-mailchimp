
<?php

require_once('mcapikey.php');
require_once('MCAPI.class.php');
require_once('mailchimp_fn_export.php');

function mailchimp_getworkemailsbytag($mc_api, $mc_apikey, $mc_list_id, $mc_group_id, $mc_group_name)
{
	/***
	*
	* mailchimp_getworkemailsbytag($mc_list_id, $mc_group_id, $mc_group_name)
	*       returns an indexed array of the subscribers that are in the specified list and members of the specified group
	*
	*       Remember that $mc_list_id and $mc_group_id are the IDs returned by the MailChimp API, NOT the web IDs for these groups.
	*/

	if ($mc_list_id == '' or $mc_group_id == '' or $mc_group_name == '')
	{
		return "[ERROR] mailchimp_getworkemailsbytag(): Must include a valid MailChimp list ID, a MailChimp group ID, and a MailChimp group name to run properly!\n";
	}

	$status = 'subscribed';
	list($header, $mc_subscribed_raw) = mc_export_list($mc_apikey, $status, $mc_list_id, $mc_group_id, $mc_group_name);
	list($mc_subscribed, $mc_subscribed_fulldetails) = process_raw_mc_export($header, $mc_subscribed_raw);

	$subscribers = array();
	foreach ($mc_subscribed_fulldetails as $subscriber)
	{
		$subscribers[] = $subscriber['First Name'] . "\t" . $subscriber['Last Name'] . "\t" . strtolower($subscriber['Email Address']) . "\t" . $mc_group_name . "\n";
	}

	return $subscribers;
}

?>
