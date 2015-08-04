<?php

/* 
 * Copyright (C) 2015 Scott Handley <https://github.com/>
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

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class BuddyNotify extends PluginBase implements CommandExecutor 
{    
    const PROP_NOTIFIER_ACTIVE = "buddynotifier";
    const PROP_SYSTEM_ADDRESS  = "system-address";
    const PROP_NOTIFY_ON_START = "notify-start";
    const PROP_NOTIFY_ON_STOP  = "notify-stop";
    const PROP_NOTIFY_ON_AUTH  = "notify-auth";
    const PROP_NOTIFY_ON_QUIT  = "notify-quit";
    const PROP_DATE_FORMAT     = "date-format";
 
    const DB_ADDRESSES         = "address.yml";
    const DB_BUDDIES           = "buddy.yml";

    const EVENT_AUTH           = "event-auth";
    const EVENT_QUIT           = "event-quit";
    
    const COMMAND_SEND         = "cmd-send";
    
    const SYSTEM               = "___SYSTEM___";
            
    /** @var EventListener */
    protected $listener;

   /** @var boolean */
    protected $isActive = true;
   /** @var boolean */
    protected $notifyOnStart = true;
   /** @var boolean */
    protected $notifyOnStop = true;
   /** @var boolean */
    protected $notifyOnAuth = true;
   /** @var boolean */
    protected $notifyOnQuit = true;
    
    /** @var String */
    protected $systemAddress;
    
    /** @var String */
    protected $dateFormat = "D M j, Y, g:i a";
        
    /** @var String */
    protected $buddyConfigPath;

    /** @var String */
    protected $addressConfigPath;
    
    /** @var array */
    protected $l10n = [];
    
    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        
        /* messages configuration and localization file */
        $this->saveResource("l10n.yml", false);
        
        $this->listener = new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->listener, $this);
        
        $config = $this->getConfig()->getAll();     
        $this->isActive = $config[BuddyNotify::PROP_NOTIFIER_ACTIVE];
        $this->systemAddress = $config[BuddyNotify::PROP_SYSTEM_ADDRESS];
        $this->notifyOnStart = $config[BuddyNotify::PROP_NOTIFY_ON_START];
        $this->notifyOnStop = $config[BuddyNotify::PROP_NOTIFY_ON_STOP];
        $this->notifyOnAuth = $config[BuddyNotify::PROP_NOTIFY_ON_AUTH];
        $this->notifyOnQuit = $config[BuddyNotify::PROP_NOTIFY_ON_QUIT];
        $this->dateFormat = $config[BuddyNotify::PROP_DATE_FORMAT];
        
        $this->l10n = (new Config($this->getDataFolder() . "l10n.yml"))->getAll();
        
        $this->addressConfigPath = $this->getDataFolder() . BuddyNotify::DB_ADDRESSES;
        if( !file_exists($this->addressConfigPath) )
        {
            $this->getAddressConfig()->save();
        }

        $this->buddyConfigPath = $this->getDataFolder() . BuddyNotify::DB_BUDDIES;
        if( !file_exists($this->buddyConfigPath) )
        {
            $this->getBuddyConfig()->save();
        }   
        
        /* disable proactive alerts if the system administrator email address is not set */
        if( ($this->systemAddress === null) || (\strlen($this->systemAddress) === 0) )
        {
            $this->getLogger()->warning($this->l10n["console"]["msg-sysadmin-email-notset1"]);
            $this->isActive = false;
            $this->notifyOnStart = false;
            $this->notifyOnStop = false;
            $this->systemAddress = null;
        }
        
        /* startup notification */
        if( $this->isFeatureEnabled(BuddyNotify::PROP_NOTIFY_ON_START) === true )
        {
            $date = \date($this->dateFormat);
            $srvname = $this->getServer()->getServerName();
            $srvvers = $this->getServer()->getName() . " - " . $this->getServer()->getPocketMineVersion();
            
            $subject  = $this->l10n["email"]["subject"]["prefix"];
            $subject .=  \sprintf( $this->l10n["email"]["subject"]["event-start"],$srvname);
            
            $body = \sprintf( $this->l10n["email"]["body"]["event-start"],$date,$srvname,$srvvers);

            /* future - add plugins installed to the message */
//            $plugins = $this->getServer()->getPluginManager()->getPlugins();
            
            $this->sendSystemAlert($subject, $body);
        }
    } /* onEnable */

    public function onDisable() 
    {
        /* shutdown notification */
        if( $this->isFeatureEnabled(BuddyNotify::PROP_NOTIFY_ON_STOP) === true )
        {
            $date = \date($this->dateFormat);
            $srvname = $this->getServer()->getServerName();
            $srvvers = $this->getServer()->getName() . " - " . $this->getServer()->getPocketMineVersion();

            $subject  = $this->l10n["email"]["subject"]["prefix"];
            $subject .=  \sprintf( $this->l10n["email"]["subject"]["event-stop"],$srvname);
            
            $body = \sprintf( $this->l10n["email"]["body"]["event-stop"],$date,$srvname,$srvvers);
            
            $this->sendSystemAlert($subject, $body);
        }
    } /* onDisable */
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {           
        /** @var String */
        $message = null;
        
        /* determine the playername - player cmd or console cmd */
        $playername = "undefined";
        if($sender instanceof Player)
        {
            $playername = \strtolower($sender->getDisplayName());
        }
        /* console is not allowed to send email on-behalf of another user */
        else if( isset($args[0]) && (\strcmp($args[0],"s") !== 0) && (\strcmp($args[0],"send") !== 0) )
        { 
            $playername = \strtolower(\array_shift($args));
        }
        
        /* extract the sub-command */
        $subcmd = "help";
        if( isset($args[0]) ) 
        {
            $subcmd = \strtolower(\array_shift($args));
        }

        /* verify the command is supported */
        if( \strcmp($command->getName(),"email") === 0 )
        {
            /* execute the appropriate notification sub-command */
            switch( $subcmd )
            {
                case "a":
                case "add":
                    /* arg[0] = buddy */
                    if( isset($args[0]) )
                    {
                        $message = $this->addBuddy($playername,$args[0]);
                    }
                    else
                    {
                        $message = "Usage: /email a|add <buddy>";
                    }
                    break;
                
                case "d":
                case "del":
                case "remove":
                    /* arg[0] = buddy */
                    if( isset($args[0]) )
                    {
                        $message = $this->removeBuddy($playername,$args[0]);
                    }
                    else
                    {
                        $message = "Usage: /email d|del <buddy>";
                    }
                    break;
                        
                case "l":
                case "ls":
                case "list":
                    $message = $this->listBuddies($playername);
                    break;
                        
                case "s":
                case "send":
                    /* arg[0] = buddy; arg[1..n] = message */
                    if( isset($args[0],$args[1]) )
                    {
                        $to = \array_shift($args);
                        if($sender instanceof Player)
                        {
                            $subject  = $this->l10n["email"]["subject"]["prefix"];
                            $subject .= \sprintf( $this->l10n["email"]["subject"]["cmd-send"], $playername );  

                            $body  = \sprintf( $this->l10n["email"]["body"]["greeting"], $to );       
                            $body .= \sprintf( $this->l10n["email"]["body"]["cmd-send"], \implode( " ", $args) );   
                            $body .= \sprintf( $this->l10n["email"]["body"]["signature"], $playername, $this->getServer()->getServerName());

                            $message  = $this->sendEmail($playername, $to, $subject, $body);
                        }
                        else
                        {
                            $subject  = $this->l10n["email"]["subject"]["prefix"];
                            $subject .= \sprintf( $this->l10n["email"]["subject"]["cmd-send"], $this->l10n["labels"]["sysadmin"] );  
                            
                            $message = $this->sendSystemAlert($subject, \implode( " ", $args), $to );
                        }
                    }
                    else
                    {
                        $message = "Usage: /email s|send <buddy> <message>";
                    }
                    break;
                    
                case "r":
                case "reg":
                case "register":
                    /* arg[0] = email address */
                    if( isset($args[0]) )
                    {
                        $message = $this->addAddress($playername,$args[0]);
                    }
                    else
                    {
                        $message = "Usage: /email r|reg <email>";
                    }
                    break;
 
                case "ur":
                case "unreg":
                case "unregister":
                    $message = $this->removeAddress($playername);
                    break;

                /* allows a player to opt-out of another player's notifications */
                case "us":
                case "unsub":
                case "unsubscribe":
                    /* arg[0] = buddy */
                    if( isset($args[0]) )
                    {
                        $message = $this->removeBuddy($args[0],$playername);
                    }
                    else
                    {
                        $message = "Usage: /email us|unsub <buddy>";
                    }
                    break;

                case "?":
                case "h":
                case "help":
                    $message = $this->getCommandUsage(!($sender instanceof Player)?true:false);
                    break;
                
                default:
                    /* try to send email if value assigned to $subcmd is a player */
                    if( ($sender instanceof Player) && isset($args[0]) )
                    {
                        /* recipient would be in the $subcmd */
                        $to = $subcmd;
                        
                        $subject  = $this->l10n["email"]["subject"]["prefix"];
                        $subject .= \sprintf( $this->l10n["email"]["subject"]["cmd-send"], $playername );  
                        
                        $body  = \sprintf( $this->l10n["email"]["body"]["greeting"], $to );       
                        $body .= \sprintf( $this->l10n["email"]["body"]["cmd-send"], \implode( " ", $args) );   
                        $body .= \sprintf( $this->l10n["email"]["body"]["signature"], $playername, $this->getServer()->getServerName());

                        $message  = $this->sendEmail($playername,$to,$subject,$body);
                    }
                    else
                    {
                        $message = $this->getCommandUsage(!($sender instanceof Player)?true:false);
                    }
                    break;
            } /* switch */
        }
        else
        {
            $message = $this->getCommandUsage(!($sender instanceof Player)?true:false);
        }
        
        if( $message !== null )
        {
            $sender->sendMessage($message);
        }
        return true;
        
    } /* onCommand */	

    public function onEvent(Player $player, $event)
    {
        /* check that player has buddies subscribed */
        $playername = \strtolower($player->getDisplayName());
        $mybuddies = $this->getBuddyList($playername);
        if( $mybuddies === false )
        {
            $this->getLogger()->debug($this->l10n["console"]["msg_player_no_buddies"], $playername );
            return;
        }
        
        switch( $event )
        {
            case BuddyNotify::EVENT_AUTH:
                $this->sendBuddyAlert($player, $event);
                break;
                
            case BuddyNotify::EVENT_QUIT:
                /* spam prevention - only notify when the player was legitimately logged in before quitting */
                $authplugin = $this->getServer()->getPluginManager()->getPlugIn("SimpleAuth");
                if( ($authplugin !== null) && $authplugin->isPlayerAuthenticated($player) )
                {
                    $this->sendBuddyAlert($player, $event);
                }
                break;
                
        } /* switch */
        
    } /* onEvent */

    public function isFeatureEnabled($feature)
    {
        /* check that the master switch is active */
        if( $this->isActive !== true )
        {
            $this->getLogger()->warning( $this->l10n["console"]["msg-all-notifications-off"] );
            return false;
        }
        
        switch( $feature )
        {
            case BuddyNotify::PROP_NOTIFY_ON_START:
                return $this->notifyOnStart;
                
            case BuddyNotify::PROP_NOTIFY_ON_STOP:
                return $this->notifyOnStop;
                
            case BuddyNotify::PROP_NOTIFY_ON_AUTH:
                return $this->notifyOnAuth;
                
            case BuddyNotify::PROP_NOTIFY_ON_QUIT:
                return $this->notifyOnQuit;
                
            case BuddyNotify::COMMAND_SEND:
                return true;
        }
        return false;
        
    } /* isFeatureEnabled */
    
    public function isPlayerRegistered($playername)
    {
        return ($this->getAddressConfig()->get(\strtolower($playername)) === false) ? false : true;
        
    } /*isPlayerRegistered */
    
    public function getBuddyList($playername)
    {
        $buddyconfig = $this->getBuddyConfig();
        $mybuddies = $buddyconfig->get( $playername );
        if( $mybuddies !== false )
        {
            return \explode(",", $mybuddies);   
        }
        return $mybuddies;
        
    } /* getBuddyList */
        
    public function sendEmail($from, $to, $subject, $bodytext, $bcc = null )
    {
        /** @var String */
        $message = null;
        $addressconfig = $this->getAddressConfig();        
        
        if( $this->isFeatureEnabled(BuddyNotify::COMMAND_SEND) !== true )
        {
            return TextFormat::RED . $this->l10n["commands"]["msg-send-general-error"];
        }
        
        /* validate the $to and $from email addresses */
        $fromaddress = $this->systemAddress;
        if( \strcmp($from,BuddyNotify::SYSTEM) !== 0 )
        {
            $fromaddress = $addressconfig->get($from);
            if( $fromaddress === false )
            {
                return TextFormat::RED . \sprintf( $this->l10n["commands"]["msg-player-not-registered"], $from );
            }
        }

        $toaddress = $this->systemAddress;
        if( \strcmp($to,BuddyNotify::SYSTEM) !== 0 )
        {
            $toaddress = $addressconfig->get($to);
            if( $toaddress === false )
            {
                return TextFormat::RED . \sprintf( $this->l10n["commands"]["msg-player-not-registered"], $to );
            }
        }
        
        /* construct email headers */ 
        $headers   = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: " . $this->l10n["email"]["header-mime"];
//        $headers[] = "From: " . $from . " " . $myaddress;
//        $headers[] = "Reply-To: " . $from . " " . $myaddress;
//        $headers[] = "Return-Path: " . $from . " " . $myaddress;
        $headers[] = "X-Mailer: PHP v" . phpversion();

        if( ($bcc !== null) && (\strlen($bcc) > 0) )
        {   
            $headers[] = "Bcc: " . $bcc;
        }
        
        /* send the message */       
        if( mail($toaddress, $subject, $bodytext, implode( $this->l10n["email"]["header-crlf"], $headers ),"-f " . $fromaddress) )
        {
            $message = TextFormat::GREEN . $this->l10n["commands"]["msg-send-success"];
        }
        else
        {
            $message = TextFormat::RED . $this->l10n["commands"]["msg-send-general-error"];
            $this->getLogger()->error( \sprintf($this->l10n["console"]["msg-send-error"]) );
        }  
        return $message;
        
    } /* sendEmail */
    
    protected function getAddressConfig()
    {
        return new Config($this->addressConfigPath, Config::YAML);
        
    } /* getAddressConfig */
    
    protected function getBuddyConfig()
    {
        return new Config($this->buddyConfigPath, Config::YAML);
        
    } /* getBuddyConfig */

    protected function addBuddy($playername, $buddyname)
    {        
        $buddyfound = false;
        $buddyconfig = $this->getBuddyConfig();
        $mybuddies = $this->getBuddyList($playername);
        if( $mybuddies === false )
        {
            $buddyconfig->set($playername,$buddyname);
            $buddyconfig->save();
        }
        else
        {
            /* verify the buddy isn't already subscribed */
            foreach( $mybuddies as $mybuddy )
            {
                if( strcmp($mybuddy,$buddyname) === 0 )
                {
                    $buddyfound = true;
                }
            }
            
            if( $buddyfound === false )
            {
                $buddyconfig->set($playername,\implode(",", $mybuddies) . "," . $buddyname);
                $buddyconfig->save();
            }
        }
	
        return ($buddyfound !== true) ?
                TextFormat::GREEN . \sprintf( $this->l10n["commands"]["msg-add-success"], $buddyname ) :
                TextFormat::YELLOW . \sprintf( $this->l10n["commands"]["msg-add-error"], $buddyname );
        
    } /* addBuddy */
    
    protected function removeBuddy($playername, $buddyname)
    {
        $buddyfound = false;
        $mybuddies = $this->getBuddyList($playername);
        if( $mybuddies !== false )
        {
            $newbuddies = null;
            foreach( $mybuddies as $mybuddy )
            {
                if( \strcmp($mybuddy, \strtolower($buddyname)) === 0 )
                {
                    $buddyfound = true;
                }
                else
                {
                    /* retain existing buddy subscriptions not matching */
                    if( $newbuddies === null )
                    {
                        $newbuddies = $mybuddy;
                    }
                    else
                    {
                        $newbuddies .= "," . $mybuddy;
                    }
                }
            }
        
            $buddyconfig = $this->getBuddyConfig();
            if( $newbuddies === null )
            {
                $buddyconfig->remove($playername);    
            }
            else
            {
                $buddyconfig->set($playername,$newbuddies);
            }
            $buddyconfig->save();
            
        }
        
        return ( $buddyfound === true ) ? 
            TextFormat::GREEN . \sprintf( $this->l10n["commands"]["msg-del-success"], $buddyname ) :
            TextFormat::YELLOW   . \sprintf( $this->l10n["commands"]["msg-del-error"]  , $buddyname );
        
    } /* removeBuddy */
    
    protected function listBuddies($playername)
    {                
        $addressconfig = $this->getAddressConfig();
        $myaddress = $addressconfig->get($playername);
        if( $myaddress === false )
        {
            $myaddress = $this->l10n["commands"]["msg-list-email-unset"];
        }
            
        $buddyconfig = $this->getBuddyConfig();
        $mybuddies = $buddyconfig->get( $playername );
        if( $mybuddies === false )
        {
            $mybuddies = $this->l10n["commands"]["msg-list-buddy-unset"];
        }
        
        $message  = TextFormat::BLUE . $this->l10n["commands"]["msg-list-email-label"] . TextFormat::WHITE . $myaddress . "\n"; 
        $message .= TextFormat::BLUE . $this->l10n["commands"]["msg-list-buddy-label"] . TextFormat::WHITE . $mybuddies . "\n";
        return $message;
        
    } /* listBuddies */
    
    protected function addAddress($playername, $address)
    {
        if( filter_var($address, FILTER_VALIDATE_EMAIL) ) 
        {
            $addressconfig = $this->getAddressConfig();
            $addressconfig->set($playername,$address);
            $addressconfig->save();
            return TextFormat::GREEN . $this->l10n["commands"]["msg-reg-success"];
        }
        else
        {
            return TextFormat::RED . $this->l10n["commands"]["msg-reg-error"];  
        }
        
    } /* addAddress */

    protected function removeAddress($playername)
    {
        $addressconfig = $this->getAddressConfig();
        $addressconfig->remove($playername);
	$addressconfig->save();
        return TextFormat::GREEN . $this->l10n["commands"]["msg-unreg-success"];
        
    } /* removeAddress */

    protected function sendSystemAlert( $subject, $bodytext, $to = "all" )
    {
        /** @var String */
        $message       = null;
        $body          = null;
        $bccaddresses  = null;
        $addressconfig = $this->getAddressConfig();
                
        /* broadcast message (bcc) or to an individual player (to) */
        if( \strcmp($to,"all") === 0 )
        {
            $body = \sprintf($this->l10n["email"]["body"]["greeting"],$this->l10n["labels"]["players"]);
            $players = $addressconfig->getAll(true);
            foreach( $players as $player )
            {
                $address = $addressconfig->get($player);
                if( $bccaddresses === null)
                {
                    $bccaddresses = $address;
                }
                else
                {
                    $bccaddresses .= "," . $address;
                }
            }
            
            if($bccaddresses === null)
            {
                $this->getLogger()->warning( $this->l10n["console"]["msg-no-registered-emails"] );
                return $message;
            }
        }
        else
        {
            $body = \sprintf($this->l10n["email"]["body"]["greeting"], $to);
        }
                               
        $body .= \sprintf( $this->l10n["email"]["body"]["cmd-send"], $bodytext ); 
        $body .= \sprintf($this->l10n["email"]["body"]["signature"], $this->l10n["labels"]["sysadmin"], $this->getServer()->getServerName());
                
        return $this->sendEmail(BuddyNotify::SYSTEM, (\strcmp($to,"all")===0)?BuddyNotify::SYSTEM:$to, $subject, $body, $bccaddresses);
        
    } /* sendSystemAlert */
    
    protected function sendBuddyAlert( Player $player, $event ) 
    {
        /** @var String */
        $message    = null;        
        $date       = \date($this->dateFormat);
        $world      = $player->getLevel()->getFolderName();
        $playername = \strtolower($player->getDisplayName());
        $servername = $this->getServer()->getServerName();
        
        /* player may not have an email address registered - send from system account */
        $from = BuddyNotify::SYSTEM;
        if( $this->isPlayerRegistered( $playername ) )
        {
            $from = $playername;
        }

        $subject  = $this->l10n["email"]["subject"]["prefix"];
        $subject .= \sprintf( $this->l10n["email"]["subject"][$event], $playername, $servername ); 
                
        $body  = \sprintf( $this->l10n["email"]["body"][$event], $playername, $date, $servername, $world );
        $body .= $this->getOnlineUsers($playername);
        $body .= \sprintf($this->l10n["email"]["body"]["signature"], $this->l10n["labels"]["sysadmin"], $this->getServer()->getServerName());
        
        $mybuddies = $this->getBuddyList( $playername );
        foreach( $mybuddies as $mybuddy )
        {
            /* only send alert notifications to offline players */
            if( $this->getServer()->getPlayer($mybuddy) === null )
            {
                /* buddy may be subscribed but may not have an email address registered */
                if( !$this->isPlayerRegistered( $mybuddy ) )
                {
                    continue;
                }
                
                $body  = \sprintf($this->l10n["email"]["body"]["greeting"], $mybuddy) . $body;
                $message = $this->sendEmail($from, $mybuddy, $subject, $body);
            }
        }
        return $message;
        
    } /* sendBuddyAlert */

    protected function getOnlineUsers($playername)
    {
        $numplayers = 0;
        
        /* exclude online user section if header is empty */
        $message = $this->l10n["email"]["body"]["players-hdr"];
        if( ($message === null) || (\strlen($message) === 0) )
        {
            return null;
        }
        
        /* report all online players, except for the player that is joining */
        $players = $this->getServer()->getOnlinePlayers();
        foreach( $players as $player )
        {
            $name = $player->getDisplayName();
            if( \strcmp($name, $playername) !== 0 )
            {
                $numplayers = $numplayers + 1;
                $world = $player->getLevel()->getFolderName();
//                $mode = $player->getGamemode();
                $message .= \sprintf($this->l10n["email"]["body"]["players-row"], $name, $world);
            }
        } 
        
        return ($numplayers >= 1) ? ($message) : null;
        
    } /* getOnlineUsers */
 
    protected function getCommandUsage($isConsole = false)
    {
        $usage  = TextFormat::WHITE . $this->l10n["usage"]["header"] . "\n";
        if( !$isConsole )
        {
            $usage .= TextFormat::GREEN . "/email a|add " . TextFormat::YELLOW . "<buddy>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["player"]["cmd-add"] . "\n";
            $usage .= TextFormat::GREEN . "/email d|del " . TextFormat::YELLOW . "<buddy>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["player"]["cmd-del"] . "\n";
            $usage .= TextFormat::GREEN . "/email ls|list " . TextFormat::WHITE . " : " . $this->l10n["usage"]["player"]["cmd-list"] . "\n";
            $usage .= TextFormat::GREEN . "/email s|send " . TextFormat::YELLOW . "<player>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["player"]["cmd-send"] . "\n";
            $usage .= TextFormat::GREEN . "/email r|reg " . TextFormat::YELLOW . "<email>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["player"]["cmd-reg"] . "\n";
            $usage .= TextFormat::GREEN . "/email ur|unreg " . TextFormat::WHITE . " : " . $this->l10n["usage"]["player"]["cmd-unreg"] . "\n";
            $usage .= TextFormat::GREEN . "/email us|unsub " . TextFormat::YELLOW . "<buddy>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["player"]["cmd-unsub"] . "\n";
        }
        else 
        {
            $usage .= TextFormat::GREEN . "/email " . TextFormat::YELLOW . "<player>" . TextFormat::GREEN . " a|add " . TextFormat::YELLOW . "<buddy>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["console"]["cmd-add"] . "\n";
            $usage .= TextFormat::GREEN . "/email " . TextFormat::YELLOW . "<player>" . TextFormat::GREEN . " d|del " . TextFormat::YELLOW . "<buddy>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["console"]["cmd-del"] . "\n";
            $usage .= TextFormat::GREEN . "/email " . TextFormat::YELLOW . "<player>" . TextFormat::GREEN . " ls|list " . TextFormat::WHITE . " : " . $this->l10n["usage"]["console"]["cmd-list"] . "\n";
            $usage .= TextFormat::GREEN . "/email s|send " . TextFormat::YELLOW . "<player>|all <message>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["console"]["cmd-send"] . "\n";
            $usage .= TextFormat::GREEN . "/email " . TextFormat::YELLOW . "<player>" . TextFormat::GREEN . " r|reg "    . TextFormat::YELLOW . "<email>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["console"]["cmd-reg"] . "\n";
            $usage .= TextFormat::GREEN . "/email " . TextFormat::YELLOW . "<player>" . TextFormat::GREEN . " ur|unreg " . TextFormat::WHITE  . " : " . $this->l10n["usage"]["console"]["cmd-unreg"] . "\n";
            $usage .= TextFormat::GREEN . "/email " . TextFormat::YELLOW . "<buddy>"  . TextFormat::GREEN . " us|unsub " . TextFormat::YELLOW . "<player>" . TextFormat::WHITE . " : " . $this->l10n["usage"]["console"]["cmd-unsub"] . "\n";
        }
        return $usage;
        
    }   /* getCommandUsage */
}

?>