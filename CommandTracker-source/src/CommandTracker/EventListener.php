<?php

/* 
 * Copyright (C) 2015 Scott Handley
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CommandTracker;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\utils\TextFormat;

class EventListener extends PluginBase implements Listener
{
                
    public function __construct(CommandTracker $plugin)
    {
        $this->plugin = $plugin;
    }
        
    public function onPlayerCommand(PlayerCommandPreprocessEvent $event)
    {
        $bannedword = false;
        $message    = $event->getMessage();
        $playername = $event->getPlayer()->getDisplayName();
                
        // censor global chat if "say" command is also censored
        if( $message[0] != '/' && $this->plugin->isCommandCensored("say") )
        {
            $this->plugin->getLogger()->info("<$playername> /say $message"); 
            $bannedword = $this->plugin->hasBannedWord($message);
        }
        // obfuscate password from tracking during registration
        elseif( (\substr_compare($message,"/register",0,9) === 0) &&
                ($this->plugin->showPasswords() === false) )
        {
            $this->plugin->getLogger()->info("<$playername> /register ****");
        }
        // obfuscate password from tracking during login
        elseif( (\substr_compare($message,"/login",0,6) === 0) &&
                ($this->plugin->showPasswords() === false) )
        {
            $this->plugin->getLogger()->info("<$playername> /login ****");
        }
        // log the command
        else
        {
            $this->plugin->getLogger()->info("<$playername> $message");
                
            // verify command qualifies for censorship
            $words = \explode(" ", $message);
            $cmd  = \array_shift($words);
            if( $this->plugin->isCommandCensored(\substr($cmd,1)) && isset($words) )
            {
                $bannedword = $this->plugin->hasBannedWord($message);
            }
        }

        // if banned word found, cancel command and inform player about inappropriate word usage
        if( $bannedword === true )
        {
            $this->plugin->getLogger()->warning("<$playername> used an inappropriate word. The command has been censored.");
            $event->getPlayer()->sendMessage(TextFormat::RED . "Command cancelled due to inappropriate language. Administrator has been notified.");
            $event->setCancelled(true);
        }
            
    } /* onPlayerCommand */
        
    public function onServerCommand(ServerCommandEvent $event)
    {
        $command = $event->getCommand();   
        $this->plugin->getLogger()->info("<CONSOLE> /$command");
        
    } /* onServerCommand */

    public function onRemoteCommand(RemoteServerCommandEvent $event)
    {
        $command = $event->getCommand();   
        $this->plugin->getLogger()->info("<REMOTE> /$command");
        
    } /* onRemoteCommand */

}

?>