<?php

namespace SkyBoss;

use pocketmine\{
    network\mcpe\protocol\RemoveObjectivePacket,
    network\mcpe\protocol\SetDisplayObjectivePacket,
    network\mcpe\protocol\SetScorePacket,
    network\mcpe\protocol\types\ScorePacketEntry,
    Server,
    Player,
    utils\TextFormat
};

final class Session{

    /** @var Player $player */
    private $player;
    /** @var Main $plugin */
    private $plugin;

    /** @var ScorePacketEntry[] $scEntries */
    protected $scEntries = [];
    /** @var int $scId */
    protected $scId;
    /** @var string $scoreboardObjName */
    protected $scoreboardObjName = " ";

    protected const MAX_LINES = 15;

    public function __construct(Player $player, Main $plugin){
        $this->player = $player;
        $this->plugin = $plugin;
        $this->scoreboardObjName = Settings::getBossScoreboardTitle();
        $this->scId = Main::$scoreboardIds++;
    }

    /**
     * @return null|Player
     */
    public function getPlayer(): ?Player{
        return $this->player;
    }

    /**
     * @param ScorePacketEntry[]|null $lines
     */
    public function sendScoreboard(?array $lines = null): void{
        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_CHANGE;
        $pk->entries = $lines ?? $this->scEntries;

        $this->getPlayer()->sendDataPacket($pk);
    }

    public function addDisplay(): void{
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = $this->scoreboardObjName;
        $pk->displayName = $this->scoreboardObjName;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $this->getPlayer()->sendDataPacket($pk);

        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = "test";
        $this->getPlayer()->sendDataPacket($pk);
    }

    public function removeDisplay(): void{
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $this->scoreboardObjName;
        $this->getPlayer()->sendDataPacket($pk);
    }

    public function scoreboardContent(): void{
        $player = $this->getPlayer();
        $manager = $this->plugin->getBossManager();
        if($manager->isOn()){
            $this->removeLines();
            $this->addDisplay();
            $entity = $this->plugin->getBossEntity();

            foreach(Settings::getBossScoreboardContent() as $i => $content){
                $content = str_replace("{life}", $entity->getHealth() . "/" . $entity->getMaxHealth(), $content);
                $content = str_replace("{coords}", "X: " . $entity->getFloorX() . " Y: " . $entity->getFloorY() . " Z: " . $entity->getFloorZ(), $content);
                $content = str_replace("{level}", $entity->getLevel()->getName(), $content);
                $this->setLine($i, $content, false);
            }
            $this->sendScoreboard();
        }else{
            $this->removeDisplay();
        }
    }


    /**
     * @param int $line
     * @param string $message
     * @param bool $send
     */
    public function setLine(int $line, string $message, bool $send = true): void{
        $this->removeLine($line, $send);

        if(!isset($this->scEntries[$line - 1]) && $line !== 1){
            for($i = 1; $i <= ($line - 1); $i++){
                if(!isset($this->scEntries[$i - 1])){
                    $entry = new ScorePacketEntry();
                    $entry->objectiveName = $this->scoreboardObjName;
                    $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
                    $entry->customName = str_repeat(" ", $i); //You can't send two lines with the same message
                    $entry->score = $i;
                    $entry->scoreboardId = ($this->scId + $i);
                    $this->scEntries[$i - 1] = $entry;
                }
            }
        }

        $entry = new ScorePacketEntry();
        $entry->objectiveName = $this->scoreboardObjName;
        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
        $entry->customName = $message;
        $entry->score = $line;
        $entry->scoreboardId = ($this->scId + $line); //You can't send two lines with the same sc id

        $this->scEntries[$line - 1] = $entry;

        if($send){
            $this->sendScoreboard([$entry]);
        }
    }

    /**
     * @param int $line
     * @param bool $send
     */
    public function removeLine(int $line, bool $send = true): void{
        if($send){
            if(isset($this->scEntries[$line - 1])){
                $entry = $this->scEntries[$line - 1];
            }else{
                $entry = new ScorePacketEntry();
                $entry->objectiveName = $this->scoreboardObjName;
                $entry->score = $line;
                $entry->scoreboardId = ($this->scId + $line);
            }

            $this->removeLines([$entry]);
        }

        unset($this->scEntries[$line - 1]);
    }

    /**
     * @param ScorePacketEntry[]|null $lines
     */
    public function removeLines(?array $lines = null) : void{
        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_REMOVE;
        $pk->entries = $lines ?? $this->scEntries;
        $this->getPlayer()->sendDataPacket($pk);
    }

    /**
     * @param string $str
     * @return string
     */
    private function pad(string $str): string{
        return str_pad($str, 25);
    }
}
