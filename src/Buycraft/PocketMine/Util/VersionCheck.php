<?php
/**
 * Created by PhpStorm.
 * User: andrew
 * Date: 4/25/17
 * Time: 5:18 AM
 */

namespace Buycraft\PocketMine\Util;


use pocketmine\event\Listener;

class VersionCheck implements Listener
{
    const VERSION_URL = "https://plugin.buycraft.net/versions/pocketmine";

    private $version;

    public function check()
    {
        $ctx = curl_init(self::VERSION_URL);
        curl_setopt($ctx, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ctx, CURLOPT_TIMEOUT, 5);
        curl_setopt($ctx, CURLOPT_RETURNTRANSFER, true);
    }
}