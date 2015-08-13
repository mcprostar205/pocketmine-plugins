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

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use BuddyNotify\BuddyNotify;

class EventTask extends PluginTask 
{
    /** @var BuddyNotify */
    private $plugin;
    
    /** @var Player */
    private $player;
    
    /** @var String */
    private $event;
    
    public function __construct(BuddyNotify $plugin, Player $player, $event) 
    {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->event  = $event;
        
        parent::__construct($plugin);
        
    } /* __construct */
  
    public function onRun($currentTick) 
    {
        if( isset($this->plugin) && isset($this->player) && isset($this->event) )
        {
            $this->plugin->onEvent($this->player, $this->event);
        }
         
    } /* onRun */
    
    public function onCancel()
    {
        unset($this->plugin);
        unset($this->player);
        unset($this->event);
    }
        
} /* class EventTask */

?>