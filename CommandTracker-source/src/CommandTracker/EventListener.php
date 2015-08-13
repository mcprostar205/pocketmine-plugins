<?php

/* 
 * Copyright (C) 2015 Scott Handley
 * https://github.com/mcprostar205/pocketmine-plugins/tree/master/CommandTracker-source
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

use LogLevel;
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
        $playername = $event->getPlayer()->getDisplayName();
        $message    = $event->getMessage();
        $words      = \explode(" ", $message);

        // global chat is the "say" command
        if( $message[0] !== '/' )
        {
            $cmd = "say";
        }
        else
        {
            $cmd = \strtolower(\substr(\array_shift($words),1));
        }
                
        // obfuscate password from tracking on SimpleAuth register or login commands
        if( ((\strcmp($cmd,"register") === 0) || (\strcmp($cmd,"login") === 0)) &&
                ($this->plugin->showPasswords() === false) )
        {
            $this->plugin->logMessage(LogLevel::INFO, "<$playername> /$cmd ****");
        }
        else
        {
            if( $this->plugin->isCommandTracked($cmd) )
            {
                $this->plugin->logMessage(LogLevel::INFO, "<$playername> /$cmd " . \implode(" ", $words));
            }
            
            // verify command qualifies for censorship
            if( $this->plugin->isCommandCensored($cmd) && isset($words) )
            {
                // if banned word found, cancel command and inform player about inappropriate word usage
                if( $this->plugin->hasBannedWord($message) === true )
                {
                    $this->plugin->logMessage(LogLevel::WARNING, "<$playername> used an inappropriate word. The command ($cmd) has been censored.");
                    $event->getPlayer()->sendMessage(TextFormat::RED . "Command cancelled due to inappropriate language. Administrator has been notified.");
                    $event->setCancelled(true);
                        
                } /* if( hasBannedWord ) */
                    
            } /* if( isCommandCensored ) */
                   
        } /* else $cmd (!register || !login) && showPasswords */
            
    } /* onPlayerCommand */
        
    public function onServerCommand(ServerCommandEvent $event)
    {
        $this->logConsoleCommand("CONSOLE", $event->getCommand()); 
        
    } /* onServerCommand */

    public function onRemoteCommand(RemoteServerCommandEvent $event)
    {  
        $this->logConsoleCommand("REMOTE", $event->getCommand()); 
        
    } /* onRemoteCommand */

    protected function logConsoleCommand($context, $message)
    {
        $words = \explode(" ", $message);
        $cmd   = \strtolower(\array_shift($words));
        if( $this->plugin->isCommandTracked($cmd) )
        {   
            $this->plugin->logMessage(LogLevel::INFO, "<$context> /$message");
        }
    }
}

?>