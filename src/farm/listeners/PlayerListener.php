<?php

namespace farm\listeners;

use farm\events\player\PlayerAddExpEvent;
use farm\events\player\PlayerLevelUp;
use farm\Main;
use farm\player\FarmingPlayer;
use pocketmine\block\Sapling;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\math\Vector3;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\sound\XpLevelUpSound;
use ReflectionClass;
use ReflectionException;

class PlayerListener implements Listener
{
    public function onCreatePlayer(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(FarmingPlayer::class);
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $player->sendMessage(Main::$prefix);
        $info = Main::getInstance()->getPlayerManager()->getPlayerInfos($player);

        if($info['first_join'] === $info['last_join']){
            $text = [
                "",
                "§l§aSeja bem-vindo ao Farm Simulator!",
                "",
                "§l§g|§r §aPara começar, você precisa de uma fazenda.",
                "§l§g|§r §aUse o comando /farm island tp para ir até a sua fazenda.",
                "§l§g|§r §aBoa sorte!",
                "",
                "",
                "§l§e|§r §eDicas:",
                "§l§a|§r §e- Use o comando /farm shop para comprar itens.",
                "§l§a|§r §e- Use o comando /farm missions para ver as missões disponíveis.",
                "§l§a|§r §e- Use o comando /farm rank para ver o ranking dos jogadores.",
                "§l§a|§r §e- Use o comando /farm help para ver todos os comandos disponíveis."
            ];

            $player->sendMessage(implode("\n", $text));
        }

        if($info['vip'] && $info['vip_expires'] < time()){
            $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §cSeu VIP expirou!");
            $info['vip'] = false;
            $info['vip_expires'] = 0;
            Main::getInstance()->getPlayerManager()->setPlayerInfos($player, $info);
        }
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $info = Main::getInstance()->getPlayerManager()->getPlayerInfos($player);
        $info['last_join'] = time();
        Main::getInstance()->getPlayerManager()->setPlayerInfos($player, $info);
    }

    public function onUseItem(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if($player instanceof FarmingPlayer) {
           if(!$player->handleItem($item)){
               $event->cancel();
           }
        }
    }

    public function onHitVoid(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        if($entity instanceof FarmingPlayer){
            if($event->getCause() === EntityDamageEvent::CAUSE_VOID){
                $entity->teleport($entity->getFarmWorld()->getSpawnLocation());
                $entity->sendPopup("§l§4Cuidado! \n §r§cVocê caiu no vazio!");
                $event->cancel();
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    public function onShift(PlayerToggleSneakEvent $event): void {
        $player = $event->getPlayer();
        if ($player instanceof FarmingPlayer) {
            $block = $player->getWorld()->getBlock($player->getPosition());
            if ($block instanceof Sapling) {
                $grow = $player->getGrow();
                $player->addGrow($grow + 1);

                for ($i = 0; $i < 10; $i++) {
                    $pos = $player->getPosition();
                    $pos->x += (mt_rand(0,1) ? 0.1 : -0.1);
                    $pos->y += (mt_rand(0,1) ? 1 : 1.5);
                    $pos->z += (mt_rand(0,1) ? 0.1 : -0.1);
                    $player->getWorld()->addParticle(new Vector3($pos->x, $pos->y, $pos->z), new HappyVillagerParticle());
                }

                if($grow >= 15){
                    $player->setGrow(0);
                    $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aVocê acelerou o crescimento da árvore!");
                    $reflection = new ReflectionClass($block);

                    $ready = $reflection->getProperty("ready");
                    $ready->setAccessible(true);
                    $ready->setValue($block, true);

                    $growMethod = $reflection->getMethod("grow");
                    $growMethod->setAccessible(true);
                    $growMethod->invoke($block,null);
                }
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlockAgainst();
        $pos = $block->getPosition();
        if($player instanceof FarmingPlayer) {
            if(!$player->inChunk(new Vector3($pos->x, $pos->y, $pos->z))){
                $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §cVocê não pode construir aqui!");
                $event->cancel();
            }

            if(!$player->handleItem($item)){
                $event->cancel();
            }
        }
    }

    public function onLevelUp(PlayerLevelUp $event): void
    {
        $player = $event->getPlayer();
        $level = $event->getLevel();
        $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aParabéns! Você subiu para o nível {$level}!");

        $player->getWorld()->addSound($player->getPosition(), new XpLevelUpSound(30));

        if($level % 5 === 0){
            if($player instanceof FarmingPlayer){
                $player->upgradeChunk();
            }
        }
    }

    public function onExp(PlayerAddExpEvent $event): void
    {
        $player = $event->getPlayer();
        $exp = $event->getExp();
        $player->sendMessage("§l§e[§r§aFarm§eSimulator§l§e]§r §aVocê ganhou {$exp} de experiência!");
        $player->getWorld()->addSound($player->getPosition(), new NoteSound(NoteInstrument::PLING(), 1));
    }
}