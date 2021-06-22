<?php

namespace SkyBoss;

use pocketmine\{
    entity\Entity,
    entity\Skin,
    event\block\BlockPlaceEvent,
    event\Listener,
    event\player\PlayerJoinEvent,
    event\player\PlayerQuitEvent,
    item\Item,
    nbt\tag\ByteArrayTag,
    nbt\tag\CompoundTag,
    nbt\tag\DoubleTag,
    nbt\tag\FloatTag,
    nbt\tag\ListTag,
    nbt\tag\StringTag,
    Server,
    Player
};

use SkyBoss\{
    entity\BossEntity
};

class EventListener implements Listener{

    /** @var Main $plugin */
    private $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerJoinEvent $ev
     * @priority HIGHEST
     * @ignoreCancelled TRUE
     */
    public function onPlayerJoin(PlayerJoinEvent $ev): void{
        $player = $ev->getPlayer();
        $this->plugin->openSession($player);
    }

    /**
     * @param PlayerQuitEvent $ev
     * @priority HIGHEST
     * @ignoreCancelled TRUE
     */
    public function onPlayerQuit(PlayerQuitEvent $ev): void{
        $player = $ev->getPlayer();
        $this->plugin->closeSession($player);
    }
}
