<?php
declare(strict_types=1);

namespace phpcube;

use phpcube\invmenu\muqsit\invmenu\InvMenuHandler;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use phpcube\command\RaceCommand;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class CubeRace extends PluginBase {

    use SingletonTrait;

    public Config $config;

    public const COMMAND_PERMISSION = "cmd.race";

    public static string $prefix = "";

    public array $startIndexes = [];
    public array $finishIndexes = [];
    public array $upIndexes = [];
    public array $downIndexes = [];
    public array $slotLimits = [
        [9, 17],
        [18, 26],
        [27, 35],
        [36, 44]
    ];

    public array $commandData = [];

    public array $horses = [];

    public static array $taskCache = [];

    public function onEnable() : void {
        self::setInstance($this);

        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }

        $this->horses = [
            VanillaItems::LEATHER_TUNIC()->setCustomName($this->getHorseName(1)),
            VanillaItems::IRON_CHESTPLATE()->setCustomName($this->getHorseName(2)),
            VanillaItems::GOLDEN_CHESTPLATE()->setCustomName($this->getHorseName(3)),
            VanillaItems::DIAMOND_CHESTPLATE()->setCustomName($this->getHorseName(4))
        ];

        self::$prefix = $this->config->get("prefix");

        $this->startIndexes = $this->config->getNested("raceSettings.startIndexes", []);
        $this->finishIndexes = $this->config->getNested("raceSettings.finishIndexes", []);
        $this->upIndexes = $this->config->getNested("raceSettings.upIndexes", []);
        $this->downIndexes = $this->config->getNested("raceSettings.downIndexes", []);

        $this->commandData = [
            "permission" => self::COMMAND_PERMISSION,
            "name" => $this->config->get("commandName"),
            "description" => $this->config->get("commandDescription"),
            "aliases" => $this->config->get("commandAliases")
        ];

        $this->getServer()->getCommandMap()->register("race", new RaceCommand);
    }


    /**
     * @param int $index
     * @return string
     */
    public function getHorseName(int $index) : string {
        return "§r" . $this->config->getNested("horsesName.horse{$index}", "");
    }

    /**
     * @param Player $player
     * @return void
     */
    public function cancelTask(Player $player): void {
        if(isset(self::$taskCache[strtolower($player->getName())])) {
            $task = self::$taskCache[strtolower($player->getName())];
            if($task !== null) {
                if(!$task->isCancelled()) {
                    $task->cancel();
                }
            }
        }
    }
}