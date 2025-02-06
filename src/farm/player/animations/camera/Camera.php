<?php

namespace farm\player\animations\camera;

use CameraAPI\Instructions\ClearCameraInstruction;
use CameraAPI\Instructions\FadeCameraInstruction;
use CameraAPI\Instructions\SetCameraInstruction;
use farm\Main;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

trait Camera
{
    /**
     * Inicia a sequência de câmera com rotação de 360° ao redor da ilha.
     *
     * @param Player  $player        Jogador que receberá a animação de câmera.
     * @param Vector3 $islandCenter  Centro da ilha.
     */
    public function startIntro(Player $player, Vector3 $islandCenter): void {
        $radius = 10;             // Distância da câmera em relação ao centro da ilha.
        $cameraHeight = 75;       // Altura da câmera.
        $durationSeconds = 5;     // Duração da animação (segundos).
        $ticksTotal = $durationSeconds * 20; // Convertendo para ticks.
        $tickCount = 0;

        $task = new ClosureTask(function () use (&$tickCount, $ticksTotal, $player, $islandCenter, $radius, $cameraHeight, &$task): void {
            if ($tickCount >= $ticksTotal) {
                // Finaliza a animação e retorna o controle ao jogador.
                $fadeCameraInstruction = new FadeCameraInstruction();
                $fadeCameraInstruction->setTime(0.2, 2, 1);
                $fadeCameraInstruction->setColor(0, 0, 0);
                $fadeCameraInstruction->send($player);
                $clearCameraInstruction = new ClearCameraInstruction();
                $clearCameraInstruction->setClear(true);
                $clearCameraInstruction->setRemoveTarget(true);
                $clearCameraInstruction->send($player);
                $task->getHandler()->cancel();
                return;
            }

            // Calcula a fração concluída e o ângulo atual (0 a 2π).
            $progress = $tickCount / $ticksTotal;
            $angle = $progress * 2 * M_PI;

            // Calcula a nova posição da câmera ao redor da ilha.
            $x = $islandCenter->getX() + $radius * cos($angle);
            $z = $islandCenter->getZ() + $radius * sin($angle);
            $newPos = new Vector3($x, $cameraHeight, $z);

            // Define a câmera na nova posição.
            $instruction = new SetCameraInstruction();
            $instruction->setCameraPosition($newPos);
            $instruction->setFacingPosition($islandCenter);
            $instruction->setEase(1, 0.1); // Suavização para evitar movimentação brusca.
            $instruction->setRotation(0, 5); // Rotação da câmera.
            $instruction->send($player);

            $tickCount++;
        });

        // Agenda a atualização da câmera a cada tick.
        Main::getInstance()->getScheduler()->scheduleRepeatingTask($task, 1);
    }
}