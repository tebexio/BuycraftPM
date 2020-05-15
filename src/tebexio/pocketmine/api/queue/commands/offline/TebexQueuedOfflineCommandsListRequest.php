<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\queue\commands\offline;

use tebexio\pocketmine\api\TebexGETRequest;
use tebexio\pocketmine\api\TebexResponse;
use tebexio\pocketmine\api\queue\TebexDuePlayer;
use tebexio\pocketmine\api\RespondingTebexRequest;

final class TebexQueuedOfflineCommandsListRequest extends TebexGETRequest implements RespondingTebexRequest{

	public function getEndpoint() : string{
		return "/queue/offline-commands";
	}

	public function getExpectedResponseCode() : int{
		return 200;
	}

	public function createResponse(array $response) : TebexResponse{
		$commands = [];
		foreach($response["commands"] as $cmd){
			$cmd["player"]["id"] = (int) $cmd["player"]["id"];
			$commands[] = new TebexQueuedOfflineCommand(
				$cmd["id"],
				$cmd["command"],
				$cmd["payment"],
				$cmd["package"],
				new TebexQueuedOfflineCommandConditions($cmd["conditions"]["delay"]),
				TebexDuePlayer::fromTebexResponse($cmd["player"])
			);
		}

		return new TebexQueuedOfflineCommandsInfo(
			new TebexQueuedOfflineCommandsMeta($response["meta"]["limited"]),
			$commands
		);
	}
}