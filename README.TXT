Insightly - MailChimp Sync

A program to sync tags in your Insight.ly list with groups in your MailChimp lists with greater flexibility than allowed by the builtin integration.

Note: Insight.ly only makes their API available to paying customers.  If you are not a paying Insight.ly customer, this script will not work for you.

Setting up:

0) You'll need Python and PHP installed on the server where you're planning to run /mailchimp_insightly_merge.php; this was written for Python 2.7 and PHP 5.3 but should work for other versions as well.
1) Put mailchimp_fn_addgroups.php, mailchimp_fn_export.php, mailchimp_fn_getemailsbygroup.php, and mcapikey.php into your favorite include folder
2) Put mailchimp_getlistIDs.php, insightly_getworkemailsbytag.py, and insightly_addtags.py into your scripts folder (suggested: the folder that contains the include folder)
3) In mailchimp_insightly_merge.php at the very top, change the $fullpath variable to point to your scripts folder.  Then change the $include_folder variable to point to the include folder.  The require_once statements in mailchimp_insightly_merge.php assume that your scripts folder contains your include folder. If that is not true of your configuration, you will need to change these require statements.
4) Don't forget to add your API key to mcapikey.php and Insight.ly username to insightly_getworkemailsbytag.py and insightly_addtags.py
5) If you haven't already installed the Python requests library, you can do that here: http://docs.python-requests.org/en/latest/
6) Make sure the MailChimp PHP Wrapper (MCAPI.class.php) is in your include folder.  You can find the MailChimp PHP wrapper here: http://apidocs.mailchimp.com/api/downloads/#php
7) Visit mailchimp_insightly_merge.php in your web browser to get started!

How to Sync your Insight.ly tags and MailChimp groups:

Step 1: On mailchimp_insightly_merge.php, enter the Insight.ly tag and its corresponding Parent Grouping Name, Group Name, and MailChimp list ID.

Note: The MailChimp list ID is NOT the same as the web ID (the ID shown in the URL when logged in) for that list.  To get the MailChimp list ID, you can browse to the specific list in your MailChimp account, then go to Settings > List Name and Defaults, or you can use mailchimp_getlistIDs.php

Step 2: The script will show you two lists of people: 

1) Insight.ly *work-email* contacts who have the tag you specified that DO NOT exist in your MailChimp list within the corresponding group
2) MailChimp subscribers for your MailChimp list who are members of the specified group but DO NOT have the corresponding Insight.ly tag

Step 3: Check the checkboxes for the people you'd like to sync when you click "Apply Tags and Groups!"; they will receive the corresponding tags or groups in Insight.ly and/or MailChimp.

You can sync some, all, or none of the contacts - or just see whether your lists are synced up.  If anyone is unsubscribed or cleaned from the MailChimp list you specified, they'll appear in a list below the syncing tables so you know who was unsubscribed or cleaned.

Note: Syncing emails that exist in both systems will update them with the new Insight.ly tag or MailChimp group.
Note: If an email exists in MailChimp but does not exist in Insight.ly, syncing will add them to Insight.ly.
Note: If an email exists in Insight.ly but does not exist in MailChimp, syncing will add it to MailChimp as long as that email has not been previously unsubscribed or cleaned from your list.

Note: To prevent your account from being throttled, a sleep timer of roughly 0.3 seconds occurs before every API call to MailChimp or Insight.ly