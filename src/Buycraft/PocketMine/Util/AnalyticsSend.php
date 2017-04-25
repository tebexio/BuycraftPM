<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 4/10/17
 * Time: 6:11 AM
 */

namespace Buycraft\PocketMine\Util;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\scheduler\AsyncTask;

class AnalyticsSend extends AsyncTask
{
    const ANALYTICS_URL = "https://plugin.buycraft.net/analytics/startup";

    private $json;
    private $secret;

    public function __construct($json, $secret)
    {
        parent::__construct();
        $this->json = $json;
        $this->secret = $secret;
    }

    public static function sendAnalytics(BuycraftPlugin $plugin)
    {
        $data = [
            'server' => [
                'platform' => 'pocketmine',
                'platform_version' => $plugin->getServer()->getPocketMineVersion(),
                'online_mode' => false
            ],
            'plugin' => [
                'version' => $plugin->getDescription()->getVersion()
            ]
        ];
        $json = json_encode($data);

        $plugin->getServer()->getScheduler()->scheduleAsyncTask(new AnalyticsSend($json, $plugin->getConfig()->get('secret')));
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $ctx = curl_init(self::ANALYTICS_URL);
        curl_setopt($ctx, CURLOPT_HTTPHEADER, ["X-Buycraft-Secret: " . $this->secret, "User-Agent: BuycraftPM", 'Content-Type: application/json']);
        curl_setopt($ctx, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ctx, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ctx, CURLOPT_TIMEOUT, 5);
        curl_setopt($ctx, CURLOPT_POST, 1);
        curl_setopt($ctx, CURLOPT_POSTFIELDS, $this->json);
        curl_setopt($ctx, CURLOPT_FAILONERROR, true);

        $result = curl_exec($ctx);
        $err = curl_error($ctx);
        curl_close($ctx);

        if ($result === FALSE) {
            throw new \Exception("Unable to send analytics: " . $err);
        }
    }
}