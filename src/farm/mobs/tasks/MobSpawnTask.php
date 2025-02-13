<?php

namespace farm\mobs\tasks;

use farm\Main;
use farm\mobs\entities\Creeper;
use farm\mobs\entities\Skeleton;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\Zombie;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\World;

class MobSpawnTask extends Task
{
    private const SPAWN_RADIUS = 16;
    private const MAX_ATTEMPTS = 8;
    private const MAX_MOBS_PER_AREA = 3;
    private const LIGHT_THRESHOLD = 7;

    public function onRun(): void {
        foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
            $this->attemptSpawnNear($player);
        }
    }

    private function attemptSpawnNear(Player $player): void {
        $world = $player->getWorld();
        $pos = $player->getPosition();

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            // Gera coordenadas inteiras corretamente
            $x = (int)($pos->x + mt_rand(-self::SPAWN_RADIUS, self::SPAWN_RADIUS));
            $z = (int)($pos->z + mt_rand(-self::SPAWN_RADIUS, self::SPAWN_RADIUS));

            // Encontra o bloco sólido mais alto
            $y = $world->getHighestBlockAt($x, $z);

            // Verifica se a posição de spawn está exposta ao céu
            $spawnY = $y + 1;
            if ($world->getFullLightAt($x, $spawnY, $z) > self::LIGHT_THRESHOLD) continue;

            // Verifica o bloco base
            if(is_null($y)) continue;

            $baseBlock = $world->getBlockAt($x, $y, $z);
            if (!$baseBlock->isSolid() || $baseBlock->isTransparent()) continue;

            // Verifica se a posição de spawn é válida
            $spawnPos = new Position($x, $spawnY, $z, $world);
            if (!$world->getBlockAt($x, $spawnY, $z)->canBeReplaced()) continue;

            // Verifica se há céu aberto acima (evitar cavernas/casas)
            if ($world->getBlockAt($x, $spawnY + 1, $z)->canBeReplaced()) {
                if ($world->getFullLightAt($x, $spawnY, $z) > 0) {
                    if ($world->getRealBlockSkyLightAt($x, $spawnY, $z) < self::LIGHT_THRESHOLD) continue;
                    $time = $world->getTimeOfDay();
                    if ($time < World::TIME_NIGHT || $time > World::TIME_SUNRISE) continue;
                }
            }

            // Contagem de mobs na área
            if ($this->countNearbyMobs($spawnPos, $world) < self::MAX_MOBS_PER_AREA) {
                $this->spawnRandomMob($spawnPos, $world);
                return;
            }
        }
    }

    private function countNearbyMobs(Position $pos, World $world): int {
        $area = new AxisAlignedBB(
            $pos->x - 8,
            $pos->y - 3,
            $pos->z - 8,
            $pos->x + 8,
            $pos->y + 3,
            $pos->z + 8
        );

        $count = 0;
        foreach ($world->getNearbyEntities($area) as $entity) {
            if ($entity instanceof Living && !$entity instanceof Player) {
                $count++;
            }
        }
        return $count;
    }

    private function spawnRandomMob(Position $pos, World $world): void {
        $mobs = [
            Zombie::class,
            Skeleton::class,
            Creeper::class
        ];

        $mobClass = $mobs[array_rand($mobs)];
        $location = new Location(
            $pos->x + 0.5,
            $pos->y,
            $pos->z + 0.5,
            $world,
            lcg_value() * 360,
            0
        );

        $entity = new $mobClass($location);
        $entity->spawnToAll();
    }
}