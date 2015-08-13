#BuddyNotify v1.1

Plugin for PocketMine-MP that sends email notifications to players when server starts/stops and/or when their buddy's join/leave. 
   
   Copyright (C) 2015 Scott Handley
   https://github.com/mcprostar205/pocketmine-plugins/tree/master/BuddyNotify-source
   
   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.
  
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
 
   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.

## Requirements

* PocketMine-MP Alpha_1.4 API 1.10.0 or greater
* SimpleAuth plug-in
* PHP mail configuration settings in php.ini

## Documentation 

BuddyNotify adds system and player email notifications to PocketMine-MP servers. 

BuddyNotify is a quick and easy way to send alerts when your server starts and stops or
to automatically notify players when one of their buddies joins or leaves the game! This 
provides a convenient way to communicate to your player community when they are offline.

BuddyNotify also enables players (and administrators) to send email chats directly to 
other registered players during game play! For instance, players can invite offline 
buddies to join in and play together without leaving the game. Or, Administrators can 
notify players about server maintenance plans and/or warn players about following server 
rules! Other examples might be notifying players about a mini-game that is about to start!

Once players register their email address, they will start receiving notifications, such as:

* Server start. Notifies players that game play is available.
* Server stop. Notifies players that game play is suspended.
* Player joined. Notifies offline players when a buddy joins the game. 
* Player leaves. Notifies offline players when a buddy leaves the game.
* Player emails. Sends an email chat to a specified player.

Note: BuddyNotify requires SimpleAuth plugin to reduce spam from unauthorized server 
players while also ensuring a player is planning to play for sometime.

For more information on setting up and configuring BuddyNotify, please see the Documentation.

## Commands

* `/email a|add <buddy> : Add buddy to your alerts`
* `/email d|del <buddy>  : Remove buddy from your alerts`
* `/email ls|list : Show your alerts email and assigned buddies`
* `/email s|send <player>  : Send a player an email`
* `/email r|reg <email> : Register your email to receive alerts`
* `/email ur|unreg : Unregister your email from receiving alerts`
* `/email us|unsub <buddy>  : Unsubscribe from a buddy's alerts`
* `/email ?|help : Show sub-command help`
* From Console: `email <player> a|add <buddy> : Add buddy to player's alerts`
* From Console: `email <player> d|del <buddy> : Remove buddy from player's alerts`
* From Console: `email <player> ls|list  : Show player's alerts email and assigned buddies`
* From Console: `email s|send <player>|all <message> : Send a player or everyone an email`
* From Console: `email <player> r|reg <email> : Register player's email to receive alerts`
* From Console: `email <player> ur|unreg  : Unregister player's email from receiving alerts`
* From Console: `email <buddy> us|unsub <player> : Unsubscribe player another player's alerts`
* From Console: `email ?|help : Show sub-command help`
* From Console: `stop noemail : Shutdown the server without sending an email notification. After the next start/stop, notification will resume.`

## Installation Instructions

* Install and enable an appropriate mail server for your operating system. Verify it is operating correctly on its own by sending a sample email.
* Open the PocketMine PHP configuration (bin/php5/bin/php.ini) and edit (or add) the "sendmail_path" with the path to your email server software.
* Download The BuddyNotify plugin and install it in the PocketMine plugins folder.
* Restart your PocketMine Server to active the plugin for the first time.
* Open _BuddyNotify/config.yml_, edit the "server-address" property with a valid system operator's email address, and save.
* Restart your PocketMine Server to activate the new configuration with valid server-address.
* Register players and send a sample notification.

## Configuration

You can modify the _BuddyNotify/config.yml_ file on the _plugins_ directory once the plugin has been run at least one time.

| Configuration | Type | Default | Description |
| :---: | :---: | :---: | :--- |
| buddynotifier | boolean | true | Master switch turning the notification system on or off. Good for maintenance activities to prevent spamming.
| system-address | string | <empty> | Server administration email address from which server notifications are sent.
| l10n-file | string | l10n.html | Localized string file for in-game and email notifications.
| notify-start | boolean | true | Enable server started notifications for registered players.
| notify-stop | boolean | true | Enable server shutdown notifications for registered players.
| notify-auth | boolean | true | Enable player joined notifications for subscribed players.
| notify-quit | boolean | true | Enable player quit notifications for subscribed players.
| notify-delay | integer | 5000 | Delay in milliseconds before sending a notification.
| date-format | string | "D M j, Y, g:i a" | Configures date/time in localized format.

### Localization

Commands and email messages can be localized by translating the text in the l10n.yml and 
l10n_html.yml configuration files.

### Email

Your server must have a mail server configured and operational for this plug-in to 
function. You need to update your 'php.ini' file with the correct command. You may 
need to update the line break specific to your operating system. This configuration 
'header-crlf' can be found in the 'l10n.yml' or 'l10n_html.yml' file.

By default, BuddyNotify delivers email notifications in plain text. However, BuddyNotify
can send HTML formatted notifications providing a more interactive experience. To enable 
HTML notifications, change the 'l10n-file' to 'l10n_html.yml' in the 'config.yml'.

## Frequently Asked Questions

BuddyNotify requires the server hosting PocketMine-MP have an email server running to 
relay messages to recipients (such as sendmail on unix). Once you have verified your 
mail relay is working on its own, be sure to update the PocketMine-MP's php.ini for 
the mail command.

On Unix, _php.ini_ can be found in the PocketMine-MP location under _bin/php5/bin_. 
A common mail configuration would be:

`[mail function]`
`sendmail_path="/usr/sbin/sendmail -t"​`

Below are some common questions and answers:
`[BuddyNotify] System alerts disabled. System operator email address not set in 'config.xml'.`
You have not updated the "system-address" property in _plugins/BuddyNotify/config.xml_ 
with a valid system administrators email address.​

`[BuddyNotify] Unable to send. Check the master switch in the 'config.cfg'`
You have set the "buddynotifier" property to "false" in _plugins/BuddyNotify/config.xml"_

`[BuddyNotify] Unable to send. There are no registered email addresses.`
No player has registered their email address. Either inform players to register their email 
addresses or register player email addresses from the console.
​
`[BuddyNotify] Unable to send. Check the 'sendmail_path' property in 'php.ini' is correct.`
There is a problem sending mail. Possible causes:
- You haven't installed, configured, or activated your email server relay
- You haven't configured the PocketMine PHP.ini with the path to your email server executable​

## Permissions

| Permission | Default | Description |
| :---: | :---: | :--- |
| buddynotify.commands.email: | true | Allows users to notify players of their activity.

## For developers

### Plugin API methods

All methods are available through the main plugin object

* boolean isFeatureEnabled(String $feature)
* boolean isPlayerRegistered(String $playername)
* String [] getBuddyList(String $playername)
* String sendEmail(String $from, String $to, $subject, $body, $bcc=null)

## Release Notes

### 1.1

* Improved player join/quit email notifications to reduce notification sent when a player drops from the game briefly and re-joins within a specified time interval.

A configuration entry `notify-delay` can be added to increase or decrease the time interval before issuing a quit notification. Default time is 5 minutes.

* Added configuration entry `l10n-file` to specify which localization file to use (e.g l10n_fr-fr.yml)

* Added capability to send email notification in HTML format for richer formatting and interactive experience using hypertext.

A default `l10n_html.yml` file has been included to demonstrate. It can be assigned to `l10n-file` config entry.

* Added capability to suppress email notifications temporarily on a server "restart" (stop followed by start).

Console command to suppress notification => `stop noemail`

* No longer sends email notifications when the "reload" command is issued.
