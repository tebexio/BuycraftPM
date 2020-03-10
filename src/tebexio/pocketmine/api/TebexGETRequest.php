<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api;

abstract class TebexGETRequest implements TebexRequest{

	public function addAdditionalCurlOpts(array &$curl_opts) : void{
	}
}