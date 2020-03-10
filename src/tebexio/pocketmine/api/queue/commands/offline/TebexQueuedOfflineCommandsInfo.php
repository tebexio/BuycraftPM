<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\queue\commands\offline;

use tebexio\pocketmine\api\TebexResponse;

final class TebexQueuedOfflineCommandsInfo implements TebexResponse{

	/** @var TebexQueuedOfflineCommandsMeta */
	private $meta;

	/** @var TebexQueuedOfflineCommand[] */
	private $commands;

	/**
	 * @param TebexQueuedOfflineCommandsMeta $meta
	 * @param TebexQueuedOfflineCommand[] $commands
	 */
	public function __construct(TebexQueuedOfflineCommandsMeta $meta, array $commands){
		$this->meta = $meta;
		$this->commands = $commands;
	}

	public function getMeta() : TebexQueuedOfflineCommandsMeta{
		return $this->meta;
	}

	/**
	 * @return TebexQueuedOfflineCommand[]
	 */
	public function getCommands() : array{
		return $this->commands;
	}
}