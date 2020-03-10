<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler\command;

use pocketmine\command\ConsoleCommandSender;

final class TebexCommandSender extends ConsoleCommandSender{

	public static function instance() : self{
		static $instance = null;
		return $instance ?? $instance = new self();
	}

	private function __construct(){
		parent::__construct();
	}

	public function getName() : string{
		return "TEBEX";
	}
}