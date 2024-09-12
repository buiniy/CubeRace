<?php

declare(strict_types=1);

namespace  phpcube\invmenu\muqsit\invmenu\type\util\builder;

use  phpcube\invmenu\muqsit\invmenu\type\InvMenuType;

interface InvMenuTypeBuilder{

	public function build() : InvMenuType;
}