<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 4/10/17
 * Time: 6:11 AM
 */

namespace Buycraft\PocketMine\Util;


use Buycraft\PocketMine\BuycraftPlugin;
use Buycraft\PocketMine\PluginApi;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AnalyticsSend extends AsyncTask
{
    const ANALYTICS_URL = PluginApi::BUYCRAFT_PLUGIN_API_URL . "/analytics/startup";

    private $json;
    private $secret;

    public function __construct($json, $secret)
    {
        //parent::__construct();
        $this->json = $json;
        $this->secret = $secret;
    }

    public static function sendAnalytics(BuycraftPlugin $plugin)
    {

        //noop
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        //noop
    }

    public function onCompletion(Server $server)
    {
        //noop
    }
}