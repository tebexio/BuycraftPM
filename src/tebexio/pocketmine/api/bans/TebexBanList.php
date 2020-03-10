<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\bans;

use tebexio\pocketmine\api\TebexResponse;

final class TebexBanList implements TebexResponse{

	/** @var TebexBanEntry[] */
	private $entries;

	/**
	 * @param TebexBanEntry[] $entries
	 */
	public function __construct(array $entries){
		$this->entries = $entries;
	}

	/**
	 * @return TebexBanEntry[]
	 */
	public function getAll() : array{
		return $this->entries;
	}
}