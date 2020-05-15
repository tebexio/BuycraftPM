<?php

declare(strict_types=1);

namespace tebexio\pocketmine;

use tebexio\pocketmine\api\bans\TebexBanListRequest;
use tebexio\pocketmine\api\bans\TebexBanRequest;
use tebexio\pocketmine\api\checkout\TebexCheckoutRequest;
use tebexio\pocketmine\api\information\TebexInformationRequest;
use tebexio\pocketmine\api\listing\TebexListingRequest;
use tebexio\pocketmine\api\package\TebexPackageRequest;
use tebexio\pocketmine\api\package\TebexPackagesRequest;
use tebexio\pocketmine\api\queue\TebexDuePlayersListRequest;
use tebexio\pocketmine\api\queue\commands\TebexDeleteCommandRequest;
use tebexio\pocketmine\api\queue\commands\offline\TebexQueuedOfflineCommandsListRequest;
use tebexio\pocketmine\api\queue\commands\online\TebexQueuedOnlineCommandsListRequest;
use tebexio\pocketmine\api\sales\TebexSalesRequest;
use tebexio\pocketmine\api\user\TebexUserLookupRequest;
use tebexio\pocketmine\thread\response\TebexResponseHandler;

final class TebexAPI extends BaseTebexAPI{

	public function getInformation(TebexResponseHandler $callback) : void{
		$this->request(new TebexInformationRequest(), $callback);
	}

	public function getSales(TebexResponseHandler $callback) : void{
		$this->request(new TebexSalesRequest(), $callback);
	}

	public function getBanList(TebexResponseHandler $callback) : void{
		$this->request(new TebexBanListRequest(), $callback);
	}

	public function getQueuedOfflineCommands(TebexResponseHandler $callback) : void{
		$this->request(new TebexQueuedOfflineCommandsListRequest(), $callback);
	}

	public function getDuePlayersList(TebexResponseHandler $callback) : void{
		$this->request(new TebexDuePlayersListRequest(), $callback);
	}

	public function getQueuedOnlineCommands(int $player_id, TebexResponseHandler $callback) : void{
		$this->request(new TebexQueuedOnlineCommandsListRequest($player_id), $callback);
	}

	public function getListing(TebexResponseHandler $callback) : void{
		$this->request(new TebexListingRequest(), $callback);
	}

	public function getPackage(int $package_id, TebexResponseHandler $callback) : void{
		$this->request(new TebexPackageRequest($package_id), $callback);
	}

	public function getPackages(TebexResponseHandler $callback) : void{
		$this->request(new TebexPackagesRequest(), $callback);
	}

	/**
	 * @param int[] $command_ids
	 * @param TebexResponseHandler|null $callback
	 */
	public function deleteCommands(array $command_ids, ?TebexResponseHandler $callback = null) : void{
		$this->request(new TebexDeleteCommandRequest($command_ids), $callback ?? TebexResponseHandler::unhandled());
	}

	public function lookup(string $username_or_uuid, TebexResponseHandler $callback) : void{
		$this->request(new TebexUserLookupRequest($username_or_uuid), $callback);
	}

	public function ban(string $username_or_uuid, ?string $reason = null, ?string $ip = null, ?TebexResponseHandler $callback = null) : void{ // TODO: test this
		$this->request(new TebexBanRequest($username_or_uuid, $reason, $ip), $callback ?? TebexResponseHandler::unhandled());
	}

	public function checkout(int $package_id, string $username, TebexResponseHandler $callback) : void{
		$this->request(new TebexCheckoutRequest($package_id, $username), $callback);
	}
}