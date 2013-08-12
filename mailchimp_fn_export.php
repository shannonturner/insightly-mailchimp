<?php

require_once('mcapikey.php');
require_once('MCAPI.class.php');

// Helper functions used by mailchimp_fn_getemailsbygroup.php and mailchimp_fn_addtags.php

function mc_export_list($mc_apikey, $status, $list_id, $mc_group_id=Null, $mc_group_name=Null)
{
	// Uses the MailChimp Export API to get a list of subscribers, unsubscribers, and cleaned for a given list/group
	// Unlike the regular MailChimp API, can get full details for a person.
	// See http://apidocs.mailchimp.com/export/1.0/list.func.php for documentation

	$members_list = array();

	$chunk_size = 4096; // bytes
	$url = "http://us2.api.mailchimp.com/export/1.0/list?apikey=" . $mc_apikey . "&status=" . $status;
	if ($list_id !== Null)
	{
		$url .= "&id=" . $list_id;
	}
	if ($mc_group_id !== Null && $mc_group_name !== Null)
	{
		$url .= "&segment[match]=all&segment[conditions][0][field]=interests-" . $mc_group_id . "&segment[conditions][0][op]=all&segment[conditions][0][value]=" . str_replace(" ", "+", $mc_group_name);
	}

//      echo $url . "\n";

	usleep(340000);
	$handle = @fopen($url, 'r');
	if (!$handle)
	{
		if ($_ENV['USER'] != '') { echo "[ERROR] Couldn't access URL: {$url}\n"; }
	}
	else
	{
		$i = 0;
		$header = array();
		while (!feof($handle))
		{
			$buffer = fgets($handle, $chunk_size);
			if (trim($buffer) != '')
			{
				$obj = json_decode($buffer);
				if ($i == 0)
				{
					$header = $obj;  // Store the header row
				}
				else
				{
					$members_list[$i-1] = array();

					foreach ($header as $column_index => $column)
					{
							$members_list[$i-1][$column] = $obj[$column_index];
					}
				}
				$i++;
			}
		}
		fclose($handle);
	}

	return array($header, $members_list);
}

function process_raw_mc_export($header, $members_list_raw)
{
	// Used to extract the email addresses from mc_export_list

	$members_list = array();
	$members_list_fulldetails = array();

//      print_r($members_list_raw);
//      echo count($members_list_raw) . "\n";

	if (count($members_list_raw) > 0)
	{
		foreach ($members_list_raw as $index => $member)
		{
			$members_list[] = strtolower($member['Email Address']);
			$members_list_fulldetails[strtolower($member['Email Address'])] = array();
			foreach ($header as $column_index => $column)
			{
				$members_list_fulldetails[strtolower($member['Email Address'])][$column] = $member[$column];
			}
		}
		if ($_ENV['USER'] != '') // If this is being run from the command line and not from the webpage
		{
			echo "\t(" . count($members_list) . " records in this list.)\n";
		}
	}
	else
	{
		if ($_ENV['USER'] != '') // If this is being run from the command line and not from the webpage
		{
			echo "\t0 records in this list; skipping. \n";
		}
	}
	return array($members_list, $members_list_fulldetails);
}

function echo_success_message($subscribe_results)
{
	// For use with $mc_api->listBatchSubscribe()

	if ($_ENV['USER'] != '') // If this is being run from the command line and not from the webpage
	{
		echo "Batch Subscribe Successful! \n\t" . "Added: " . $subscribe_results['add_count'] . "\n\t" . "Updated: " . $subscribe_results['update_count'] . "\n\t" . "Errors:  " . $subscribe_results['error_count'] . "\n";

		foreach($subscribe_results['errors'] as $subscribe_result)
		{
			echo $subscribe_result['email_address']. " failed.\n\t" . "Code: " . $subscribe_result['code'] . "\n\t" . "Message: " . $subscribe_result['message'] . "\n";
		}
	}
}

?>