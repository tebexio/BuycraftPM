<?php

declare(strict_types=1);

namespace tebexio\pocketmine;

use tebexio\pocketmine\api\TebexRequest;
use tebexio\pocketmine\thread\TebexThread;
use tebexio\pocketmine\thread\TebexThreadPool;
use tebexio\pocketmine\thread\response\TebexResponseHandler;
use tebexio\pocketmine\thread\ssl\SSLConfiguration;

abstract class BaseTebexAPI{

	public const BASE_ENDPOINT = "https://plugin.tebex.io";

	/** @var TebexThreadPool */
	private $pool;

	/** @var SSLConfiguration */
	private $ssl_config;

	public function __construct(TebexPlugin $plugin, string $secret, SSLConfiguration $ssl_config, int $workers){
		$this->pool = new TebexThreadPool($plugin);
		$this->ssl_config = $ssl_config;
		for($i = 0; $i < $workers; $i++){
			$this->pool->addWorker(new TebexThread($secret, $ssl_config));
		}
		$this->pool->start();
	}

	public function request(TebexRequest $request, TebexResponseHandler $callback) : void{
		$this->pool->getLeastBusyWorker()->push($request, $callback);
	}

	public function getLatency() : float{
		return $this->pool->getLatency();
	}

	public function waitAll(int $sleep_duration_ms = 50000) : void{
		$this->pool->waitAll($sleep_duration_ms);
	}

	public function shutdown() : void{
		$this->pool->shutdown();
		$this->ssl_config->close();
	}
}