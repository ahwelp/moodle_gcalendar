# moodle_gcalendar

This project has the intention to integrate Moodle with Google Calendar.

How to install:

1 - Copy the googlecalendar folder to local/ and configure file permission

2 - Allow Moodle to update the Google Calendar
  - Log in your defined system account
  - Acess https://console.developers.google.com - Credentials
  - Create a project
  - In the project created, enable the Calendar API
  - In the project created, create an OAuth Key
  - Download Key and place in the certs/ folder
  - In the command line run the boot.php file
  - Paste the link on the browser
  - The verification code might be in the browser URL
  - Make sure the quickstart file has been created
  
3 - (Optional) Register your site in Google (Required for two way sync)
  - Acess https://console.developers.google.com - Credentials - Domain Verification
  - Add an domain
  
4 - Run Moodle's admin verification and accept to install the plugin

#############################################################################################

This plugin use the calendar Hook(https://docs.moodle.org/dev/Calendar_API#Event_hook). So this will create an event whenever an event is created in Moodle. The events in moodle are stored in the table {event}.

Example of things that create event:
- Assignment Due Date
- Quiz Open
- Quiz Close
- Quiz Duration (When open and close are defined)
