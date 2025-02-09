<?php
declare(strict_types=1);

namespace farm\manager\missions;

use farm\events\player\mission\CompleteMission;
use farm\events\player\mission\ProgressMission;
use farm\listeners\ProtectionItem;
use farm\Main;
use farm\player\FarmingPlayer;
use InvalidArgumentException;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

class Mission
{
    private int $progress = 0;
    private ?int $startTime = null;
    private bool $completed = false;

    /**
     * @param string      $id                Identificador único da missão.
     * @param string      $name              Nome da missão.
     * @param string      $description       Descrição da missão.
     * @param int         $goal              Meta de progresso para completar a missão.
     * @param int         $rewardMoney       Recompensa em moedas.
     * @param int         $rewardExp         Recompensa em experiência (XP).
     * @param int|null    $timeLimit         Tempo limite para completar a missão (em segundos). Null para sem limite.
     * @param bool        $perLife           Se verdadeiro, reinicia a missão caso o jogador morra.
     * @param Item[]       $itemRewards       Lista de itens como recompensa. (Ex.: objetos da classe Item)
     * @param array       $permissionRewards Lista de permissões como recompensa.
     *
     * @throws InvalidArgumentException se o timeLimit for definido e não for um inteiro positivo.
     */
    public function __construct(
        int $completed,
        private readonly string $event,
        private readonly array $criteria,
        private readonly string $id,
        private readonly string $name,
        private readonly string $description,
        private readonly int $goal,
        private readonly int $rewardMoney,
        private readonly int $rewardExp,
        private readonly ?int $timeLimit = null,
        private readonly bool $perLife = false,
        private readonly array $itemRewards = [],
        private readonly array $permissionRewards = []
    ) {
        if ($timeLimit !== null && $timeLimit <= 0) {
            throw new InvalidArgumentException("O tempo limite deve ser um inteiro positivo.");
        }
        $this->completed = $completed === 1;
    }

    public function getEvent(string $event): string
    {
        $event = match ($event) {
            "BlockBreakEvent" => BlockBreakEvent::class,
            "PlayerInteractEvent" => PlayerInteractEvent::class,
            "BlockPlaceEvent" => BlockPlaceEvent::class,
            "EntityDamageByEntityEvent" => EntityDamageByEntityEvent::class,

            default => throw new InvalidArgumentException("Evento não encontrado.")
        };
        return $event;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEventClass(): string {
        return $this->event;
    }

    public function getCriteria(): array {
        return $this->criteria;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getGoal(): int
    {
        return $this->goal;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getTimeRemaining(): ?int
    {
        if ($this->timeLimit === null) {
            return null;
        }
        if ($this->startTime === null) {
            return $this->timeLimit;
        }
        $elapsed = time() - $this->startTime;
        return max(0, $this->timeLimit - $elapsed);
    }

    public function getStartTime(): ?int
    {
        return $this->startTime;
    }

    public function getRewardMoney(): int
    {
        return $this->rewardMoney;
    }

    public function getRewardExp(): int
    {
        return $this->rewardExp;
    }

    public function getItemRewards(): array
    {
        return $this->itemRewards;
    }

    public function getPermissionRewards(): array
    {
        return $this->permissionRewards;
    }

    public function getRewards(): array
    {
        return [
            'money'       => $this->rewardMoney,
            'exp'         => $this->rewardExp,
            'items'       => $this->itemRewards,
            'permissions' => $this->permissionRewards
        ];
    }

    public function increaseProgress(int $amount, FarmingPlayer $player): void
    {
        if ($this->completed || $amount <= 0) {
            return;
        }

        if ($this->timeLimit !== null) {
            if ($this->startTime === null) {
                $this->startTime = time();
            } else {
                $elapsed = time() - $this->startTime;
                if ($elapsed > $this->timeLimit) {
                    $text = [
                        Main::$prefix,
                        "",
                        "§l| §r§cMissão expirada: §f{$this->name}",
                        "§l| §r§cTempo limite: §f{$this->timeLimit} segundos",
                        "§l| §r§cProgresso reiniciado.",
                    ];
                    $player->sendMessage(implode("\n", $text));
                    $this->reset();
                    return;
                }
            }
        }

        $this->progress += $amount;
        $ev = new ProgressMission($player, $this, $this->progress);
        $ev->call();

        if ($this->progress >= $this->goal) {
            $this->complete($player);
            $ev = new CompleteMission($player, $this);
            $ev->call();
        }
    }

    private function complete(FarmingPlayer $player): void
    {
        $this->completed = true;
        $player->addMoney($this->rewardMoney);
        $player->addExp($this->rewardExp);

        /**
         * @var Item $item
         */
        foreach ($this->itemRewards as $item) {
            if($player->getInventory()->canAddItem($item)){
                $player->getInventory()->addItem($item);
            }else{
                $item = $player->getWorld()->dropItem($player->getPosition(), $item);
                if($item !== null){
                    $itemNbt = $item->getNamedTag();
                    $itemNbt->setTag(ProtectionItem::KEY,
                        CompoundTag::create()->setString(ProtectionItem::DROP_PROTECTION, $player->getName())
                    );
                }
            }
        }

        foreach ($this->permissionRewards as $permission) {
            $permission = Main::getInstance()->getLoader()->registerPermission($permission, "Permissão de missão");
            $player->addAttachment(Main::getInstance(), $permission->getName(), true);
        }

        $text = [
            Main::$prefix,
            "",
            "§l| §r§aMissão concluída: §f{$this->name}",
            "§l| §r§aRecompensas:",
            "§l| §r§a Moedas: §f+{$this->rewardMoney}",
            "§l| §r§a XP: §f+{$this->rewardExp}",
        ];

        if (!empty($this->itemRewards)) {
            $items = implode(", ", array_map(fn($item) => (string)$item, $this->itemRewards));
            $text[] = "§l| §r§a- Itens: §f{$items}";
        }

        if (!empty($this->permissionRewards)) {
            $text[] = "§l| §r§a- Permissões: §f" . implode(", ", $this->permissionRewards);
        }

        $player->sendMessage(implode("\n", $text));
        $player->saveData();
    }

    public function reset(): void
    {
        $this->progress  = 0;
        $this->completed = false;
        $this->startTime = null;
    }

    public function loadFromData(int $progress, bool $completed, mixed $start_time): void
    {
        $this->progress  = $progress;
        $this->completed = $completed;
        $this->startTime = $start_time;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function isPerLife(): bool
    {
        return $this->perLife;
    }

    public function setProgress(int $progress): void
    {
        $this->progress = $progress;
    }

    public function setCompleted(int $completed): void
    {
        $this->completed = $completed === 1;
    }

    public function setStartTime(mixed $start_time): void
    {
        $this->startTime = $start_time;
    }
}
