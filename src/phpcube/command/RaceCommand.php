<?php

namespace phpcube\command;

use phpcube\CubeRace;
use phpcube\economy\EconomyProvider;
use phpcube\form\CustomForm;
use phpcube\form\SimpleForm;
use phpcube\invmenu\muqsit\invmenu\InvMenu;
use phpcube\invmenu\muqsit\invmenu\transaction\InvMenuTransaction;
use phpcube\invmenu\muqsit\invmenu\transaction\InvMenuTransactionResult;
use phpcube\invmenu\muqsit\invmenu\type\InvMenuTypeIds;
use phpcube\task\RaceTask;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\player\Player;
use pocketmine\command\CommandSender;
use pocketmine\utils\SingletonTrait;

final class RaceCommand extends Command
{
    use SingletonTrait;

    public function __construct()
    {
        parent::__construct(CubeRace::getInstance()->commandData["name"], CubeRace::getInstance()->commandData["description"]);
        $this->setPermission(CubeRace::getInstance()->commandData["permission"]);
        $this->setAliases(CubeRace::getInstance()->commandData["aliases"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(CubeRace::$prefix . "§cТолько игроку");
            return false;
        }

       $this->openRaceMenu($sender);

        return true;
    }

    private function openRaceMenu(Player $player): void
    {
        $form = new SimpleForm(function(Player $player, int|string|null $data) : void {
            if ($data === null || $data === "exit") {
                return;
            }

            switch ($data) {
                case "bet":
                    $this->openBetForm($player);
                    break;
                case "help":
                    $this->openHelpForm($player);
                    break;
            }
        });

        $form->setTitle(CubeRace::$prefix . "§rМеню гонок");
        $form->addButton("§r§fСделать ставку", 1, "https://minecraft.wiki/images/Block_of_Gold_JE6_BE3.png", "bet");
        $form->addButton("§r§fПомощь", 1, "https://static.wikia.nocookie.net/minecraft_gamepedia/images/f/f2/Paper_JE2_BE2.png", "help");
        $form->addButton("§r§cВыход", 1, "https://mineblocks.com/1/wiki/images/c/cc/Barrier.png", "exit");

        $player->sendForm($form);
    }

    public function openHelpForm(Player $player): void
    {
        $form = new SimpleForm(function(Player $player, int|string|null $data) : void {
            if ($data === null) {
                return;
            }
            if($data === "back") {
                $this->openRaceMenu($player);
            }
        });

        $form->setTitle(CubeRace::$prefix . "§rПомощь");
        $form->setContent(CubeRace::getInstance()->config->get("help-text"));
        $form->addButton("Назад", 1, "https://mineblocks.com/1/wiki/images/c/cc/Barrier.png", "back");

        $player->sendForm($form);
    }

    private function openBetForm(Player $player): void
    {
        $minBet = CubeRace::getInstance()->config->get("min-bet");
        $maxBet = CubeRace::getInstance()->config->get("max-bet");

        $form = new CustomForm(function(Player $player, array $data = null) use ($maxBet, $minBet) {
            if ($data === null) {
                return;
            }

            $betAmount = (int) $data[1];
            $selectedHorseIndex = (int) $data[2];
            $selectedHorse = CubeRace::getInstance()->horses[$selectedHorseIndex];

            if ($betAmount < $minBet || $betAmount > $maxBet) {
                $player->sendMessage(CubeRace::$prefix . " §cСумма ставки должна быть между §f$" . $minBet . " §cи §f$" . $maxBet);
                return;
            }

            if(EconomyProvider::balance($player) < $betAmount){
                $player->sendMessage(CubeRace::$prefix . " §cУ вас недостаточно средств!");
                return;
            }

            EconomyProvider::reduce($player, $betAmount);
            $player->sendMessage(CubeRace::$prefix . " §aВаша ставка в размере §f$" . $betAmount . " §aна лошадь " . $selectedHorse->getCustomName() . " §aбыла принята!");

            $this->startRace($player, $betAmount, $selectedHorseIndex);

        });

        $form->setTitle(CubeRace::$prefix . "§rСделать ставку");
        $form->addLabel("Введите сумму ставки (мин. " . $minBet . ", макс. " . $maxBet . "):");
        $form->addInput("Сумма ставки", "1000", "");
        $form->addDropdown("Выберите лошадь", array_map(function($horse) {
            return $horse->getCustomName();
        }, CubeRace::getInstance()->horses));

        $player->sendForm($form);
    }

    public function raceListener(InvMenuTransaction $transaction, Player $player): InvMenuTransactionResult
    {
        return $transaction->discard();
    }

    public function startRace(Player $player, int $betAmount, int $selectedHorse): void
    {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName(CubeRace::$prefix . " §fГонки лошадей");
        $menu->setListener(function (InvMenuTransaction $transaction) use ($player) {
            return $this->raceListener($transaction, $player);
        });
        $menu->setInventoryCloseListener(function(Player $player) {
            CubeRace::getInstance()->cancelTask($player);
        });
        foreach (CubeRace::getInstance()->upIndexes as $up_key) {
            $menu->getInventory()->setItem($up_key, VanillaBlocks::STAINED_HARDENED_GLASS_PANE()->setColor(DyeColor::MAGENTA)->asItem());
        }

        foreach (CubeRace::getInstance()->horses as $index => $horse) {
            $menu->getInventory()->setItem(CubeRace::getInstance()->startIndexes[$index], $horse);
        }

        foreach (CubeRace::getInstance()->downIndexes as $down_key) {
            $menu->getInventory()->setItem($down_key, VanillaBlocks::STAINED_HARDENED_GLASS_PANE()->setColor(DyeColor::MAGENTA)->asItem());
        }

        $menu->send($player);
        $task = CubeRace::getInstance()->getScheduler()->scheduleRepeatingTask(
            new RaceTask(CubeRace::getInstance(), $menu, $player, $betAmount, $selectedHorse),
            15
        );

        CubeRace::getInstance()->cancelTask($player);
        CubeRace::$taskCache[strtolower($player->getName())] = $task;
    }
}