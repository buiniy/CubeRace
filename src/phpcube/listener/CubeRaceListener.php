<?php

namespace phpcube\listener;

use phpcube\CubeRace;
use pocketmine\entity\Attribute;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;

class CubeRaceListener implements Listener
{

    public function __construct()
    {

    }

    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        foreach ($event->getPackets() as $packet) {
            if (!($packet instanceof ModalFormRequestPacket)) {
                continue;
            }
            foreach ($event->getTargets() as $target) {
                $player = $target->getPlayer();
                if ($player === null || !$player->isConnected()) {
                    continue;
                }
                CubeRace::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $target): void {
                    $times = 5; // send for up to 5 x 10 ticks (or 2500ms)
                    CubeRace::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function () use ($player, $target, &$times): void {
                        --$times >= 0 || throw new CancelTaskException("Maximum retries exceeded");
                        $target->isConnected() || throw new CancelTaskException("Maximum retries exceeded");
                        $target->getEntityEventBroadcaster()->syncAttributes([$target], $player, [
                            $player->getAttributeMap()->get(Attribute::EXPERIENCE_LEVEL)
                        ]);
                    }), 10);
                }), 1);
            }
        }
    }
}