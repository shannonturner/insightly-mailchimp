
<?php

require_once('mcapikey.php');
require_once('MCAPI.class.php');
require_once('mailchimp_fn_export.php');

/* Definitions for mailchimp_addgroups() used to add groups to people in a particular MailChimp list */

function mailchimp_addgroups($mc_api, $mc_apikey, $email_addresses, $mc_list_id, $mc_group_id, $mc_grouping_name, $mc_group_name)
{
	/***
	*
	* mailchimp_addgroups($email_addresses, $mc_list_id, $mc_group_id, $mc_grouping_name, $mc_group_name
	*       returns string on error, returns 1 on success
	*
	*       For every email address in the array or filename $email_addresses, checks whether that email is signed up already for the specified group in $mc_list_id
	*               If not, and if they aren't unsubscribed or cleaned, signs them up for the specified group
	*
	*       Remember that $mc_list_id and $mc_group_id are the IDs returned by the MailChimp API, NOT the web IDs.
	*/

	if ($email_addresses == '' or $mc_list_id == '' or $mc_group_id == '' or $mc_grouping_name == '' or $mc_group_name == '')
	{
		return "[ERROR] mailchimp_addgroups(): Must include an array of emails to import, a MailChimp list ID, a MailChimp parent-group name, a MailChimp group ID, and a MailChimp group name to run properly!\n";
	}

	// Handling to allow $email_addresses to operate on a file if a string is given for $email_addresses, or an array of email addresses is an array is given
	if (!is_array($email_addresses))
	{
		// If it's not an array, try to open this as a file

		$email_filecontents = file_get_contents($email_addresses);
		if ($email_filecontents === False)
		{
			return "[ERROR] Failed to open file: {$email_addresses}\n";
		}
		$email_addresses = explode("\n", $email_filecontents);
	}

	if ($_ENV['USER'] != '') { echo "Number of email addresses to import: " . count($email_addresses) . "\n"; }

	// MailChimp Exports: Subscribed, Unsubscribed, Cleaned
	if ($_ENV['USER'] != '') { echo "Getting list of MailChimp subscribers for the list #{$mc_list_id} and who are members of " . $mc_grouping_name . " - " . $mc_group_name . ":"; }
	$status = 'subscribed';
	list($header, $mc_subscribed_raw) = mc_export_list($mc_apikey, $status, $mc_list_id, $mc_group_id, $mc_group_name);
	list($mc_subscribed, $mc_subscribed_fulldetails) = process_raw_mc_export($header, $mc_subscribed_raw);

	if ($_ENV['USER'] != '') { echo "Getting list of people unsubscribed from MailChimp for the list #{$mc_list_id}:"; }
	$status = 'unsubscribed';
	list($header, $mc_unsubscribed_raw) = mc_export_list($mc_apikey, $status, $mc_list_id);
	list($mc_unsubscribed, $mc_unsubscribed_fulldetails) = process_raw_mc_export($header, $mc_unsubscribed_raw);

	if ($_ENV['USER'] != '') { echo "Getting list of people cleaned from MailChimp for the list #{$mc_list_id}:"; }
	$status = 'cleaned';
	list($header, $mc_cleaned_raw) = mc_export_list($mc_apikey, $status, $mc_list_id);
	list($mc_cleaned, $mc_cleaned_fulldetails) = process_raw_mc_export($header, $mc_cleaned_raw);

	// Anyone in $mc_subscribed has this group already.  I can skip $mc_subscribed; I don't have to add these.
	// I also don't want to add any email addresses that have been unsubscribed previously or cleaned.
	$emails_to_add = array_diff($email_addresses, $mc_subscribed, $mc_unsubscribed, $mc_cleaned);

	if ($_ENV['USER'] != '') { echo "After removing currently subscribed group members as well as unsubscribes and cleans, there are " . count($emails_to_add) . " emails to import.\n"; }

	// In theory, anything that is not one of these merge fields is a group name
	$standard_merge_fields = array('Email Address', 'First Name', 'Last Name', 'MEMBER_RATING', 'OPTIN_TIME', 'OPTIN_IP', 'CONFIRM_TIME', 'CONFIRM_IP', 'LATITUDE', 'LONGITUDE', 'GMTOFF', 'DSTOFF', 'TIMEZONE', 'CC', 'REGION', 'UNSUB_TIME', 'UNSUB_CAMPAIGN_TITLE', 'UNSUB_CAMPAIGN_ID', 'UNSUB_REASON', 'UNSUB_REASON_OTHER', 'LAST_CHANGED', 'NEW-EMAIL', 'GROUPINGS', 'MC_LOCATION', 'MC_LANGUAGE', 'MC_NOTES', 'address', 'birthday', 'date', 'dropdown', 'image', 'multi_choice', 'number', 'phone', 'website', 'zip', 'EMAIL_TYPE');

	$email_batch = array();
	if (count($emails_to_add) > 0)
	{
		foreach ($emails_to_add as $email_to_add)
		{
			if (trim($email_to_add) == '')
			{
				continue;
			}

			$email_batch[$email_to_add] = array();
			$email_batch[$email_to_add]['EMAIL'] = $email_to_add;
			$email_batch[$email_to_add]['GROUPINGS'] = array(
					0 => array(
					'name' => $mc_grouping_name,
					'groups' => $mc_group_name
					)
			);
		}
	}

	// Change these variables as needed
	$email_type = 'html';
	$double_optin = False;
	$update_existing = True;
	$replace_interests = False;

	if (count($email_batch) > 0)
	{
		if ($_ENV['USER'] != '') { echo "Attempting to subscribe these email addresses to the specified groups ...\n"; }

		usleep(340000);
		$subscribe_results = $mc_api->listBatchSubscribe($mc_list_id, $email_batch, $double_optin, $update_existing, $replace_interests);

		if ($mc_api->errorCode)
		{
			return "[ERROR] Batch Subscribe failed!\n\t" . "Code: " . $mc_api->errorCode . "\n\t" . "Message: " . $mc_api->errorMessage . "\n";
		}
		else
		{
			echo_success_message($subscribe_results);
		}
	}
	else
	{
		if ($_ENV['USER'] != '') // If this is being run from the command line and not from the webpage
		{
			echo "\t0 emails to add, moving on ...\n";
		}
	}

	return 1;
}

?>

