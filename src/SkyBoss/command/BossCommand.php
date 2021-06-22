<?php

namespace SkyBoss\command;

use SkyBoss\Main;
use pocketmine\{
    command\CommandSender, command\PluginCommand, plugin\Plugin, Server, Player, utils\TextFormat
};

class BossCommand extends PluginCommand{

    public function __construct(Plugin $owner){
        parent::__construct("boss", $owner);
        $this->setPermission("skyboss.command");
        $this->setDescription("Boss Command");
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool{
        if($player instanceof Player){
            /** @var Main $plugin */
            $plugin = $this->getPlugin();
            if(isset($args[0])){
                $manager = $plugin->getBossManager();
                switch($args[0]){
                    case "start":
                        if(!$manager->isOn()){
                            $manager->start();
                            $player->sendMessage(TextFormat::GREEN . "Boss Event has been started");
                        }else{
                            $player->sendMessage(TextFormat::RED . "Boss Event has already started");
                        }
                        break;
                    case "stop":
                        if($manager->isOn()){
                            $manager->stop();
                            $player->sendMessage(TextFormat::RED . "Boss Event has been stopped");
                        }else{
                            $player->sendMessage(TextFormat::RED . "Boss is not running");
                        }
                        break;
                    default:
                        $player->sendMessage(TextFormat::RED . "Usage:\n/boss start\n/boss stop");
                        break;
                }
            }else{
                $player->sendMessage(TextFormat::RED . "Usage:\n/boss start\n/boss stop");
            }
        }
        return true;
    }
}
