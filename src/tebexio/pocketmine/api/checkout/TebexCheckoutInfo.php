<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\checkout;

use tebexio\pocketmine\api\TebexResponse;

final class TebexCheckoutInfo implements TebexResponse{

	/** @var string */
	private $url;

	/** @var string */
	private $expires;

	public function __construct(string $url, string $expires){
		$this->url = $url;
		$this->expires = $expires;
	}

	public function getUrl() : string{
		return $this->url;
	}

	public function getExpires() : string{
		return $this->expires;
	}
}