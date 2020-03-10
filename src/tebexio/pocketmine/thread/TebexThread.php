<?php

declare(strict_types=1);

namespace tebexio\pocketmine\thread;

use function is_string;
use Logger;
use pocketmine\utils\MainLogger;
use tebexio\pocketmine\api\TebexRequest;
use tebexio\pocketmine\api\EmptyTebexResponse;
use tebexio\pocketmine\api\RespondingTebexRequest;
use tebexio\pocketmine\TebexAPI;
use tebexio\pocketmine\thread\request\TebexRequestHolder;
use tebexio\pocketmine\thread\response\TebexResponseFailureHolder;
use tebexio\pocketmine\thread\response\TebexResponseHandler;
use tebexio\pocketmine\thread\response\TebexResponseHolder;
use tebexio\pocketmine\thread\response\TebexResponseSuccessHolder;
use tebexio\pocketmine\thread\ssl\SSLConfiguration;
use Generator;
use JsonException;
use pocketmine\Thread;
use Threaded;

final class TebexThread extends Thread{

	/** @var TebexResponseHandler[] */
	private static $handlers = [];

	/** @var int */
	private static $handler_ids = 0;

	/** @var Threaded<string> */
	private $incoming;

	/** @var Threaded<string> */
	private $outgoing;

	/** @var Logger */
	private $logger;

	/** @var int */
	public $busy_score = 0;

	/** @var bool */
	private $running = false;

	/** @var string */
	private $secret;

	/** @var string */
	private $ca_path;

	public function __construct(string $secret, SSLConfiguration $ssl_config){
		$this->ca_path = $ssl_config->getCAInfoPath();
		$this->incoming = new Threaded();
		$this->outgoing = new Threaded();
		$this->logger = MainLogger::getLogger();
		$this->secret = $secret;
	}

	public function push(TebexRequest $request, TebexResponseHandler $handler) : void{
		$handler_id = ++self::$handler_ids;
		$this->incoming[] = igbinary_serialize(new TebexRequestHolder($request, $handler_id));
		self::$handlers[$handler_id] = $handler;
		++$this->busy_score;
		$this->synchronized(function() : void{
			$this->notify();
		});
	}

	/**
	 * @return array<int, mixed>
	 */
	private function getDefaultCurlOptions() : array{
		$curl_opts = [
			CURLOPT_HTTPHEADER => [
				"X-Tebex-Secret: " . $this->secret,
				"User-Agent: Tebex-PMMP"
			],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 5,
		];
		if($this->ca_path !== ""){
			$curl_opts[CURLOPT_CAINFO] = $this->ca_path;
		}
		return $curl_opts;
	}

	public function run() : void{
		$this->running = true;
		$this->registerClassLoader();
		$default_curl_opts = $this->getDefaultCurlOptions();
		while($this->running){
			while(($request = $this->incoming->shift()) !== null){
				/** @var TebexRequestHolder $request_holder */
				$request_holder = igbinary_unserialize($request);

				/** @var TebexRequest $request */
				$request = $request_holder->request;

				$url = TebexAPI::BASE_ENDPOINT . $request->getEndpoint();
				$this->logger->debug("[cURL] Executing request: " . $url);

				$ch = curl_init($url);
				if($ch === false){
					$response_holder = new TebexResponseFailureHolder($request_holder->handler_id, 5000, new TebexException("cURL request failed during initialization"));
				}else{
					$curl_opts = $default_curl_opts;
					$request->addAdditionalCurlOpts($curl_opts);
					curl_setopt_array($ch, $curl_opts);

					$body = curl_exec($ch);
					$latency = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

					if(!is_string($body)){
						$response_holder = new TebexResponseFailureHolder($request_holder->handler_id, $latency, new TebexException("cURL request failed {" . curl_errno($ch) . "): " . curl_error($ch)));
					}else{
						$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
						if($response_code !== $request->getExpectedResponseCode()){
							$response_holder = new TebexResponseFailureHolder($request_holder->handler_id, $latency, new TebexException(json_decode($body, true)["error_message"] ?? "Expected response code " . $request->getExpectedResponseCode() . ", got " . $response_code));
						}elseif($request instanceof RespondingTebexRequest){
							$exception = null;
							$result = null;
							try{
								$result = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
							}catch(JsonException $e){
								$exception = $e;
							}
							if($exception !== null){
								$response_holder = new TebexResponseFailureHolder($request_holder->handler_id, $latency, new TebexException($exception->getMessage()));
							}elseif(isset($result["error_code"])){
								$response_holder = new TebexResponseFailureHolder($request_holder->handler_id, $latency, new TebexException($result["error_message"]));
							}else{
								$response_holder = new TebexResponseSuccessHolder($request_holder->handler_id, $latency, $request->createResponse($result));
							}
						}else{
							$response_holder = new TebexResponseSuccessHolder($request_holder->handler_id, $latency, EmptyTebexResponse::instance());
						}
					}

					curl_close($ch);
				}

				$this->outgoing[] = igbinary_serialize($response_holder);
			}

			$this->sleep();
		}
	}

	public function sleep() : void{
		$this->synchronized(function() : void{
			if($this->running){
				$this->wait();
			}
		});
	}

	public function stop() : void{
		$this->running = false;
		$this->synchronized(function() : void{
			$this->notify();
		});
	}

	/**
	 * Collects all responses and returns the total latency
	 * (in seconds) in sending request and getting response.
	 *
	 * @return Generator<float>
	 */
	public function collect() : Generator{
		while(($holder = $this->outgoing->shift()) !== null){
			/** @var TebexResponseHolder $holder */
			$holder = igbinary_unserialize($holder);

			$holder->trigger(self::$handlers[$holder->handler_id]);
			unset(self::$handlers[$holder->handler_id]);
			--$this->busy_score;

			yield $holder->latency;
		}
	}

	public function setGarbage() : void{
		// NOOP
	}
}