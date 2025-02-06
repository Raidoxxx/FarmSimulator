<?php

declare(strict_types=1);

namespace farm\player\populator;

use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\object\OakTree;
use pocketmine\world\generator\populator\Populator;

class Island implements Populator {

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void {

        $center = new Vector3(264, 68, 264);

        for($x = -1; $x <= 1; $x++) {
            for($z = -1; $z <= 1; $z++) {
                for($y = -1; $y <= 1; $y++) {
                    $pos = $center->add($x, $y, $z);

                    if($pos->x >> 4 !== $chunkX || $pos->z >> 4 !== $chunkZ) {
                        continue;
                    }

                    if($pos->y === 69) {
                        $world->setBlockAt($pos->x, $pos->y, $pos->z, VanillaBlocks::GRASS());
                    } else {
                        $world->setBlockAt($pos->x, $pos->y, $pos->z, VanillaBlocks::DIRT());
                    }
                }
            }
        }

        // Extensões dentro do mesmo chunk
        $this->createPlatform($world, $center->add(3, 0, 0)); // Direita
        $this->createPlatform($world, $center->subtract(0, 0, 3)); // Cima

        // Árvore
        $treePos = $center->add(4, 2, 1);
        if($this->isInsideChunk($treePos)) {
            (new OakTree())->getBlockTransaction($world, $treePos->x, $treePos->y, $treePos->z, $random)->apply();
        }

        // Bedrock central
        $world->setBlockAt($center->x, $center->y, $center->z, VanillaBlocks::BEDROCK());
    }

    private function createPlatform(ChunkManager $world, Vector3 $center): void {
        for($x = -1; $x <= 1; $x++) {
            for($z = -1; $z <= 1; $z++) {
                for($y = -1; $y <= 0; $y++) {
                    $pos = $center->add($x, $y, $z);

                    if(!$this->isInsideChunk($pos)) {
                        continue;
                    }

                    $world->setBlockAt($pos->x, $pos->y, $pos->z, VanillaBlocks::DIRT());
                    if($y === 0) {
                        $world->setBlockAt($pos->x, $pos->y + 1, $pos->z, VanillaBlocks::GRASS());
                    }
                }
            }
        }
    }

    private function isInsideChunk(Vector3 $pos): bool {
        return $pos->x >> 4 === 16 && $pos->z >> 4 === 16;
    }
}