<?php

namespace SkyBoss\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use SkyBoss\entity\types\DevilBossEntity;
use SkyBoss\entity\types\GodBossEntity;
use SkyBoss\entity\types\SuperHumanEntity;

class SpawnBossCommand extends Command{

    public function __construct(){
        parent::__construct("spawnboss", "", null, []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender->isOp()){
            $sender->sendMessage(TextFormat::RED . "Spawn boss needs permission");
            return;
        }
        if(!isset($args[0])){
            $sender->sendMessage("Usage: /$commandLabel (god|devil|superhuman)");
            return;
        }
        if(!$sender instanceof Player) return;
        switch($args[0]){
            case "god":
                $nbt = GodBossEntity::createBaseNBT($sender->getPosition());
                $entity = new GodBossEntity($sender->getLevel(), $nbt);
                $entity->spawnToAll();
                break;
            case "devil":
                $nbt = DevilBossEntity::createBaseNBT($sender->getPosition());
                $entity = new DevilBossEntity($sender->getLevel(), $nbt);
                $entity->spawnToAll();
                break;
            case "superhuman":
                $nbt = SuperHumanEntity::createBaseNBT($sender->getPosition());
                $entity = new SuperHumanEntity($sender->getLevel(), $nbt);
                $entity->spawnToAll();
                break;
        }
    }
}