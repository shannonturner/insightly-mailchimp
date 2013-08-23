import base64
import requests
from requests.auth import HTTPBasicAuth
import sys
import time

# insightly_getworkemailsbytag.py
# Gets all Insight.ly contacts matching the specified tags who have work emails

try:
        tags = sys.argv[1:] # Be sure to use quotes if your tag contains spaces
except IndexError:
        print "insightly_getworkemailsbytag.py uses the following parameters: tag\nBe sure to wrap your tag in quotes.  Multiple tags may be specified.\n"
        sys.exit(1)

if len(tags) < 1:
        print "insightly_getworkemailsbytag.py uses the following parameters: tag\nBe sure to wrap your tag in quotes.  Multiple tags may be specified.\n"
        sys.exit(1)

# Authentication
# Per Insightly's API rules, you must be a paying customer to have access.
# Leave the password blank; your username will be base64-encoded and passed in for authentication

username = ''
password = ''
apikey = base64.b64encode("{0}:{1}".format(username, password))

base_url = "https://api.insight.ly/v2/"

for tag in tags:

    parameters = "Contacts?tag={0}".format(tag.replace(" ", "+"))

    try:
        # Get all contacts matching the tags input

        time.sleep(0.3)
        request = requests.get("{0}{1}".format(base_url, parameters), auth=(username, password))
        response_details = request.json()

    except requests.ConnectionError, e:
        print "[ERROR] Fetch failed ({0}): {1}{2}\n".format(e, base_url, parameters)
        sys.exit(1)

        # The Insight.ly API does not offer a way to view all current valid tags.
    if len(response_details) == 0:
        print "Searching for emails with the tag {0} returned 0 results.<br> Either this is not the tag you want (is it a typo?), or this tag does not yet exist.<br><br> If you continue, you will be tagging your contacts with the new tag <b>{0}</b>.".format(tag)

    for each_contact in response_details:
        try:
            first_name = each_contact.get('FIRST_NAME')
            last_name = each_contact.get('LAST_NAME')

            if each_contact.get('CONTACTINFOS') is not None:
                for way_to_contact in each_contact['CONTACTINFOS']:
                    if way_to_contact.get('LABEL') == 'Work' and way_to_contact.get('TYPE') == 'EMAIL':
                        work_email = way_to_contact.get('DETAIL')
            else:
                work_email = None

            print "{0}\t{1}\t{2}\t{3}".format(first_name, last_name, work_email.lower(), tag)
        except UnicodeError, e:
            continue

