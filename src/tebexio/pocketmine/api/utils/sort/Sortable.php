<?php

declare(strict_types=1);

namespace tebexio\pocketmine\api\utils\sort;

interface Sortable{

	public function getOrder() : int;
}