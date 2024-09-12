<?php

declare(strict_types=1);

namespace  phpcube\invmenu\muqsit\invmenu\session\network\handler;

use Closure;
use  phpcube\invmenu\muqsit\invmenu\session\network\NetworkStackLatencyEntry;

interface PlayerNetworkHandler{

	public function createNetworkStackLatencyEntry(Closure $then, int $protocolId) : NetworkStackLatencyEntry;
}