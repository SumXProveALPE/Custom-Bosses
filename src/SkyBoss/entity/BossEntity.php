<?php

namespace SkyBoss\entity;

use SkyBoss\Settings;
use pocketmine\{
    block\Block,
    block\Fence,
    block\FenceGate,
    block\Lava,
    block\Liquid,
    block\Stair,
    block\StoneSlab,
    block\Water,
    entity\Creature,
    entity\Effect,
    entity\EffectInstance,
    entity\Entity,
    entity\Human,
    event\entity\EntityDamageByEntityEvent,
    event\entity\EntityDamageEvent,
    item\enchantment\Enchantment,
    item\enchantment\EnchantmentInstance,
    item\Item,
    level\Level,
    math\Math,
    math\Vector2,
    math\Vector3,
    nbt\tag\CompoundTag,
    Player,
    utils\TextFormat
};

class BossEntity extends Human{

    public const NETWORK_ID = self::PLAYER;

    public $target;
    public $stayTime = 0;
	public $lastUpdate = 0;

    protected $moveTime = 0;
    protected $friendly = false;
    protected $fireProof = false;
    protected $attackDelay = 0;
    protected $maxJumpHeight = 1.2;
    protected $checkTargetSkipTicks = 1;
    protected $damagedByPlayer = false;
    protected $maxAge = 0;
    protected $xpDropAmount = 0;
    protected $attackDistance = 2;

    public function getName(): string{
        return "Boss Entity";
    }

    public function onUpdate(int $currentTick): bool{
        if(!$this->isAlive() || $this->isClosed() || $this->isFlaggedForDespawn()){
            return false;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);
        $this->updateNametag();

        $target = $this->updateMove($tickDiff);

        if($target instanceof Entity && $target->distanceSquared($this) <= Settings::getBossDistance()){
            $this->checkAndAttackEntity($target);
        }elseif(
            $target instanceof Vector3
            && $this->distanceSquared($target) <= 10
            && $this->motion->y == 5
        ){
            $this->moveTime = 0;
        }

        return true;
    }

    public function updateNametag(): void{
//        $this->setNameTag("§b§lGOD" . TextFormat::EOL . TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::WHITE .
//        $this->getHealth() . "/" . $this->getMaxHealth() . TextFormat::DARK_GRAY . "]");
    }

    public function checkAndAttackEntity(Entity $player){
        $this->attackEntity($player);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 20 && $this->distanceSquared($player) < 2){
            $this->attackDelay = 0;
            $damage = Settings::getBossDamage()[array_rand(Settings::getBossDamage())];
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
            $player->attack($ev);
            $this->doHitAnimation();

            foreach(Settings::getBossEffect() as $effect){
                if(empty($effect)) continue;
                $effectInstance = new EffectInstance(Effect::getEffectByName($effect["name"]), $effect["duration"] * 20, $effect["amplifier"]);
                if($player instanceof Player){
                    if($player->getGamemode() === Player::SURVIVAL || $player->getGamemode() === Player::ADVENTURE){
                        $player->addEffect($effectInstance);
                    }
                }
            }
        }
    }

    public function entityBaseTick(int $tickDiff = 1): bool{
        $this->attackDelay += $tickDiff;
        return parent::entityBaseTick($tickDiff);
    }

    public function isKnockback() : bool{
        return $this->attackTime > 0;
    }

    public function updateMove($tickDiff){
        if($this->isClosed() or $this->getLevel() == null){
            return null;
        }

        if($this->isKnockback()){
            $kbLevel = 4 / Settings::getBossKnockback();
            $this->move(($this->motion->x * $tickDiff) / $kbLevel, $this->motion->y, ($this->motion->z * $tickDiff) / $kbLevel);
            $this->motion->y -= 0.2 * $tickDiff;
            $this->updateMovement();
            return null;
        }

        $before = $this->getBaseTarget();
        $this->changeTarget();
        if($this->getBaseTarget() instanceof Creature or $this->getBaseTarget() instanceof Block or $before !== $this->getBaseTarget() and
            $this->getBaseTarget() !== null
        ){
            $x = $this->getBaseTarget()->x - $this->x;
            $y = $this->getBaseTarget()->y - $this->y;
            $z = $this->getBaseTarget()->z - $this->z;

            $diff = abs($x) + abs($z);
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motion->x = 0;
                $this->motion->z = 0;
            }elseif($diff > 0){
                $this->motion->x = Settings::getBossSpeed() * 0.15 * ($x / $diff);
                $this->motion->z = Settings::getBossSpeed() * 0.15 * ($z / $diff);
                $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
            }
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));

        }

        $dx = $this->motion->x * $tickDiff;
        $dz = $this->motion->z * $tickDiff;
        $isJump = false;
        $this->checkBlockCollision();
        if($this->isCollidedHorizontally or $this->isUnderwater()){
            $isJump = $this->checkJump($dx, $dz);
            $this->updateMovement();
        }
        if($this->stayTime > 0){
            $this->stayTime -= $tickDiff;
            $this->move(0, $this->motion->y * $tickDiff, 0);
        }else{
            $futureLocation = new Vector2($this->x + $dx, $this->z + $dz);
            $this->move($dx, $this->motion->y * $tickDiff, $dz);
            $myLocation = new Vector2($this->x, $this->z);
            if(($futureLocation->x != $myLocation->x || $futureLocation->y != $myLocation->y) && !$isJump){
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if(!$isJump){
            if($this->isOnGround()){
                $this->motion->y = 0;
            }elseif($this->motion->y > -$this->gravity * 4){
                if(!($this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid)){
                    $this->motion->y -= $this->gravity * 1;
                }
            }else{
                $this->motion->y -= $this->gravity * $tickDiff;
            }
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        $this->updateMovement();
        parent::updateMovement();
        return $this->getBaseTarget();
    }

    protected function checkJump($dx, $dz){
        if($this->motion->y == $this->gravity * 2){
            return $this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Liquid;
        }else{
            if($this->getLevel()->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid){
                $this->motion->y = $this->gravity * 2;
                return true;
            }
        }

        if($this->motion->y > 0.1 or $this->stayTime > 0){
            return false;
        }

        if($this->getDirection() === null){
            return false;
        }

        // sometimes entities overlap blocks and the current position is already the next block in front ...
        // they overlap especially when following an entity - you can see it when the entity (e.g. creeper) is looking
        // in your direction but cannot jump (is stuck). Then the next line should apply
        $blockingBlock = $this->getLevel()->getBlock($this->getPosition());
        if($blockingBlock->canPassThrough()){ // when we can pass through the current block then the next block is blocking the way
            try{
                $blockingBlock = $this->getTargetBlock(2); // just for correction use 2 blocks ...
            }catch(\InvalidStateException $ex){
                return false;
            }
        }
        if($blockingBlock != null/* and !$blockingBlock->canPassThrough()*/ and $this->getMaxJumpHeight() > 0){
            // we cannot pass through the block that is directly in front of entity - check if jumping is possible
            $upperBlock = $this->getLevel()->getBlock($blockingBlock->add(0, 1, 0));
            $secondUpperBlock = $this->getLevel()->getBlock($blockingBlock->add(0, 2, 0));
            // check if we can get through the upper of the block directly in front of the entity
            if($upperBlock->canPassThrough() && $secondUpperBlock->canPassThrough()){
                if($blockingBlock instanceof Fence || $blockingBlock instanceof FenceGate){ // cannot pass fence or fence gate ...
                    $this->motion->y = $this->gravity;
                }else if($blockingBlock instanceof StoneSlab or $blockingBlock instanceof Stair){ // on stairs entities shouldn't jump THAT high
                    $this->motion->y = $this->gravity * 4;
                }else if($this->motion->y < ($this->gravity * 3.2)){ // Magic
                    $this->motion->y = $this->gravity * 3.2;
                    if($blockingBlock instanceof Lava || $blockingBlock instanceof Water){
                        $this->motion->y = $this->motion->y * 2;
                    }
                }else{
                    $this->motion->y += $this->gravity * 0.25;
                }
                return true;
            }elseif(!$upperBlock->canPassThrough()){
                $this->yaw = $this->getYaw() + mt_rand(-120, 120) / 10;
            }
        }
        return false;
    }

    protected function changeTarget(){
        if($this->target instanceof Creature and $this->target->isAlive()){
            return;
        }
        if(!$this->target instanceof Creature || !$this->target->isAlive() || $this->target->isClosed() || $this->target->isFlaggedForDespawn()){
            foreach($this->getLevel()->getEntities() as $entities){
                if($entities === $this or !($entities instanceof Creature) || $entities instanceof self){
                    continue;
                }
                if($this->distanceSquared($entities) > 81){
                    continue;
                }
                if($entities instanceof Player){
                    if($entities->getGamemode() !== Player::ADVENTURE && $entities->getGamemode() !== Player::SURVIVAL){
                        continue;
                    }
                }
                $this->target = $entities;
            }
        }
    }

    public function getDrops(): array{
        $drops = [];
        foreach(Settings::getBossDrops() as $drop){
            if(empty($drop)) continue;
            if(is_numeric($drop["id"])){
                $itemID = $drop["id"];
            }else{
                $itemID = constant(Item::class . "::" . strtoupper($drop["id"]));
            }
            $item = Item::get($itemID, $drop["damage"] ?? 0, $drop["amount"] ?? 1);
            if(isset($drop["name"])){
                $item->setCustomName($drop["name"]);
            }
            if(isset($drop["enchantments"])){
                foreach($drop["enchantments"] as $ench => $lvl){
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantmentByName($ench), $lvl));
                }
            }
            $drops[] = $item;
        }
        return $drops;
    }

    public function getBaseTarget(): ?Vector3{
        return $this->target;
    }

    public function getMaxJumpHeight(): int{
        return 3;
    }
}
