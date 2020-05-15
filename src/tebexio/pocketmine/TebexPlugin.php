<?php

declare(strict_types=1);

namespace tebexio\pocketmine;

use muqsit\invmenu\InvMenuHandler;
use tebexio\pocketmine\api\information\TebexInformation;
use tebexio\pocketmine\handler\command\TebexBuyCommandExecutor;
use tebexio\pocketmine\handler\command\TebexCommandExecutor;
use tebexio\pocketmine\handler\command\UnregisteredTebexCommandExecutor;
use tebexio\pocketmine\handler\TebexHandler;
use tebexio\pocketmine\thread\TebexException;
use tebexio\pocketmine\thread\response\TebexResponseHandler;
use tebexio\pocketmine\thread\ssl\SSLConfiguration;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginBase;

final class TebexPlugin extends PluginBase{

	/** @var TebexInformation */
	private $information;

	/** @var TebexHandler */
	private $handler;

	/** @var TebexAPI */
	private $api;

	/** @var PluginCommand */
	private $command;

	/** @var PluginCommand */
	private $buy_command;

	/** @var int */
	private $worker_limit;

	public function onEnable() : void{
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}

		$command = new PluginCommand("tebex", $this);
		$command->setAliases(["tbx", "bc", "buycraft"]);
		$command->setPermission("tebex.admin");
		$this->getServer()->getCommandMap()->register($this->getName(), $command);
		$this->command = $command;

		$command = new PluginCommand("buy", $this);
		$this->getServer()->getCommandMap()->register($this->getName(), $command);
		$this->buy_command = $command;

		$this->worker_limit = (int) $this->getConfig()->get("worker-limit", 2);

		$secret = (string) $this->getConfig()->get("secret");
		try{
			$this->setSecret($secret);
		}catch(TebexException $e){
			$this->getLogger()->notice(($secret !== "" ? $e->getMessage() . " " : "") . "Please configure your server's secret using: /" . $this->command->getName() . " secret <secret>");
			$this->command->setExecutor(new UnregisteredTebexCommandExecutor($this));
		}
	}

	/**
	 * @param string $secret
	 * @return TebexInformation
	 * @throws TebexException
	 */
	public function setSecret(string $secret) : TebexInformation{
		/** @var TebexInformation|TebexException $result */
		$result = null;

		$api = new TebexAPI($secret, SSLConfiguration::recommended(), $this->worker_limit);
		$api->getInformation(new TebexResponseHandler(
			static function(TebexInformation $information) use(&$result) : void{ $result = $information; },
			static function(TebexException $e) use(&$result) : void{ $result = $e; }
		));
		$api->waitAll();

		if($result instanceof TebexException){
			$api->shutdown();
			throw $result;
		}

		$this->init($api, $result);
		return $this->information;
	}

	private function init(TebexAPI $api, TebexInformation $information) : void{
		if($this->handler !== null){
			$this->handler->shutdown();
		}

		if($this->api !== null){
			$this->api->shutdown();
		}

		$this->api = $api;
		$this->information = $information;
		$this->handler = new TebexHandler($this);

		$this->command->setExecutor(new TebexCommandExecutor($this, $this->handler));
		$this->buy_command->setExecutor(new TebexBuyCommandExecutor($this->handler));

		$account = $this->information->getAccount();
		$server = $this->information->getServer();
		$this->getLogger()->debug("Listening to events of \"" . $server->getName() . "\"[#" . $server->getId() . "] server as \"" . $account->getName() . "\"[#" . $account->getId() . "] (latency: " . round($this->getApi()->getLatency() * 1000) . "ms)");
	}

	public function getApi() : TebexAPI{
		return $this->api;
	}

	public function getInformation() : TebexInformation{
		return $this->information;
	}

	public function onDisable() : void{
		if($this->handler !== null){
			$this->handler->shutdown();
		}

		if($this->api !== null){
			$this->api->shutdown();
		}
	}
}