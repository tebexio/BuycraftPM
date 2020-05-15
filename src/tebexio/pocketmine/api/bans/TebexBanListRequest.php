<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\bans;

use tebexio\pocketmine\api\TebexGETRequest;
use tebexio\pocketmine\api\TebexResponse;
use tebexio\pocketmine\api\RespondingTebexRequest;

final class TebexBanListRequest extends TebexGETRequest implements RespondingTebexRequest{

	public function getEndpoint() : string{
		return "/bans";
	}

	public function getExpectedResponseCode() : int{
		return 200;
	}

	public function createResponse(array $response) : TebexResponse{
		$entries = [];
		foreach($response["data"] as $entry){
			$entries[] = TebexBanEntry::fromTebexResponse($entry);
		}
		return new TebexBanList($entries);
	}
}