<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\utils\time;

final class NoneTebexTimeUnit implements TebexTimeUnit{

	public function getName() : string{
		return "None";
	}

	public function toSeconds(int $value) : int{
		return 0;
	}
}