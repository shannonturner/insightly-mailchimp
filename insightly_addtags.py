import json
import requests
from requests.auth import HTTPBasicAuth
import sys
import time

# insightly_addtags.py
# Adds specified tags to the specified emails

try:
        filename = sys.argv[1]
        tag = sys.argv[2]
except IndexError:
        print "Parameters for insightly_addtags.py: filename (or list of email addresses), tag, optional: pass_method (default: filename, 'array' to use an array instead of a file)\nBe sure to wrap your parameters in quotes if they contain spaces.\n"
        sys.exit(1)

try:
        pass_method = sys.argv[3]
except IndexError:
        pass_method = None # This is optional; if "array" is specified, attempt to get emails from a list since PHP will pass these through as a string representation of a JSON object

# Authentication
# Per Insightly's API rules, you must be a paying customer to have access.
# Leave the password blank; your username will be base64-encoded and passed in for authentication

username = ''
password = ''

base_url = "https://api.insight.ly/v2/"

if pass_method == 'array': # Get email addresses passed through from PHP
        emails = filename.replace("\\","").strip('[]').replace('"', '').split(",")
else: # Loading email addresses from provided file
        try:
            with open(filename) as emails_file:
                emails = emails_file.read().strip()
                emails = emails.split("\n")
        except IOError, e:
            print "[ERROR] Failed to open file {0}\n".format(filename)
            sys.exit(1)

print "Adding the tag {0} to {1} email addresses".format(tag, len(emails))

# Get Contact ID from email address, use that to look up contact and update tag

headers = {'content-type': 'application/json'}

for email in emails:

    if email.strip() == "": # Ignore blank lines
        continue

    parameters = "Contacts?email={0}".format(email)

    try:
        # Get Contact ID (and other details) from email address
        time.sleep(0.3)
        request = requests.get("{0}{1}".format(base_url, parameters), auth=(username, password))
        response_details = request.json()
    except IOError, e:
        print "[ERROR] Fetch failed ({0}): {1}{2}".format(e, base_url, parameters)

    # NOTE: In some cases, multiple people may have the same email address.  All of these people will receive the tags.

    print "{0} matched {1} records.".format(email, len(response_details))

    if len(response_details) == 0:
        print "\t{0} does not exist as a contact in Insight.ly; adding them.<br>\n".format(email)

        new_contact = {  
            "EMAILLINKS": [], "FIRST_NAME": None, "LAST_NAME": None, "DATES": [], "CONTACTLINKS": [], "BACKGROUND": None, "ADDRESSES": [], "VISIBLE_TO": "EVERYONE", "DEFAULT_LINKED_ORGANISATION": None, "CONTACT_ID": 0, "IMAGE_URL": None, "VISIBLE_USER_IDS": None, "VISIBLE_TEAM_ID": None, "CONTACT_FIELD_10": None, "SALUTATION": None, "CONTACT_FIELD_9": None, "CONTACT_FIELD_8": None, "LINKS": [], "CONTACT_FIELD_3": None, "CONTACT_FIELD_2": None, "CONTACT_FIELD_1": None, "CONTACT_FIELD_7": None, "CONTACT_FIELD_6": None, "CONTACT_FIELD_5": None, "CONTACT_FIELD_4": None, 

            "CONTACTINFOS": [{"SUBTYPE": None, "CONTACT_INFO_ID": None, "TYPE": "Email", 
            "DETAIL": email, 
            "LABEL": "Work"}],
            "TAGS": [{'TAG_NAME': tag}],
            "DATE_CREATED_UTC": '{0}-{1:0>2d}-{2:0>2d} {3:0>2d}:{4:0>2d}:{5:0>2d}'.format(*time.localtime()[0:6]),
            "DATE_UPDATED_UTC": '{0}-{1:0>2d}-{2:0>2d} {3:0>2d}:{4:0>2d}:{5:0>2d}'.format(*time.localtime()[0:6])
        }

        new_contact = json.JSONEncoder().encode(new_contact)

        time.sleep(0.3)
        post_contact = requests.post("{0}Contacts".format(base_url), auth=(username, password), headers=headers, data=new_contact)

        if post_contact.status_code == 201:
            print "\tSuccessfully added {0}".format(email)
        else:
            print "\t[ERROR] Failed to add {0} as a new contact! Status code: {1}".format(email, post_contact.status_code)

    for each_contact in response_details:

        contact_id = each_contact['CONTACT_ID']
        existing_tags_raw = each_contact.get('TAGS')

        existing_tags = []

        if existing_tags_raw is not None:
            for existing_tag_raw in existing_tags_raw:
                existing_tags.append(existing_tag_raw['TAG_NAME'])

        if tag in existing_tags:
            print "{0} already has the tag {1}!".format(contact_id, tag)
        else:
            print "Updating {0} with the tag {1}".format(contact_id, tag)

            modified_contact = each_contact
            modified_contact['TAGS'].append({'TAG_NAME': tag})
            modified_contact = json.JSONEncoder().encode(modified_contact)

            time.sleep(0.3)
            put_contact = requests.put("{0}Contacts/{1}".format(base_url, contact_id), auth=(username, password), headers=headers, data=modified_contact)

            if put_contact.status_code != 200:
                print "\t[ERROR] Update failed! Status code: {0}".format(put_contact.status_code)
