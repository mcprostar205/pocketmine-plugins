<?php

/* 
 * Copyright (C) 2015 Scott Handley
 * https://github.com/mcprostar205/pocketmine-plugins/tree/master/BuddyNotify-source
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

namespace BuddyNotify;

use pocketmine\event\Listener;
use SimpleAuth\event\PlayerAuthenticateEvent;
use pocketmine\event\player\PlayerQuitEvent; 
use pocketmine\event\server\ServerCommandEvent;

class EventListener implements Listener
{              
    /** @var BuddyNotify */
    private $plugin;
    
    public function __construct(BuddyNotify $plugin)
    {
        $this->plugin = $plugin;
        
    } // __construct
        
    public function onPlayerAuthenticate(PlayerAuthenticateEvent $event)
    {
        if( $this->plugin->isFeatureEnabled(BuddyNotify::PROP_NOTIFY_ON_AUTH) === true )
        {
            $this->plugin->onEvent($event->getPlayer(),BuddyNotify::EVENT_AUTH);
        }
        
    } // onPlayerAuthenticate
        
    public function onPlayerQuit(PlayerQuitEvent $event)
    {
        if( $this->plugin->isFeatureEnabled(BuddyNotify::PROP_NOTIFY_ON_QUIT) === true )
        {
            $this->plugin->onEvent($event->getPlayer(),BuddyNotify::EVENT_QUIT);
        }
        
    } // onPlayerQuit

    public function onServerCommand(ServerCommandEvent $event)
    {
        /* check for suppressing SHUTDOWN/STARTUP/RELOAD notification 1 time */
        $message = $event->getCommand();
        if( !isset($message) || (isset($message) && \strlen($message) === 0) )
        {
            return;
        }
        else if( \substr_compare($message,"stop",0,4) === 0 )
        {
            $words = \explode(" ", $message);
            foreach( $words as $word )
            {
                if( isset($word[1]) && ($word === "noemail") )
                {
                    $this->plugin->setFeature(BuddyNotify::PROP_NOTIFY_ON_QUIT,false);
                    \touch($this->plugin->getDataFolder().BuddyNotify::TEMPDISABLE);
                    break;
                }
            }
        }
        else if( \substr_compare($message,"reload",0,6) === 0 )
        {
            $this->plugin->setFeature(BuddyNotify::PROP_NOTIFY_ON_QUIT,false);
            \touch($this->plugin->getDataFolder().BuddyNotify::TEMPDISABLE);
        }
        
    } // onServerCommand
}

?>