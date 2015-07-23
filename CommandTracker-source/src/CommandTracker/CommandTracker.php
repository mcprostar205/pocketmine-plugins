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
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CommandTracker extends PluginBase implements CommandExecutor
{
    const PROP_SHOW_PASSWORDS   = "show-passwords";
    const PROP_CENSOR_COMMANDS  = "commands-censored";
    const PROP_BANNED_WORDS     = "words-banned";
     
    protected $passwordsVisible = false;
    protected $commandsCensored = [];
    protected $wordsBanned      = [];

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
            
        $this->passwordsVisible = $this->getConfig()->get(CommandTracker::PROP_SHOW_PASSWORDS);    
        $this->commandsCensored = \explode(",", $this->getConfig()->get(CommandTracker::PROP_CENSOR_COMMANDS));
        $this->wordsBanned      = \explode(",", $this->getConfig()->get(CommandTracker::PROP_BANNED_WORDS));
        
    } /* onEnable */

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        $message = null;
        
        // track command allowed only to be initiated by an Administrator from the console 
        if($sender instanceof Player)
        {
            $sender->sendMessage(TextFormat::YELLOW . "/track can only be executed from console");
            return true;
        }
    
        // verify the command is supported and sub-command provided 
        if( (\strcmp($command->getName(),"track") !== 0) || !isset($args) )
        {
            return true;
        }
        
        $subcmd = \strtolower(\array_shift($args));
        switch ( $subcmd )
        {
            /* ban word */
            case "bw-add":
                $message = $this->banWord($args[0]);
                break;
            
            /* unban word */
            case "bw-del":
                $message = $this->banWord($args[0],false);
                break;
            
            /* censor command */
            case "cc-add":
                $message = $this->censorCommand($args[0]);
                break;
            
            /* uncensor command */
            case "cc-del":
                $message = $this->censorCommand($args[0],false);
                break;
            
            /* toggle show password in logs */
            case "sp":
                $message = $this->togglePasswordVisibility();
                break;
                
            /* usage */
            case "?":
            default:
                $message .= TextFormat::GREEN . "/track bw-add " . TextFormat::YELLOW . "<word>"    . TextFormat::WHITE . " : Add a word to be censored\n";
                $message .= TextFormat::GREEN . "/track bw-del " . TextFormat::YELLOW . "<word>"    . TextFormat::WHITE . " : Remove a word from being censored\n";
                $message .= TextFormat::GREEN . "/track cc-add " . TextFormat::YELLOW . "<command>" . TextFormat::WHITE . " : Add a command to be censored\n";
                $message .= TextFormat::GREEN . "/track cc-del " . TextFormat::YELLOW . "<command>" . TextFormat::WHITE . " : Remove a command from being censored\n";
                $message .= TextFormat::GREEN . "/track sp "     . TextFormat::YELLOW . "<command>" . TextFormat::WHITE . " : Toggles password visibility in console log\n";
                break;
            
        } /* switch */
                
        if( $message !== null )
        {
            $sender->sendMessage($message);
        }
        return true;
        
    } /* onCommand */
    
    public function showPasswords()
    {
        return $this->passwordsVisible;
        
    } /* showPasswords */
        
    public function isCommandCensored($command)
    {
        $commandfound = false;
        $command = \strtolower($command);
        if( isset($this->commandsCensored) )
        {
            foreach( $this->commandsCensored as $censoredCommand )
            {
                if( \strcmp($command,$censoredCommand) === 0 )
                {
                    $commandfound = true;
                    break;
                }                
            } /* foreach */
        }
        return $commandfound;
        
    } /* isCommandCensored */
                
    public function hasBannedWord($message)
    {
        $wordfound = false;
        $words     = \explode(" ", $message);
        
        /* check for the first word banned word occurance */
        foreach( $this->wordsBanned as $bannedWord )
        {
            // breaks on the first inappropriate word encountered
            
            // complete word match?
            if( $bannedWord{0} === "\\" )
            {
                $bannedWord = \substr($bannedWord,1);
                foreach( $words as $word )
                {   
                    if( \strcasecmp($word,$bannedWord) === 0 )
                    {
                        $wordfound = true;
                        break 2;
                    }
                } /* foreach( $words as $word */
            }   
                
            // substring match?
            else if( \stripos($message,$bannedWord) !== false )
            {
                $wordfound = true;
                break;
            }
            
        } /* foreach( $this->wordsBanned as $bannedWord ) */
        
        return $wordfound;
        
    } /* hasBannedWord */
        
    protected function banWord($word, $add = true)
    {
        $message = null;
        $wordlist = $this->getConfig()->get(CommandTracker::PROP_BANNED_WORDS);
        $revisedlist = null;
        $savelist = false;
        
        if( $add === true )
        {
            if( isset($word) )
            {
                if(($revisedlist = $this->addCsvItem($word, $wordlist)) === true )
                {
                    $message = TextFormat::YELLOW . "Word already banned";
                }
                else
                {
                    $message = TextFormat::GREEN . "Word added to banned list";
                    $savelist = true;
                }               
            }
            else
            {
                $message = "Usage: /track bw-add <word>";
            }
        }
        else
        {
            if( isset($word) )
            {
                if(($revisedlist = $this->removeCsvItem($word, $wordlist)) === false)
                {
                    $message = TextFormat::YELLOW . "Word not currently banned";
                }
                else
                {
                    $message = TextFormat::GREEN . "Word removed from banned list";
                    $savelist = true;
                }
            }
            else
            {
                $message = "Usage: /track bw-del <word>";
            }            
        }
        
        if( $savelist === true )
        {
            $this->getConfig()->set(CommandTracker::PROP_BANNED_WORDS,$revisedlist);
            $this->getConfig()->save();
            $this->wordsBanned = \explode(",", $this->getConfig()->get(CommandTracker::PROP_BANNED_WORDS));    
        }
         
        return $message;
    } /* banWord */
    
    protected function censorCommand($command, $add = true)
    {
        $message = null;
        $commandlist = $this->getConfig()->get(CommandTracker::PROP_CENSOR_COMMANDS);
        $revisedlist = null;
        $savelist = false;
        
        if( $add === true )
        {
            if( isset($command) )
            {
                if(($revisedlist = $this->addCsvItem($command, $commandlist)) === true )
                {
                    $message = TextFormat::YELLOW . "Command already being censored";
                }
                else
                {
                    $message = TextFormat::GREEN . "Command added for censorship";
                    $savelist = true;
                }               
            }
            else
            {
                $message = "Usage: /track cc-add <command>";
            }
        }
        else
        {
            if( isset($command) )
            {
                if(($revisedlist = $this->removeCsvItem($command, $commandlist)) === false)
                {
                    $message = TextFormat::YELLOW . "Command not being censored";
                }
                else
                {
                    $message = TextFormat::GREEN . "Command removed from censorship";
                    $savelist = true;
                }
            }
            else
            {
                $message = "Usage: /track cc-del <command>";
            }            
        }
        
        if( $savelist === true )
        {
            $this->getConfig()->set(CommandTracker::PROP_CENSOR_COMMANDS,$revisedlist);
            $this->getConfig()->save();
            $this->commandsCensored = \explode(",", $this->getConfig()->get(CommandTracker::PROP_CENSOR_COMMANDS));    
        }
        
        return $message;
    } /* censorCommand */
    
    protected function togglePasswordVisibility( )
    {
        if( $this->passwordsVisible === true )
        {
            $this->passwordsVisible = false;
            $message = TextFormat::GREEN . "Turned 'off' passwords tracking";
        }
        else
        {
            $this->passwordsVisible = true;
            $message = TextFormat::GREEN . "Turned 'on' password tracking";
        }
        return $message;
        
    } /* togglePasswordVisibility */

    protected function addCsvItem($item,$csv)
    {
        $found   = false;
        $item    = \strtolower($item);
        $newcsv  = $csv;
        
        if( \strlen($newcsv) === 0 )
        {
            $newcsv = $item;
        }
        else 
        {
            $values = explode(",",$newcsv);
            
            /* verify the word isn't already added */
            foreach( $values as $value )
            {
                if( \strcmp($item,$value) === 0 )
                {
                    $found = true;
                }
            }
            
            if( $found === false )
            {
                $newcsv .= "," . $item;
            }
        }
        
        return ($found === true) ? true : $newcsv;
                
    } /* addCsvItem */
        
    protected function removeCsvItem($item,$csv)
    {
        $found   = false;
        $item    = \strtolower($item);
        $newcsv  = null;
        
        if( \strlen($csv) !== 0 )
        {
            $values  = explode(",",$csv);
            foreach( $values as $value )
            {
                if( \strcmp($item,$value) === 0 )
                {
                    $found = true;
                }
                else
                {
                    /* retain existing word not matching */
                    if( $newcsv === null )
                    {
                        $newcsv = $value;
                    }
                    else
                    {
                        $newcsv .= "," . $value;
                    }
                }
                
            } /* foreach */
                    
        } /* \strlen */
        
        return ($found === true) ? $newcsv : false;
            
    } /* removeCsvItem */

}
?>