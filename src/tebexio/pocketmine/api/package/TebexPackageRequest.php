<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\package;

use tebexio\pocketmine\api\TebexGETRequest;
use tebexio\pocketmine\api\TebexResponse;
use tebexio\pocketmine\api\RespondingTebexRequest;

final class TebexPackageRequest extends TebexGETRequest implements RespondingTebexRequest{

	/** @var int */
	private $package_id;

	public function __construct(int $package_id){
		$this->package_id = $package_id;
	}

	public function getEndpoint() : string{
		return "/packages/" . $this->package_id;
	}

	public function getExpectedResponseCode() : int{
		return 200;
	}

	public function createResponse(array $response) : TebexResponse{
		return TebexPackage::fromTebexResponse($response);
	}
}