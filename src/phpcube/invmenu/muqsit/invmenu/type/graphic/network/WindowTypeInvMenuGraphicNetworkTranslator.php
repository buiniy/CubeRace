<?php

declare(strict_types=1);

namespace  phpcube\invmenu\muqsit\invmenu\type\graphic\network;

use  phpcube\invmenu\muqsit\invmenu\session\InvMenuInfo;
use  phpcube\invmenu\muqsit\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

final class WindowTypeInvMenuGraphicNetworkTranslator implements InvMenuGraphicNetworkTranslator{

	public function __construct(
		readonly private int $window_type
	){}

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void{
		$packet->windowType = $this->window_type;
	}
}