<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\queue\commands;

abstract class TebexQueuedCommandConditions{

	/** @var int */
	private $delay;

	public function __construct(int $delay){
		$this->delay = $delay;
	}

	final public function getDelay() : int{
		return $this->delay;
	}
}