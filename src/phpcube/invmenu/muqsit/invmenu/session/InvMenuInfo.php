<?php

declare(strict_types=1);

namespace  phpcube\invmenu\muqsit\invmenu\session;

use  phpcube\invmenu\muqsit\invmenu\InvMenu;
use  phpcube\invmenu\muqsit\invmenu\type\graphic\InvMenuGraphic;

final class InvMenuInfo{

	public function __construct(
		readonly public InvMenu $menu,
		readonly public InvMenuGraphic $graphic,
		readonly public ?string $graphic_name
	){}
}