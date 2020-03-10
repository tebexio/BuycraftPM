<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\queue\commands\online;

use tebexio\pocketmine\api\TebexGETRequest;
use tebexio\pocketmine\api\TebexResponse;
use tebexio\pocketmine\api\RespondingTebexRequest;

final class TebexQueuedOnlineCommandsListRequest extends TebexGETRequest implements RespondingTebexRequest{

	/** @var int */
	private $player_id;

	public function __construct(int $player_id){
		$this->player_id = $player_id;
	}

	public function getEndpoint() : string{
		return "/queue/online-commands/" . $this->player_id;
	}

	public function getExpectedResponseCode() : int{
		return 200;
	}

	public function createResponse(array $response) : TebexResponse{
		$commands = [];
		foreach($response["commands"] as $cmd){
			$commands[] = new TebexQueuedOnlineCommand(
				$cmd["id"],
				$cmd["command"],
				$cmd["payment"],
				$cmd["package"],
				new TebexQueuedOnlineCommandConditions($cmd["conditions"]["delay"], $cmd["conditions"]["slots"])
			);
		}

		return new TebexQueuedOnlineCommandsInfo($commands);
	}
}