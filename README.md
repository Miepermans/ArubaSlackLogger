# ArubaSlackLogger

Logging Aruba Syslog to MySQL and Slack

This project was initially meant for me to use in the Hackerspace. However, parsing Aruba logs gave me all other idea's about user mapping and so on.

Please note this is still a work in progress.

## Prerequisites

* php-cli
* mysql
* a syslog receiver
* a Slack incoming webhook URL

## Configuring the ArubaSlackLogger:

1. Install the database from the database.sql dump and add a user for this database.

2. Configure your syslog receiver for forwarding to this script.

3. Restart your syslog receiver.

4. Tweak the settings.inc.php file for slack settings, Tweak the mysql_class/settings.ini.php file for database config.

5. ???

6. Profit!

I will add a small demo config file for Syslog-NG which i prefer to use because of the flexibility.

## Copyright and license
#### Code released under Beerware
#### mysql_class code licensed by Indieteq
