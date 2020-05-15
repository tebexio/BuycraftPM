<?php

declare(strict_types=1);

namespace tebexio\pocketmine\handler;

use Closure;
use tebexio\pocketmine\api\listing\TebexListingInfo;
use tebexio\pocketmine\handler\gui\form\TebexFormGUI;
use tebexio\pocketmine\handler\gui\inventory\TebexInventoryGUI;
use tebexio\pocketmine\TebexPlugin;
use tebexio\pocketmine\thread\response\TebexResponseHandler;

final class TebexGUIHandler{

	/** @var TebexPlugin */
	private $plugin;

	/** @var int */
	private $refresh_rate;

	/** @var int */
	private $last_request = 0;

	/** @var string */
	private $gui_class;

	/** @var TebexFormGUI|null */
	private $cached;

	public function __construct(TebexPlugin $plugin, int $refresh_rate = 20 * 60 * 3){
		$this->plugin = $plugin;
		$this->refresh_rate = $refresh_rate;
		switch($type = $plugin->getConfig()->get("categories-gui-type", "form")){
			case "form":
				$this->gui_class = TebexFormGUI::class;
				break;
			case "inventory":
				$this->gui_class = TebexInventoryGUI::class;
				break;
			default:
				throw new \InvalidArgumentException("Invalid config entry for categories-gui-type: " . $type . ". Expected 'form' or 'inventory'");
		}
	}

	public function getGUI(Closure $callback) : void{
		if(time() - $this->last_request >= $this->refresh_rate){
			$this->plugin->getLogger()->debug("Fetching category information");
			static $callbacks = null;
			if($callbacks === null){
				$callbacks = [];
				$this->plugin->getApi()->getListing(TebexResponseHandler::onSuccess(function(TebexListingInfo $info) use(&$callbacks) : void{
					$class = $this->gui_class;

					$this->cached = new $class($this->plugin, $info);
					$this->last_request = time();

					foreach($callbacks as $cb){
						$cb($this->cached);
					}
					$callbacks = null;
				}));
			}
			$callbacks[spl_object_id($callback)] = $callback;
		}else{
			$callback($this->cached);
		}
	}
}