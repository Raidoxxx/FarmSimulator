<?php

namespace farm\player;

use farm\database\models\Database;
use farm\database\repositories\PlayerRepository;
use farm\events\player\PlayerAddExpEvent;
use farm\events\player\PlayerLevelUp;
use farm\Main;
use farm\manager\missions\Mission;
use farm\particles\FloatingText;
use farm\world\FarmWorld;
use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;

class FarmingPlayer extends Player
{
    private array $chunks;
    private int $money;
    private int $level;
    private int $exp;
    private int $expToNextLevel;
    private int $farmSize;
    private int $grow = 0;
    private Database $database;
    private bool $needsSave = false;
    private ?World $farmWorld = null;
    private array $missions = [];

    const BREAK = 0;
    const PLACE = 1;
    const INTERACT = 2;

    public function __construct(Server $server, NetworkSession $session, PlayerInfo $playerInfo, bool $authenticated, Location $spawnLocation, ?CompoundTag $namedtag)
    {
        $this->database = Main::getInstance()->getDatabase();
        parent::__construct($server, $session, $playerInfo, $authenticated, $spawnLocation, $namedtag);
        $this->loadData();
        $this->loadWorldPlayer();
    }

    public function initEntity(CompoundTag $nbt): void
    {
        if ($this->farmWorld) {
            $this->teleport($this->farmWorld->getSpawnLocation());
        }
        parent::initEntity($nbt);
    }

    public function loadWorldPlayer(): void
    {
        Server::getInstance()->getWorldManager()->loadWorld($this->getName());
        $world = $this->getServer()->getWorldManager()->getWorldByName($this->getName());
        if ($world === null) {
            $this->getServer()->getWorldManager()
                ->generateWorld(
                    $this->getName(),
                    WorldCreationOptions::create()->setGeneratorClass(FarmWorld::class)
                );

            $world = $this->getServer()->getWorldManager()->getWorldByName($this->getName());
        }

        $world->setAutoSave(true);
        $world->setSpawnLocation(new Vector3(264, 71, 264));
        $this->farmWorld = $world;
    }

    private function loadData(): void
    {
        $repository = $this->database->getRepository("players");
        if ($repository instanceof PlayerRepository) {
            $repository->findByPlayerUuid($this->getUniqueId()->toString(),
                function ($playerData) {
                    if ($playerData !== null) {
                        $this->chunks = json_decode($playerData['chunks'], true);
                        $this->money = $playerData['money'];
                        $this->level = $playerData['level'];
                        $this->exp = $playerData['exp'];
                        $this->expToNextLevel = $playerData['expToNextLevel'];
                        $this->farmSize = $playerData['farmSize'];
                    } else {
                        $this->chunks = ["16:16"];
                        $this->money = 0;
                        $this->level = 1;
                        $this->exp = 0;
                        $this->expToNextLevel = 100;
                        $this->farmSize = 0;
                    }
                }
            );
        }


        $this->database->getRepository("player_missions")->findByPlayerUuid($this->getUniqueId()->toString(),
            function ($missionsData) {
                if (!empty($missionsData)) {
                    $mission = Main::getInstance()->getPlayerManager()->getMission($missionsData['mission_id']);

                    if ($mission !== null) {
                        $mission->setProgress($missionsData['progress']);
                        $mission->setCompleted($missionsData['completed']);
                        $mission->setStartTime($missionsData['start_time']);
                        $this->missions[] = $mission;
                    }
                }
            }
        );
    }

    /**
     * @return Mission[]
     */
    public function getMissions(): array
    {
        return $this->missions;
    }

    public function saveData(bool $force = false): void
    {
        if ($this->needsSave || $force) {
            $data = [
                'uuid' => $this->getUniqueId()->toString(),
                'name' => $this->getName(),
                'chunks' => json_encode($this->chunks),
                'money' => $this->money,
                'level' => $this->level,
                'exp' => $this->exp,
                'expToNextLevel' => $this->expToNextLevel,
                'farmSize' => $this->farmSize
            ];

            $this->database->getRepository("players")->save($data);

            /** @var Mission $mission */
            foreach ($this->missions as $mission) {
                $missionsData = [
                    'player_uuid' => $this->getUniqueId()->toString(),
                    'mission_id' => $mission->getId(),
                    'progress' => $mission->getProgress(),
                    'completed' => $mission->isCompleted(),
                    'start_time' => $mission->getStartTime()
                ];

                $this->database->getRepository("player_missions")->save($missionsData);
            }
            $this->needsSave = false;
        }
    }

    private function calculateExpToNextLevel(): int
    {
        return (int)(100 * pow(1.2, $this->level - 1));
    }

    // Getters e Setters com marcação para salvamento
    public function getChunks(): array
    {
        return $this->chunks;
    }

    public function addChunk(string $chunk): void
    {
        $this->chunks[] = $chunk;
        $this->needsSave = true;
    }

    public function removeChunk(string $chunk): void
    {
        $key = array_search($chunk, $this->chunks);
        if ($key !== false) {
            unset($this->chunks[$key]);
            $this->needsSave = true;
        }
    }

    public function getMoney(): int
    {
        return $this->money;
    }

    public function setMoney(int $money): void
    {
        $this->money = $money;
        $this->needsSave = true;
    }

    public function addMoney(int $amount): void
    {
        $this->money += $amount;
        $this->needsSave = true;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
        $this->expToNextLevel = $this->calculateExpToNextLevel();
        $this->needsSave = true;
    }

    public function addExp(int $amount): void
    {
        $this->exp += $amount;
        $ev = new PlayerAddExpEvent($this, $amount);
        $ev->call();
        while ($this->exp >= $this->expToNextLevel) {
            $this->exp -= $this->expToNextLevel;
            $this->level++;
            $this->expToNextLevel = $this->calculateExpToNextLevel();
            $event = new PlayerLevelUp($this, $this->level);
            $event->call();
        }
        $this->needsSave = true;
    }

    public function getFarmSize(): int
    {
        return $this->farmSize;
    }

    public function setFarmSize(int $farmSize): void
    {
        $this->farmSize = $farmSize;
        $this->needsSave = true;
    }

    public function __destruct()
    {
        $this->saveData();
    }

    public function breakBlock(Vector3 $pos): bool
    {
        if ($this->handleActionBlocks($pos, self::BREAK)) {
            return parent::breakBlock($pos);
        }

        return false;
    }

    public function interactBlock(Vector3 $pos, int $face, Vector3 $clickOffset): bool
    {
        if ($this->handleActionBlocks($pos, self::INTERACT)) {
            return parent::interactBlock($pos, $face, $clickOffset);
        }

        return false;
    }

    public function useHeldItem(): bool
    {
        return $this->handleItem($this->getInventory()->getItemInHand()) && parent::useHeldItem();
    }

    public function handleActionBlocks(Vector3 $pos, int $action): bool
    {
        if ($this->farmWorld !== null && $this->getWorld() === $this->farmWorld) {
            if ($this->inChunk($pos)) {
                if ($action === self::BREAK) {
                    if ($this->isFarmBlock($this->getWorld()->getBlock($pos))) {
                        if ($this->iCanBreakFarm($this->getWorld()->getBlock($pos))) {
                            $this->handleBlockFarm($pos);
                        } else {
                            $this->noHasLevel($this->getWorld()->getBlock($pos));
                            return false;
                        }
                    }
                }

                if ($action === self::INTERACT) {
                    if ($this->isFarmBlock($this->getWorld()->getBlock($pos))) {
                        if ($this->iCanBreakFarm($this->getWorld()->getBlock($pos))) {
                            return true;
                        } else {
                            $this->noHasLevel($this->getWorld()->getBlock($pos));
                            return false;
                        }
                    }
                }
                return true;
            } else {
                $this->sendPopup("§cVocê não pode interagir com blocos fora do seu Farm!");
                return false;
            }
        }

        return true;
    }

    public function inChunk(Vector3 $pos): bool
    {
        if ($this->farmWorld !== null) {
            if ($this->getWorld()->getFolderName() !== $this->farmWorld->getFolderName()) {
                return false;
            }
            $availableChunks = $this->getChunks();
            $chunkX = $pos->x >> 4;
            $chunkZ = $pos->z >> 4;

            foreach ($availableChunks as $chunk) {
                $chunk = explode(":", $chunk);
                if ($chunk[0] == "$chunkX" && $chunk[1] == "$chunkZ") {
                    return true;
                }
            }
        }

        return false;
    }

    public function iCanBreakFarm(Block $block): bool
    {
        if ($this->isFarmBlock($block)) {
            $name = strtolower(str_replace([" ", "Block"], ["", ""], $block->getName()));
            if (isset(Main::getInstance()->getFarmPrices()[$name])) {
                $level = Main::getInstance()->getFarmLevels()[$name];

                if ($this->getLevel() < $level) {
                    return false;
                }
            }
        }
        return true;
    }


    public function isFarmBlock(Block $block): bool
    {
        $name = strtolower(str_replace([" ", "Block"], ["", ""], $block->getName()));
        return isset(Main::getInstance()->getFarmPrices()[$name]);
    }

    public function handleBlockFarm(Vector3 $pos): void
    {
        $block = $this->getWorld()->getBlock($pos);
        $name = strtolower(str_replace([" ", "Block"], ["", ""], $block->getName()));
        $price = Main::getInstance()->getFarmPrices()[$name];
        $exp = Main::getInstance()->getFarmXp()[$name];
        $this->addMoney($price);
        $this->addExp($exp);
        $this->sendPopup("§a+$price");
        $this->sendPopup("§a+{$exp} XP");
        $floatingText = new FloatingText("§a+§R$$price", "§l§a+{$exp} XP", 12);
        $this->getWorld()->addParticle($pos->add(0.5, 1, 0.5), $floatingText);
    }

    private function noHasLevel(Block $block): void
    {
        $name = strtolower(str_replace([" ", "Block"], ["", ""], $block->getName()));
        $level = Main::getInstance()->getFarmLevels()[$name];
        $floatingText = new FloatingText("§cNível necessário {$level}", "§4Sem Nível", 12);
        $this->getWorld()->addParticle($block->getPosition()->add(0.5, 1, 0.5), $floatingText);
    }

    public function handleItem(Item $getItemInHand): bool
    {
        $name = strtolower(str_replace([" ", "Block"], ["", ""], $getItemInHand->getName()));
        if (isset(Main::getInstance()->getFarmPrices()[$name])) {
            $level = Main::getInstance()->getFarmLevels()[$name];
            if ($this->getLevel() < $level) {
                $this->sendPopup("§cNível necessário {$level}");
                return false;
            }
        }

        return true;
    }

    public function addMission(Mission $mission): void
    {
        $this->missions[] = $mission;
        $this->needsSave = true;
    }

    public function removeMission(Mission $mission): void
    {
        $key = array_search($mission, $this->missions);
        if ($key !== false) {
            unset($this->missions[$key]);
            $this->needsSave = true;
        }
    }

    public function hasMission(Mission $mission): bool
    {
        $missions = $this->getMissions();
        foreach ($missions as $m) {
            if ($m->getId() === $mission->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getFarmWorld(): ?World
    {
        return $this->farmWorld;
    }

    public function getGrow(): int
    {
        return $this->grow;
    }

    public function addGrow(int $grow): void
    {
        $this->grow += $grow;
    }

    public function setGrow(int $grow): void
    {
        $this->grow = $grow;
    }

    public function upgradeChunk(): void
    {
        $currentChunks = $this->getChunks();
        $currentCount = count($currentChunks);

        $newTotal = $currentCount * 2 + 3;

        $chunksToAdd = $newTotal - $currentCount;

        $adjacentChunks = [];
        foreach ($currentChunks as $chunkCoord) {
            // Supondo que o formato seja "x:z"
            list($x, $z) = explode(":", $chunkCoord);
            $x = (int)$x;
            $z = (int)$z;

            $neighbors = [
                ($x - 1) . ":" . $z, // Oeste
                ($x + 1) . ":" . $z, // Leste
                $x . ":" . ($z - 1), // Norte
                $x . ":" . ($z + 1), // Sul
            ];

            foreach ($neighbors as $neighbor) {
                if (!in_array($neighbor, $currentChunks) && !in_array($neighbor, $adjacentChunks)) {
                    $adjacentChunks[] = $neighbor;
                }
            }
        }

        $chunksToActuallyAdd = array_slice($adjacentChunks, 0, $chunksToAdd);

        foreach ($chunksToActuallyAdd as $chunkCoord) {
            $this->addChunk($chunkCoord);
        }

        $this->setFarmSize($newTotal);
        $this->sendPopup("§aSeu Farm foi expandido para {$newTotal} chunks!");
    }

}