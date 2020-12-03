<?php

namespace AbruptYT\SlashMSG;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;

/**
 * Diese Klasse repräsentiert das SlashMSG Plugin.
 * @author AbruptYT
 * @version 1.0
 */
class SlashMSG extends PluginBase {

    public static $instance, $lastMSG = [], $msgToggle = [];
    const PREFIX = "§l§9MSG §r§8» §r";

    public function onEnable() {
        $this->getLogger()->info("Plugin wurde erfolgreich aktiviert!");
        self::$instance = $this;

        $this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);

        $cmdmap = $this->getServer()->getCommandMap();

        /**
         * @var $cmds string[]
         */
        $cmds = ["msg", "tell", "r", "reply", "msgtoggle"];

        foreach ($cmds as $cmd) {
            /**
             * @var $cmd Command|null
             */
            $cmd = $this->getServer()->getPluginCommand($cmd);
            if($cmd != null) {
                $cmdmap->unregister($cmd);
            }
        }

        $cmdmap->registerAll("SlashMSG", [new MSGCommand("msg", $this), new ReplyCommand("reply", $this), new MSGToggleCommand("msgtoggle", $this)]);
    }

    public function onDisable() {
        $this->getLogger()->alert("deaktiviere Plugin...");
    }

    /**
     * @return Plugin
     */
    public static function getInstance() {
        return self::$instance;
    }

}

/**
 * Diese Klasse repräsentiert den /msg Command.
 * @author AbruptYT
 * @version 1.0
 */
class MSGCommand extends PluginCommand {

    public function __construct(string $name, Plugin $owner) {
        parent::__construct($name, $owner);
        $this->setDescription("Sende eine private Nachricht an einen Spieler.");
        $this->setAliases(["message"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {

        if(!$sender instanceof Player) return false;

        if(!isset($args[0])) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cBitte gib einen Namen an.");
            return false;
        }

        /**
         * @var $player Player|null
         */
        $player = SlashMSG::getInstance()->getServer()->getPlayer($args[0]);

        if($player === null) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cSpieler wurde nicht gefunden!");
            return false;
        }

        if(!isset($args[1])) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cBitte gib eine Nachricht ein!");
            return false;
        }

        if(isset(SlashMSG::$msgToggle[$player->getName()])) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cDu kannst diesem Spieler keine Privatnachrichten senden!");
            return false;
        }

        unset($args[0]);

        /**
         * @var $message string
         */
        $message = implode(" ", $args);

        $sName = $sender->getName();
        $pName = $player->getName();

        SlashMSG::$lastMSG[$pName] = $sName;

        $player->sendMessage("§7[§e". $sName ." §8-> §cdir§7] §r" . $message);
        $sender->sendMessage("§7[§cDu §8-> §e" . $pName . "§7] §r" . $message);

    }

}

/**
 * Diese Klasse repräsentiert den /reply Command.
 * @author AbruptYT
 * @version 1.0
 */
class ReplyCommand extends PluginCommand {

    public function __construct(string $name, Plugin $owner) {
        parent::__construct($name, $owner);
        $this->setDescription("Antworte auf eine private Nachricht.");
        $this->setAliases(["r"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {

        if(!$sender instanceof Player) return false;

        $sName = $sender->getName();

        if(!isset(SlashMSG::$lastMSG[$sName])) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cDir hat noch niemand geschrieben!");
            return false;
        }

        /**
         * Player name of the last message sender.
         * @var $lastMSG string|null
         */
        $lastMSG = SlashMSG::$lastMSG[$sName];

        /**
         * The player who sent the last message to the sender.
         * @var $player Player|null
         */
        $player = SlashMSG::getInstance()->getServer()->getPlayer($lastMSG);

        if($player === null) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cDer Spieler §e" . $lastMSG . "§c ist offline!");
            return false;
        }

        if(!isset($args[0])) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cBitte gib eine Nachricht an!");
            return false;
        }

        $pName = $player->getName();

        if(isset(SlashMSG::$msgToggle[$player->getName()])) {
            $sender->sendMessage(SlashMSG::PREFIX . "§cDu kannst diesem Spieler keine Privatnachrichten senden!");
            return false;
        }


        /**
         * @var $message string
         */
        $message = implode(" ", $args);

        $player->sendMessage("§7[§e". $sName ." §8-> §cdir§7] §r" . $message);
        $sender->sendMessage("§7[§cDu §8-> §e" . $pName . "§7] §r" . $message);

    }

}

/**
 * Diese Klasse repräsentiert den /msgtoggle Command.
 * @author AbruptYT
 * @version 1.0
 */
class MSGToggleCommand extends PluginCommand {

    public function __construct(string $name, Plugin $owner) {
        parent::__construct($name, $owner);
        $this->setDescription("Deaktiviere deine Privatnachrichten.");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {

        if(!$sender instanceof Player) return false;

        $sName = $sender->getName();

        if(isset(SlashMSG::$msgToggle[$sName])) {
            unset(SlashMSG::$msgToggle[$sName]);
            $sender->sendMessage(SlashMSG::PREFIX . "§aDu hast deine MSG's aktiviert!");
        } elseif (!isset(SlashMSG::$msgToggle[$sName])) {
            SlashMSG::$msgToggle[$sName] = true;
            $sender->sendMessage(SlashMSG::PREFIX . "§cDu hast deine MSG's deaktiviert!");
        }

    }

}

/**
 * Diese Klasse repräsentiert den EventHandler.
 * @author AbruptYT
 * @version 1.0
 */
class EventHandler implements Listener {

    public function onQuit(PlayerQuitEvent $event) {
        $pName = $event->getPlayer()->getName();
        if(isset(SlashMSG::$lastMSG[$pName])) unset(SlashMSG::$lastMSG[$pName]);
        if(isset(SlashMSG::$msgToggle[$pName])) unset(SlashMSG::$msgToggle[$pName]);
    }

}