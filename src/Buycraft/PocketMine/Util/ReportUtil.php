<?php

namespace Buycraft\PocketMine\Util;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\Server;
use pocketmine\utils\Utils;

class ReportUtil
{
    /**
     * Captures the output of phpinfo(). If possible, it will try using `exec()` before resorting to using object
     * buffering in the current process.
     * @return array
     */
    public static function capturePhpinfo() {
        if (function_exists("exec")) {
            // Execute the existing PHP binary with the --info parameter. We'll prefer this method as it's
            // less prone to breaking things, and most shared hosts are too dumb to block this.
            $out = [];
            exec(PHP_BINARY . " --info", $out);
            return $out;
        }

        // In the event that didn't work, we'll run phpinfo() in this current process and capture the output with
        // ob_*() functions.
        ob_start();
        phpinfo();
        $output = ob_get_contents();
        ob_end_clean();
        return explode('\n', $output);
    }

    /**
     * Generates the main part of the report that requires access to resources that can only be safely accessed in the
     * server thread.
     * @return array
     */
    public static function generateBaseReport() {
        $report_lines = [];
        $report_lines[] = "### Server Information ###";
        $report_lines[] = "Report generated on " . date('r');
        $report_lines[] = "";
        $report_lines[] = "Operating system: " . PHP_OS . " / " . Utils::getOS();
        $report_lines[] = "PHP version: " . PHP_VERSION;
        $report_lines[] = "Server version: " . Server::getInstance()->getPocketMineVersion() . " (API: " .
            Server::getInstance()->getApiVersion() . ")";

        $report_lines[] = "";
        $report_lines[] = "### Platform Information ###";
        $report_lines[] = "Plugin version: " . BuycraftPlugin::getInstance()->getDescription()->getVersion();
        $report_lines[] = "";
        $api_exists = BuycraftPlugin::getInstance()->getPluginApi() !== null;
        $report_lines[] = "Connected to Buycraft? " . ($api_exists ? 'yes' : 'no');
        $information = BuycraftPlugin::getInstance()->getServerInformation();
        if ($information !== NULL) {
            $report_lines[] = "Web store ID: " . $information->account->id;
            $report_lines[] = "Web store URL: " . $information->account->domain;
            $report_lines[] = "Web store name: " . $information->account->name;
            $report_lines[] = "Web store currency: " . $information->account->currency->iso_4217;
            $report_lines[] = "Web store in online mode? " . $information->account->online_mode;

            $report_lines[] = "Server name: " . $information->server->name;
            $report_lines[] = "Server ID: " . $information->server->id;
        }

        $report_lines[] = "";
        $report_lines[] = "### Service Status ###";
        return $report_lines;
    }

    /**
     * Generates the service status lines (this has to be done in an async task for obvious reasons).
     * @return array
     */
    public static function generateServiceStatus() {
        $checks = [
            // Notice that we're not using just plugin.buycraft.net. That's because it throws an error. We'll compromise
            // and use the PocketMine versions page.
            'Buycraft plugin API' => 'https://plugin.buycraft.net/versions/pocketmine',
            "Google over HTTPS" => 'https://encrypted.google.com',
            "Google over HTTP" => 'http://www.google.com'
        ];

        $results = [];

        foreach($checks as $name => $url) {
            $ctx = curl_init($url);
            curl_setopt($ctx, CURLOPT_FAILONERROR, true);
            if ($name === 'Buycraft plugin API') {
                curl_setopt($ctx, CURLOPT_CAINFO, BuycraftPlugin::getInstance()->getDataFolder() . "comodo_ecc.pem");
            } else {
                curl_setopt($ctx, CURLOPT_SSL_VERIFYPEER, false);
            }
            curl_setopt($ctx, CURLOPT_TIMEOUT, 5);
            $result = curl_exec($ctx);
            if ($result === FALSE) {
                $results[] = "Can't access " . $name . " (" . $url . "): " . curl_error($ctx);
            } else {
                $results[] = "Can access " . $name . " (" . $url . "):";
            }
            curl_close($ctx);
        }

        return $results;
    }
}