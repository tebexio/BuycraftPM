<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\listing;

use tebexio\pocketmine\api\TebexResponse;

final class TebexListingInfo implements TebexResponse{

	/** @var TebexCategory[] */
	private $categories;

	/**
	 * @param TebexCategory[] $categories
	 */
	public function __construct(array $categories){
		$this->categories = $categories;
	}

	/**
	 * @return TebexCategory[]
	 */
	public function getCategories() : array{
		return $this->categories;
	}
}