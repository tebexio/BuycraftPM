<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\sales;

use tebexio\pocketmine\api\TebexResponse;

final class TebexSalesList implements TebexResponse{

	/** @var TebexSale[] */
	private $sales;

	/**
	 * @param TebexSale[] $sales
	 */
	public function __construct(array $sales){
		$this->sales = $sales;
	}

	/**
	 * @return TebexSale[]
	 */
	public function getAll() : array{
		return $this->sales;
	}
}