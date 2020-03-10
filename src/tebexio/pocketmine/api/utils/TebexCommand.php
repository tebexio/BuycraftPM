<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\utils;

use tebexio\pocketmine\api\queue\TebexDuePlayer;
use pocketmine\Player;

final class TebexCommand{

	/** @var string */
	private $string;

	public function __construct(string $string){
		$this->string = $string;
	}

	public function asRawString() : string{
		return $this->string;
	}

	public function asOnlineFormattedString(Player $player, TebexDuePlayer $due_player) : string{
		$gamertag = "\"" . $player->getName() . "\"";
		return strtr($this->string, [
			"{name}" => $gamertag,
			"{player}" => $gamertag,
			"{username}" => "\"" . $due_player->getName() . "\"",
			"{id}" => $player->getXuid()
		]);
	}

	public function asOfflineFormattedString(TebexDuePlayer $due_player) : string{
		$gamertag = "\"" . $due_player->getName() . "\"";
		return strtr($this->string, [
			"{name}" => $gamertag,
			"{player}" => $gamertag,
			"{username}" => $gamertag,
			"{id}" => $due_player->getUuid()
		]);
	}
}