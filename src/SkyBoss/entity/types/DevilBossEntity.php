<?php

namespace SkyBoss\entity\types;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use SkyBoss\entity\BossEntity;
use SkyBoss\Main;

class DevilBossEntity extends BossEntity{

    private $specialAttackCd = 60 * 60 * 5;

    public function __construct(Level $level, CompoundTag $nbt){
        $this->setSkin(Main::getInstance()->getDevilSkin());
        parent::__construct($level, $nbt);
        $this->setMaxHealth(15000);
        $this->setHealth($this->getMaxHealth());
        $this->setScale(1.9);
    }


    public function onUpdate(int $currentTick): bool{
        $this->specialAttackCd--;
        if($this->specialAttackCd <= 0){
            $bb = $this->getBoundingBox()->expand(3, 3,3);
            foreach($this->getLevel()->getNearbyEntities($bb) as $entity){
                if($entity instanceof Player){
                    if($entity === $this){
                        continue;
                    }
//                    $entity->knockBack($entity, 0, $entity->getX() - $this->getX(), $entity->getZ() - $this->getZ(), 1);
                    $entity->setHealth($entity->getHealth() - mt_rand(30,40));
                    $entity->setOnFire(20);
                }
            }
            $this->specialAttackCd = 60 * 60 * 5;
        }
        return parent::onUpdate($currentTick);
    }

    public function getName(): string{
        return "DevilBoss";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 20 && $this->distanceSquared($player) < 2){
            $this->attackDelay = 0;
            $damage = mt_rand(10, 25);
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $player->attack($ev);
            $this->doHitAnimation();
        }
    }

    public function updateNametag(): void{
        $this->setNameTag("§4§lDEVIL" . TextFormat::EOL . TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::WHITE .
            $this->getHealth() . "/" . $this->getMaxHealth() . TextFormat::DARK_GRAY . "]");
        parent::updateNametag(); // TODO: Change the autogenerated stub
    }
}