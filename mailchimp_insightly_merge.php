<!DOCTYPE html>
<html>
<head>
<title>Insightly/MailChimp Tag and Group Syncer</title>

<?php

// Hey, you there! Change this to wherever the Insightly and MailChimp programs live
$fullpath = "";
$include_folder = "inc/"

// If inc/ isn't your include folder, be sure to change these require statements
require_once($fullpath.$include_folder.'mailchimp_fn_export.php');
require_once($fullpath.$include_folder.'mailchimp_fn_addgroups.php');
require_once($fullpath.$include_folder.'mailchimp_fn_getemailsbygroup.php');
require_once($fullpath.$include_folder.'mcapikey.php');
require_once($fullpath.$include_folder.'MCAPI.class.php');

?>

<style type="text/css">
.container {
	width: 1200px;
	margin: 50px auto; /* the auto value on the sides, coupled with the width, centers the layout */
	position:relative;
}
</style>

<script type="text/javascript">

// Validation to prevent empty form entry
function validateGrabTagAndGroupForm()
{
	var ins = document.forms["grabTagAndGroupForm"]["iTagField"].value;
	var mcG = document.forms["grabTagAndGroupForm"]["mcGroupField"].value;
	var mcLID = document.forms["grabTagAndGroupForm"]["mcListIDField"].value;
	var mcPGID = document.forms["grabTagAndGroupForm"]["mcParentGroupField"].value;

	if ((mcPGID == null || mcPGID.replace(/ /g,'') == "") || (ins == null || ins.replace(/ /g,'') == "") || (mcG == null || mcG.replace(/ /g,'') == "") || (mcLID == null || mcLID.replace(/ /g,'') == ""))
	{
		alert("You have to enter values in each of the fields to submit this form.");
		return false;
	}
	else
	{
		return true;
	}
}

// Makes sure at least one email is selected
function validateEmailsToMergeForm(count)
{
	var check = false;

	for (i = 0; i<count; i++)
	{
		if (document.getElementById(i).checked == true)
		{
			check = true;
		}
	}

	if (check == true)
	{
		document.forms['emailsToMerge'].submit();
	}
	else
	{
		alert("You haven't selected any emails to add groups or tags to.  If you don't want to merge any of these entries, then you are done, and can cancel.");
	}
}

function SetAllCheckBoxes(FormName, FieldName, CheckValue)
{
	if(!document.forms[FormName])
		return;
	var objCheckBoxes = document.forms[FormName].elements[FieldName];
	if(!objCheckBoxes)
		return;
	var countCheckBoxes = objCheckBoxes.length;
	if(!countCheckBoxes)
		objCheckBoxes.checked = CheckValue;
	else
		// set the check value for all check boxes
		for(var i = 0; i < countCheckBoxes; i++)
			objCheckBoxes[i].checked = CheckValue;
}

</script>

<?php

if (isset($_POST["step"]))
{
	$step = $_POST["step"];
}
else
{
	$step = 0;
}

if ($_POST["iTagField"])
{
	$insightly_tag = $_POST["iTagField"];
}

if (isset($_POST["mcParentGroupField"]))
{
	$mc_grouping_name = $_POST["mcParentGroupField"];
}

if (isset($_POST["mcGroupField"]))
{
	$mc_group_name = $_POST["mcGroupField"];
}

if (isset($_POST["mcListIDField"]))
{
	$mc_list_id = $_POST["mcListIDField"];
}
if (isset($_POST['mcGroupIDField']))
{
	$mc_group_id = $_POST['mcGroupIDField'];
}

if (isset($_POST["insightly_emails_selected"]))
{
	$insightly_emails_selected = $_POST['insightly_emails_selected'];
}

if (isset($_POST["mailchimp_emails_selected"]))
{
	$mailchimp_emails_selected = $_POST['mailchimp_emails_selected'];
}

// Takes in indexed array of output from insightly_getworkemailsbytag.py and mailchimp_getworkemailsbytag()
// And then returns an associative array keyed on the email, containing first and last names, the email, and the tag/group
function return_emails_as_array($external_emails_list)
{
	$return_emails = array();
	foreach ($external_emails_list as $external_email_line)
	{
		if (strpos($external_email_line, "[ERROR]") !== False)
		{
			// If an [ERROR] was found in this output line

			echo "<table style='text-align: center; vertical-align: middle; background-color: #ff80a0;'><tr><td>One of the helper scripts encountered an error.</td></tr>";
			echo "<tr><td> The error text is: {$external_email_line} </td></tr></table>";
		}
		else // Normal operation
		{
			list($first_name, $last_name, $work_email, $tag) = explode("\t", $external_email_line);
			$return_emails[$work_email] = array(
				'first_name' => $first_name,
				'last_name' => $last_name,
				'work_email' => $work_email,
				'tag' => $tag
			);
		}
	}
	return $return_emails;
}

?>

</head>
<body style="font-family:Century Gothic;">

<div class="container">
<h1 style="text-align: center;">Insightly Tag/MailChimp Group Merge</h1>
<?php

// At the start, accepts Insightly tag and MailChimp group information
if ($step == 0)
{
	echo "<BR><p><form name='grabTagAndGroupForm' id='grabTagAndGroupForm' action='mailchimp_insightly_merge.php' onSubmit='return validateGrabTagAndGroupForm()' method='post'>";
	echo "<table cellpadding=5><tr><td>Insightly Tag:<BR><BR></td><td><input type='text' name='iTagField' id='iTagField'><BR><BR></td></tr>";
	echo "<tr><td>Mailchimp Parent Grouping Name:<BR><BR></td><td><input type='text' name='mcParentGroupField' id='mcParentGroupField'><BR><BR></td></tr>";
	echo "<tr><td>Mailchimp Group Name:<BR><BR></td><td><input type='text' name='mcGroupField' id='mcGroupField'><BR><BR></td></tr>";
	echo "<tr><td>Mailchimp List ID:<BR><BR></td><td><input type='text' name='mcListIDField' id='mcListIDField'><BR><BR></td></tr></table>";
	echo "<input type='hidden' name='step' id='step' value=1>";
	echo "<input type='submit' name='submitTagAndMerge' id='submitTagAndMerge'></p>";
}

// Grab lists based on tags, groups and ids, matches the lists against each other, wipes the insightly list of unsubscribe and cleaned entries, returns and displays lists for selection
elseif ($step == 1)
{
	echo "<img id='loadingGif' width='150' src='images/loadingGif.gif' style='float:right;'>";
	// Calls python script to get insightly work emails based on tag
	$insightly_program_path = "python {$fullpath}insightly_getworkemailsbytag.py \"{$insightly_tag}\"";
	$insightly_emails_list = shell_exec(escapeshellcmd($insightly_program_path));

	if (strpos($insightly_emails_list, "Searching for emails") !== false)
	{
		$no_insightly_tag = true;
	}
	else
	{
		$no_insightly_tag = false;
	}

	// Checks MailChimp's listInterestGroupings() to get the $mc_group_id
	// NOTE: If more than one child group has the same name, you may not get the desired results since only the first match found will be saved.
	$mc_group_id = false;
	usleep(340000);
	$returned_parent_groups = $mc_api->listInterestGroupings($mc_list_id);

	if (count($returned_parent_groups) > 0 && !empty($returned_parent_groups) && $returned_parent_groups != null)
	{
		foreach ($returned_parent_groups as $returned_parent_group)
		{
			foreach ($returned_parent_group['groups'] as $returned_child_group)
			{
				if ($returned_child_group['name'] == $mc_group_name && $returned_parent_group['name'] == $mc_grouping_name)
				{
					$mc_group_id = $returned_parent_group['id'];
					break 2;
				}
			}
		}
	}
	else
	{
		die("The values you entered to identify your MailChimp group did not validate. <a href='mailchimp_insightly_merge.php'><BR><BR>Start Over?</a>");
	}

	if ($mc_group_id == false)
	{
		die("The values you entered to identify your MailChimp group did not validate. <a href='mailchimp_insightly_merge.php'><BR><BR>Start Over?</a>");
	}

	// Calls MailChimp function to get MailChimp emails by group
	$return_value = mailchimp_getworkemailsbytag($mc_api, $mc_apikey, $mc_list_id, $mc_group_id, $mc_group_name);

	$mailchimp_emails_list = array();

	if (is_array($return_value)) // Success
	{
		if (count($return_value) > 0)
		{
			foreach ($return_value as $one_email)
			{
				$mailchimp_emails_list[] = $one_email;
			}
		}
		$mailchimp_group_exists = true;
	}
	else // Failure
	{
		$mailchimp_group_exists = false;
		die("mailchimp_getworkemailsbytag() failed: ".$return_value."<BR><BR><a href='mailchimp_insightly_merge.php'>Start Over?</a>");
	}

	// MailChimp email lists have been returned!

	// Output from python script is a string, return_emails_as_array accepts an indexed array, line below converts appropriately
	$insightly_emails_list = explode("\n",trim($insightly_emails_list));

	// Returns associative arrays of email lists
	$insightly_emails = return_emails_as_array($insightly_emails_list);
	$mailchimp_emails = return_emails_as_array($mailchimp_emails_list);

	// Generates lists of emails that are NOT in the opposite list
	$in_insightly_notin_mailchimp_unscrubbed = array_diff(array_keys($insightly_emails), array_keys($mailchimp_emails));
	$in_mailchimp_notin_insightly = array_diff(array_keys($mailchimp_emails), array_keys($insightly_emails));

	// If someone is in Insightly but is not in MailChimp, they might be in the list of MailChimp's cleaned or unsubscribed emails for this list.
	//      If that's true, then I do NOT want to add them into MailChimp.

	// Getting list of people unsubscribed from MailChimp for the list $mc_list_id
	$status = 'unsubscribed';
	list($header, $mc_toolkit_unsubscribed_raw) = mc_export_list($mc_apikey, $status, $mc_list_id);
	list($mc_toolkit_unsubscribed, $mc_toolkit_unsubscribed_fulldetails) = process_raw_mc_export($header, $mc_toolkit_unsubscribed_raw);

	// Getting list of people cleaned from MailChimp for the list $mc_list_id
	$status = 'cleaned';
	list($header, $mc_toolkit_cleaned_raw) = mc_export_list($mc_apikey, $status, $mc_list_id);
	list($mc_toolkit_cleaned, $mc_toolkit_cleaned_fulldetails) = process_raw_mc_export($header, $mc_toolkit_cleaned_raw);

	// Now the list of people in Insightly and not in MailChimp is properly scrubbed of any potential unsubscribes or cleans.
	$in_insightly_notin_mailchimp = array_diff($in_insightly_notin_mailchimp_unscrubbed, $mc_toolkit_unsubscribed, $mc_toolkit_cleaned);

	// Generates list of emails that were filtered out because they are in MailChimp as cleaned or unsubscribed
	$in_insightly_in_mailchimp_cleanunsub = array_diff($in_insightly_notin_mailchimp_unscrubbed, $in_insightly_notin_mailchimp);

	$count_already_synced = count($mailchimp_emails) - count($in_mailchimp_notin_insightly);

	// We now have arrays of $in_insightly_notin_mailchimp and $in_mailchimp_notin_insightly

	// Generates UI Form to select emails from either list to be added with the opposite tags

	$count_returned_emails = 0;

	echo "<h2 align='center'>".$count_already_synced." emails are currently synced between Insightly Tag ".$insightly_tag." and MailChimp Group ".$mc_group_name."</h2>";

	// Checks to make sure that a valid MailChimp group was returned. Without a valid MailChimp group, you can't sync between Insightly and MailChimp.
	if ($mailchimp_group_exists == true)
	{
		echo "<p><form name='emailsToMerge' id='emailsToMerge' method='post' action='mailchimp_insightly_merge.php'>";
		echo "<table cellpadding=10><td align='center' valign='top'><span style='font-size:120%;'><strong>".count($in_insightly_notin_mailchimp)." Emails with Insightly Tag, not in Mailchimp Group</strong></span><BR><BR>";

		if ($no_insightly_tag == true)
		{
			echo $insightly_emails_list[0];
		}
		else
		{
			if (count($in_insightly_notin_mailchimp) > 0)
			{
				echo "<input type=\"button\" onclick=\"SetAllCheckBoxes('emailsToMerge', 'insightly_emails_selected[]', true);\" value=\"Select All\"> ";
				echo "<input type=\"button\" onclick=\"SetAllCheckBoxes('emailsToMerge', 'insightly_emails_selected[]', false);\" value=\"Unselect All\"><BR><BR>";
				echo "<table style='float:left;' cellpadding=5><tr><td><strong>Select to Merge</strong><td><strong>Insightly Emails</strong></td><td><strong>First Name</strong></td><td><strong>Last Name</strong></td></tr>\n";

				// Loops through list of Insightly emails that aren't in selected MailChimp group, generates checkboxes inputs with labels
				// $insightly_emails is the full associative array of email addresses that have the Insightly tag, $in_insightly_notin_mailchimp can be used as key to access the appropriate data
				foreach ($in_insightly_notin_mailchimp as $one_email)
				{
					echo "<tr><td align='center'><input type='checkbox' name='insightly_emails_selected[]' id='".$count_returned_emails."' value='".$insightly_emails[$one_email]['work_email']."'></td><td>".$insightly_emails[$one_email]['work_email']."</td><td>".$insightly_emails[$one_email]['first_name']."</td><td>".$insightly_emails[$one_email]['last_name']."</td></tr>\n";
					$count_returned_emails++;
				}

				echo "</table></p>";
			}
			else
			{
				echo "All emails in Insightly with the tag <strong>".$insightly_tag."</strong><BR>are in the MailChimp group <strong>".$mc_group_name."</strong><BR>";
			}
		}
		echo "</td><td align='center' valign='top'><span style='font-size:120%;'><strong>".count($in_mailchimp_notin_insightly)." Emails in Mailchimp Group, without Insightly Tag</strong></span><BR><BR>\n";
		if (count($in_mailchimp_notin_insightly) > 0)
		{
			echo "<input type=\"button\" onclick=\"SetAllCheckBoxes('emailsToMerge', 'mailchimp_emails_selected[]', true);\" value=\"Select All\"> ";
			echo "<input type=\"button\" onclick=\"SetAllCheckBoxes('emailsToMerge', 'mailchimp_emails_selected[]', false);\" value=\"Unselect All\"><BR><BR>";
			echo "<table cellpadding=5><tr><td><strong>Select to Merge</strong><td><strong>Mailchimp Emails</strong></td><td><strong>First Name</strong></td><td><strong>Last Name</strong></td></tr>\n";

			// Loops through list of MailChimp emails that don't have the selected tag in Insightly, generates checkboxes inputs with labels
			// $mailchimp_emails is the full associative array of email addresses that are in the MailChimp group, $in_mailchimp_notin_insightly can be used as key to access the appropriate data
			foreach ($in_mailchimp_notin_insightly as $one_email)
			{
				echo "<tr><td align='center'><input type='checkbox' name='mailchimp_emails_selected[]' id='".$count_returned_emails."' value='".$mailchimp_emails[$one_email]['work_email']."'></td><td>".$mailchimp_emails[$one_email]['work_email']."</td><td>".$mailchimp_emails[$one_email]['first_name']."</td><td>".$mailchimp_emails[$one_email]['last_name']."</td></tr>\n";
				$count_returned_emails++;
			}

			echo "</table>";
		}
		else
		{
			echo "All emails with the MailChimp group <strong>".$mc_group_name."</strong><BR>are in Insightly with the tag <strong>".$insightly_tag."</strong><BR>";
		}
	    ?> <script type="text/javascript"> /* Returns count of checkbox inputs for form validation */  var count_emails_for_validation = <?php echo $count_returned_emails; ?>; </script> <?php
		echo "</td></tr><tr><td colspan=2 align='center'><br><input name='emailSubmit' id='emailSubmit' type='button' onClick='validateEmailsToMergeForm(count_emails_for_validation);' value='Apply Tags and Groups!'><BR><BR>or, <a href='http://alternate.atlasproject.net/editor/mailchimp_insightly_merge.php'>cancel and start over</a></td></table>";
		echo "<input type='hidden' name='step' id='step' value=2>";
		echo "<input type='hidden' name='iTagField' id='iTagField2' value='".$insightly_tag."'>";
		echo "<input type='hidden' name='mcGroupField' id='mcGroupField2' value='".$mc_group_name."'>";
		echo "<input type='hidden' name='mcParentGroupField' id='mcParentGroupField2' value='".$mc_grouping_name."'>";
		echo "<input type='hidden' name='mcListIDField' id='mcListIDField2' value='".$mc_list_id."'>";
		echo "<input type='hidden' name='mcGroupIDField' id='mcGroupIDField2' value='".$mc_group_id."'>";
		echo "<input type='hidden' name='step' value=2>";
		echo "</form></p>";
		?> <script type="text/javascript"> /* Disables submit button if there are no checkboxes generated */ if (count_emails_for_validation == 0) document.getElementById("emailSubmit").disabled = true; </script>
		
		echo "<p><i>The sync process will update existing entries, or create new entries if the email does not exist.<br></i></p>";
		
		<?php

		// Prints a list of emails that were in insightly and were in mailchimp as cleaned or unsubscribed (if there are any)
		if (count($in_insightly_in_mailchimp_cleanunsub) > 0)
		{
			echo "<p><strong>List of ".count($in_insightly_in_mailchimp_cleanunsub)." emails that are in Insightly with the tag ".$insightly_tag." and are \"cleaned\" or \"unsubscribed\" from the MailChimp list with id ".$mc_list_id.":<BR><table><tr>";

			$count_clean_unsub = 0;
			foreach ($in_insightly_in_mailchimp_cleanunsub as $one_email)
			{
				echo "<tr><td>".$one_email."</td></tr>";
			}
			echo "</table></p>";
		}
		else
		{
			echo "<p>There were no emails in Insightly that were in MailChimp as \"cleaned\" or \"unsubscribed\".</p>";
		}
	}
	echo "<script type='text/javascript'>document.getElementById('loadingGif').style.display = \"none\";</script>";
}

// All required data have been submitted, final step applies the appropriate groups and tags
elseif ($step == 2)
{
	echo "<img id='loadingGif' width='150' src='images/loadingGif.gif'' style='float:right;'>";
	// Calls the python script to add Insightly tags to MailChimp emails
	// Passes in emails in JSON array format
	if (!empty($mailchimp_emails_selected))
	{
		$python_pass_method = "array";
		$json_encoded_mailchimp_emails_selected = json_encode($mailchimp_emails_selected);

		$insightly_program_path = "python {$fullpath}insightly_addtags.py \"{$json_encoded_mailchimp_emails_selected}\" \"{$insightly_tag}\" $python_pass_method";
		$returned_from_python = shell_exec(escapeshellcmd($insightly_program_path));

		$return_text = explode("\n",$returned_from_python);

		foreach($return_text as $returned_line)
		{
			echo $returned_line."<BR>";
		}
		echo "Insightly Tag Additions Script finished.<BR><BR>";
	}
	else
	{
		echo "No emails from MailChimp selected to be given Insightly tags<BR><BR>";
	}

	// Calls function to add Insightly emails to MailChimp group
	$emails_to_import = $insightly_emails_selected;

	if ($mc_list_id == '' || $mc_group_id == '' || $mc_grouping_name == '' || $mc_group_name == '')
	{
		die("Somehow you made it this far with a blank MailChimp list ID, MailChimp group ID, MailChimp parent-group name, or a MailChimp group name... Cannot run properly!\n");
	}
	elseif($emails_to_import == '')
	{
		echo "No emails from Insightly selected to be added to MailChimp group<BR><BR>";
	}
	else
	{
		$return_value = mailchimp_addgroups($mc_api, $mc_apikey, $emails_to_import, $mc_list_id, $mc_group_id, $mc_grouping_name, $mc_group_name);

		if ($return_value === 1) // Success
		{
			foreach($emails_to_import as $one_email)
			{
				echo $one_email." added to group ".$mc_group_name."<BR>";
			}
			echo "MailChimp Group Additions Script Completed Successfully!<br><br>\n";
		}
		else // Some Error message
		{
			echo $return_value;
		}
	}
	echo "<BR><BR><BR><a href='mailchimp_insightly_merge.php'>Start Over?</a>";
	echo "<script type='text/javascript'>document.getElementById('loadingGif').style.display = \"none\";</script>";
}

?>
</div>
</body>
</html>