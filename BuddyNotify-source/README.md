#BuddyNotify v1.0

Plugin for PocketMine-MP that sends email notifications to players when server starts/stops and/or when their buddy's join/leave. 
   
   Copyright (C) 2015 Scott Handley
   
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

BuddyNotify provides system and player email notifications to PocketMine-MP servers. 

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
* `/email d|del <buddy>"  : Remove buddy from your alerts`
* `/email ls|list : Show your alerts email and assigned buddies`
* `/email s|send <player>  : Send a player an email`
* `/email r|reg <email> : Register your email to receive alerts`
* `/email ur|unreg : Unregister your email from receiving alerts`
* `/email us|unsub <buddy>"  : Unsubscribe from a buddy's alerts`
* `/email ?|help : Show sub-command help`
* From Console: `/email <player> a|add <buddy> : Add buddy to player's alerts`
* From Console: `/email <player> d|del <buddy> : Remove buddy from player's alerts`
* From Console: `/email <player> ls|list  : Show player's alerts email and assigned buddies`
* From Console: `/email s|send <player>|all <message> : Send a player or everyone an email`
* From Console: `/email <player> r|reg <email> : Register player's email to receive alerts`
* From Console: `/email <player> ur|unreg  : Unregister player's email from receiving alerts`
* From Console: `/email <buddy> us|unsub <player> : Unsubscribe player another player's alerts`
* From Console: `/email ?|help : Show sub-command help`

## Configuration

You can modify the _BuddyNotify/config.yml_ file on the _plugins_ directory once the plugin has been run at least one time.

| Configuration | Type | Default | Description |
| :---: | :---: | :---: | :--- |
| buddynotifier | boolean | true | Master switch turning the notification system on or off. Good for maintenance activities to prevent spamming.
| system-address | string | true | Server administration email address from which server notifications are sent.
| notify-start | boolean | true | Enable server started notifications for registered players.
| notify-stop | boolean | true | Enable server shutdown notifications for registered players.
| notify-auth | boolean | true | Enable player joined notifications for subscribed players.
| notify-quit | boolean | true | Enable player quit notifications for subscribed players.
| date-format | string | "D M j, Y, g:i a" | Configures date/time in localized format.

### Localization

Commands and email messages can be localized by translating the text in the l10n.yml configuration file.

### Email

Your server must have a mail server configured and operational for this plug-in to 
function. You need to update your 'php.ini' file with the correct command. You may 
need to update the line break specific to your operating system. This configuration 
'header-crlf' can be found in the 'l10n.yml' file

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