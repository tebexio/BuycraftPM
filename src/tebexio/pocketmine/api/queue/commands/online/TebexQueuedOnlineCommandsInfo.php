<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\queue\commands\online;

use tebexio\pocketmine\api\TebexResponse;

final class TebexQueuedOnlineCommandsInfo implements TebexResponse{

	/** @var TebexQueuedOnlineCommand[] */
	private $commands;

	/**
	 * @param TebexQueuedOnlineCommand[] $commands
	 */
	public function __construct(array $commands){
		$this->commands = $commands;
	}

	/**
	 * @return TebexQueuedOnlineCommand[]
	 */
	public function getCommands() : array{
		return $this->commands;
	}
}