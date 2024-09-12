<?php

namespace phpcube\task;

use phpcube\CubeRace;
use phpcube\economy\EconomyProvider;
use phpcube\invmenu\muqsit\invmenu\InvMenu;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class RaceTask extends Task
{
    /**
     * @var CubeRace
     */
    public CubeRace $plugin;
    /**
     * @var InvMenu
     */
    public InvMenu $menu;
    /**
     * @var Player|null
     */
    public Player|null $player;
    /**
     * @var bool
     */
    public bool $raceFinished = false;
    /**
     * @var array
     */
    public array $horsePositions = [];
    /**
     * @var array
     */
    public array $horses = [];

    /**
     * @var int
     */
    public int $betAmount = 0;
    /**
     * @var int
     */
    public int $selectedHorse = 0;


    public function __construct(CubeRace $plugin, InvMenu $menu, Player|null $player, int $betAmount, int $selectedHorse) {

        if($player === null || !$player->isOnline()) {
            $this->getHandler()->cancel();
        }

        $this->plugin = $plugin;
        $this->menu = $menu;
        $this->player = $player;
        $this->betAmount = $betAmount;
        $this->selectedHorse = $selectedHorse;

        foreach ($this->plugin->startIndexes as $index => $start) {
            $this->horsePositions[$index] = $start;
            $this->horses[$index] = CubeRace::getInstance()->horses[$index];
            $this->menu->getInventory()->setItem($start, $this->horses[$index]);
        }

    }

    public function onRun(): void
    {
        if(!$this->player instanceof Player || !$this->player->isOnline()) {
            $this->getHandler()->cancel();
        }

        $inventory = $this->menu->getInventory();
        $newPositions = [];

        foreach ($this->plugin->startIndexes as $index => $start) {
            $finish = $this->plugin->finishIndexes[$index];
            $currentPosition = $this->horsePositions[$index];
            if ($currentPosition >= $finish) {
                $this->player->sendMessage(CubeRace::$prefix . " " . $this->horses[$index]->getCustomName() . " §r§a§lвыиграла!");

                $this->raceFinished = true;

                if ($this->selectedHorse === $index) {
                    EconomyProvider::add($this->player, intval($this->betAmount * 2));
                    $this->player->sendMessage(CubeRace::$prefix . " Вы выиграли §a" . intval($this->betAmount * 2) . " монет!");

                }

                foreach ($this->plugin->startIndexes as $loserIndex => $loserStart) {
                    $loserFinish = $this->plugin->finishIndexes[$loserIndex];
                    $loserCurrentPosition = $this->horsePositions[$loserIndex];
                    if ($loserCurrentPosition < $loserFinish) {
                        $inventory->setItem($loserCurrentPosition,
                            VanillaBlocks::BARRIER()->asItem()->setCustomName(CubeRace::getInstance()->getHorseName(0))
                        );
                    }
                }

                $player = $this->player;

                CubeRace::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
                    if($player instanceof Player) {
                        $player->removeCurrentWindow();
                    }
                }), 50
                );

                $this->getHandler()->cancel();
                return;
            }

            $direction = rand(0, 2);
            // Двигаемся вправо
            if ($direction == 0 || $direction == 2) {
                $nextPosition = $currentPosition + 1;
                if ($nextPosition > $finish) {
                    $nextPosition = $currentPosition;
                }
                // Двигаемся влево
            } else {
                $nextPosition = $currentPosition - 1;
                if ($nextPosition < $start) {
                    $nextPosition = $currentPosition;
                }
            }

            $minSlot = CubeRace::getInstance()->slotLimits[$index][0];
            $maxSlot = CubeRace::getInstance()->slotLimits[$index][1];

            if ($nextPosition < $minSlot || $nextPosition > $maxSlot) {
                $nextPosition = $currentPosition;
            }

            $newPositions[$index] = $nextPosition;
        }

        foreach ($this->plugin->startIndexes as $index => $start) {

            $currentPosition = $this->horsePositions[$index];
            $nextPosition = $newPositions[$index];

            $inventory->setItem($nextPosition, $this->horses[$index]);

            if ($currentPosition !== $nextPosition) {
                $inventory->setItem($currentPosition, VanillaItems::AIR());
            }

            $this->horsePositions[$index] = $nextPosition;
        }
    }
}