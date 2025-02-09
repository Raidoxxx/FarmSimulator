<?php

namespace farm\manager;

use farm\Main;
use farm\manager\missions\Mission;
use farm\player\FarmingPlayer;
use JsonException;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\utils\Config;

class PlayerManager
{
    private static int $ids = 0;
    /** @var Mission[] */
    private array $missions = [];
    public function __construct(
        private readonly Main $plugin
    ) {
        $this->registerMissions();
    }

    /**
     * @throws JsonException
     */
    public function registerMissions(): void
    {
        $this->missions = [
            new Mission(
                false,
                BlockBreakEvent::class,
                ["block_id" => VanillaBlocks::OAK_LOG()->getTypeId()],
                self::nextId(),
                "Quebrar madeira",
                "Quebre 10 blocos de madeira.",
                10,
                100,
                100
            ),

            new Mission(
                false,
                BlockBreakEvent::class,
                ["block_id" => VanillaBlocks::WHEAT()->getTypeId()],
                self::nextId(),
                "Colher trigo",
                "Colha 10 trigos.",
                10,
                100,
                100
            )
        ];

        $this->saveMissions();
    }

    /**
     * @throws JsonException
     */
    public function saveMissions(): void
    {
        foreach ($this->missions as $mission) {
            $data = [
                'mission_id' => $mission->getId(),
                'event' => $mission->getEventClass(),
                'criteria' => json_encode($mission->getCriteria(), JSON_THROW_ON_ERROR),
                'name' => $mission->getName(),
                'description' => $mission->getDescription(),
                'goal' => $mission->getGoal(),
                'reward_money' => $mission->getRewardMoney(),
                'reward_exp' => $mission->getRewardExp(),
                'time_limit' => $mission->getTimeLimit(),
                'per_life' => $mission->isPerLife(),
                'item_rewards' => json_encode($mission->getItemRewards(), JSON_THROW_ON_ERROR),
                'permission_rewards' => json_encode($mission->getPermissionRewards(), JSON_THROW_ON_ERROR),
                'completed' => false,
            ];

            $database = Main::getInstance()->getDatabase();
            if($database->getRepository('missions')->findById($mission->getId()) === null) {
                return;
            }
            $database->getRepository('missions')->save($data);
        }
    }

    public static function nextId(): string
    {
        return (string) ++self::$ids;
    }

    public function getMissions(): array
    {
        return $this->missions;
    }

    public function addMissionToPlayer(FarmingPlayer $player, Mission $mission): void
    {
        $player->addMission($mission);
    }

    public function removeMissionFromPlayer(FarmingPlayer $player, Mission $mission): void
    {
        $player->removeMission($mission);
    }

    public function getPlugin(): Main
    {
        return $this->plugin;
    }

    public function getMission(string $mission_id): ?Mission
    {
        foreach ($this->missions as $mission) {
            if ($mission->getId() === $mission_id) {
                return $mission;
            }
        }
        return null;
    }
}