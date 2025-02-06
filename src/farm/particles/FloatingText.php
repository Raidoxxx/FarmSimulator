<?php

namespace farm\particles;

use farm\Main;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\particle\FloatingTextParticle;

class FloatingText extends FloatingTextParticle
{
    /**
     * @var ?int $timeToLive Quantidade de ticks até o texto sumir. null = infinito.
     */

    protected ?int $entityId = null;
    public function __construct(
        protected string $text,
        protected string $title = "",
        protected ?int $timeToLive = 5 // null = infinito
    ){
        // Chama o construtor da classe pai (PMMPFloatingTextParticle)
        parent::__construct($this->text, $this->title);

    }

    public function getEntityId() : ?int {
        return $this->entityId;
    }

    /**
     * Sobrescreve o encode para recriar o AddActorPacket e RemoveActorPacket,
     * mas mantendo a lógica da classe-pai.
     */
    public function encode(Vector3 $pos) : array {
        $packets = [];

        if($this->entityId === null){
            $this->entityId = Entity::nextRuntimeId();
        } else {
            $packets[] = RemoveActorPacket::create($this->entityId);
        }

        if(!$this->invisible){
            $name = $this->title . ($this->text !== "" ? "\n" . $this->text : "");

            $actorFlags = (1 << EntityMetadataFlags::NO_AI);
            $actorMetadata = [
                EntityMetadataProperties::FLAGS => new LongMetadataProperty($actorFlags),
                EntityMetadataProperties::SCALE => new FloatMetadataProperty(0.01), // zero pode causar bugs
                EntityMetadataProperties::BOUNDING_BOX_WIDTH => new FloatMetadataProperty(0.0),
                EntityMetadataProperties::BOUNDING_BOX_HEIGHT => new FloatMetadataProperty(0.0),
                EntityMetadataProperties::NAMETAG => new StringMetadataProperty($name),
                EntityMetadataProperties::VARIANT => new \pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty(
                    TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(
                        VanillaBlocks::AIR()->getStateId()
                    )
                ),
                EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => new \pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty(1),
            ];

            $packets[] = AddActorPacket::create(
                $this->entityId,
                $this->entityId,  // actor unique ID (usando o mesmo por simplicidade)
                EntityIds::FALLING_BLOCK,
                $pos, // posição de spawn
                null,
                0.0,
                0.0,
                0.0,
                0.0,
                [],
                $actorMetadata,
                new PropertySyncData([], []),
                []
            );
            $this->scheduleDespawn($pos);
        }

        return $packets;
    }

    /**
     * Agenda a remoção automática do texto após $this->timeToLive ticks.
     * Se $timeToLive for null ou <= 0, não faz nada.
     */
    public function scheduleDespawn(Vector3 $pos) : void {
        if($this->timeToLive !== null && $this->timeToLive > 0){
            Main::getInstance()->getScheduler()->scheduleDelayedTask(
                new class($this->entityId, $pos) extends Task {
                    public function __construct(
                        protected int $entityId,
                        protected Vector3 $pos
                    ){}
                    public function onRun() : void {
                        $pk = RemoveActorPacket::create($this->entityId);
                        foreach(Server::getInstance()->getOnlinePlayers() as $player){
                            $player->getNetworkSession()->sendDataPacket($pk);
                        }
                    }
                },
                $this->timeToLive // ticks de delay
            );
        }
    }
}