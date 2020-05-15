<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\queue;

use tebexio\pocketmine\api\TebexResponse;

final class TebexDuePlayersInfo implements TebexResponse{

	/** @var TebexDuePlayersMeta */
	private $meta;

	/** @var TebexDuePlayer[] */
	private $players;

	/**
	 * @param TebexDuePlayersMeta $meta
	 * @param TebexDuePlayer[] $players
	 */
	public function __construct(TebexDuePlayersMeta $meta, array $players){
		$this->meta = $meta;
		$this->players = $players;
	}

	public function getMeta() : TebexDuePlayersMeta{
		return $this->meta;
	}

	/**
	 * @return TebexDuePlayer[]
	 */
	public function getPlayers() : array{
		return $this->players;
	}
}