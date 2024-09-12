<?php

declare(strict_types=1);

namespace  phpcube\invmenu\muqsit\invmenu\type;

use  phpcube\invmenu\muqsit\invmenu\InvMenu;
use  phpcube\invmenu\muqsit\invmenu\type\graphic\InvMenuGraphic;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

interface InvMenuType{

	public function createGraphic(InvMenu $menu, Player $player) : ?InvMenuGraphic;

	public function createInventory() : Inventory;
}