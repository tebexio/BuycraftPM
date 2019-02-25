<?php

namespace Buycraft\PocketMine\Commands;


use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SecretVerificationTask extends AsyncTask
{
    private $secret;
    private $dataFolder;

    /**
     * SecretVerificationTask constructor.
     * @param $secret string
     * @param $dataFolder string
     */
    public function __construct($secret, $dataFolder)
    {
        $this->secret = $secret;
        $this->dataFolder = $dataFolder;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        try {
            $api = new PluginApi($this->secret, $this->dataFolder);
            $this->setResult($api->basicGet("/information"));
        } catch (\Exception $e) {
            $this->setResult($e);
        }
    }

    public function onCompletion(Server $server)
    {
        $result = $this->getResult();
        if ($result instanceof \Exception) {
            //BuycraftPlugin::getInstance()->getLogger()->logException($result);
            BuycraftPlugin::getInstance()->getLogger()->error(TextFormat::RED . "This secret key appears to be invalid. Try again.");
        } else {

            BuycraftPlugin::getInstance()->changeApi(new PluginApi($this->secret, $this->dataFolder), $result);
            BuycraftPlugin::getInstance()->getConfig()->set('secret', $this->secret);
            BuycraftPlugin::getInstance()->getLogger()->info(TextFormat::GREEN . "Secret set!");
        }
    }
}