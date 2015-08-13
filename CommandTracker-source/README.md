#CommandTracker v1.1

Plugin for PocketMine-MP that logs all player commands and censors commands for inappropriate language. 
   
   Copyright (C) 2015 Scott Handley
   https://github.com/mcprostar205/pocketmine-plugins/tree/master/CommandTracker-source
   
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

## Documentation 

CommandTracker provides system administrators the capability to moderate player behavior, 
ensuring proper community etiquette standards are followed. Administrators can review the 
chronology of commands to identify inappropriate behavior, taking proactive measures to 
promote a safe and fun gaming environment for players of all ages.

Administrators can also configure which commands are evaluated for inappropriate word usage 
to prevent players from receiving an inappropriate message.

System administrators manage CommandTracker from the console, ensuring system policy 
for password obfuscation and command censoring are enforced.

## Commands

From the console: 

* `/track bw-add <word> : Add a word to be censored (ban word).`
* `/track bw-del <word> : Remove a word from being censored (unban word).`
* `/track cc-add <command> : Add a command to be censored (censor command).`
* `/track cc-del <command> : Remove a command from being censored (uncensor command).`
* `/track sp : Toggles password visibility in console log (show password). Applies until reset or server restarted.`

Note: By default banned words with invoke a substring match; thereby, finding various root, 
prefix, and suffix variants.In some cases, this may not be desired; therefore, a complete 
word match can be qualified by adding the word with a preceding backslash "\". The backslash 
will inform the comparison operation to perform a word compare

## Configuration

You can modify the _CommandTracker/config.yml_ file in the _plugins_ directory once the plugin 
has been run at least one time.

| Configuration | Type | Default | Description |
| :---: | :---: | :---: | :--- |
| log-tofile | boolean | false | Redirects tracking log from the console to a file in _CommandTracker/logs_.
| show-passwords | boolean | false | Obfuscate passwords for SimpleAuth's "register" and "login" commands
| commands-censored | string | say,tell | Comma separated list of commands that qualify for censoring.
| commands-ignored | string | empty | Comma separate list of commands to suppress from tracking.
| words-banned | string | empty | Comma separated list of words deemed inappropriate.

## Permissions

| Permission | Default | Description |
| :---: | :---: | :--- |
| commandtracker.commands.track: | false | Only system administrators can manage command tracking configuration.

## For developers

### Plugin API methods

All methods are available through the main plugin object

* boolean showPasswords() 
* boolean isCommandCensored(String $command)
* boolean isCommandTracked(String $command)
* boolean hasBannedWord(String $message)

## Release Notes

### 1.1

* Added feature to redirect tracking activity to a log file. 

A new _config.yml_ entry called `log-tofile` can be added with values `true` (file) or `false` (system console).

Note: Log files are generated in the _CommandTracker/logs_ directory.

* Added feature to ignor specific commands from tracking activity.

A new _config.yml_ entry called `commands-ignored` can be added with a comma separated list of commands to ignor/suppress.

### 1.0.1

* Fixed empty banned word condition throwing the following exception when Player chats: 

`[Server thread/CRITICAL]: "Could not pass event 'pocketmine\event\player\PlayerCommandPreprocessEvent' to 'CommandTracker v1.0': Uninitialized string offset: 0 on CommandTracker\EventListener`

`[22:14:33] [Server thread/NOTICE]: StringOutOfBoundsException: "Uninitialized string offset: 0" (E_NOTICE) in "/CommandTracker_v1.0.phar/src/CommandTracker/CommandTracker" at line 152`