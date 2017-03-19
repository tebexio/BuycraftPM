<?php

namespace Buycraft\PocketMine;


class PluginApi
{
    const BUYCRAFT_PLUGIN_API_URL = "https://plugin.buycraft.net";

    private $secret;

    /**
     * ApiUtil constructor.
     * @param $secret string
     */
    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Returns the decoded JSON response of a simple GET Buycraft API call.
     * @param $endpoint string
     * @return mixed
     * @throws \Exception
     */
    public function basicGet($endpoint)
    {
        // Do a basic GET request
        $ctx = $this->initializeCurl(self::BUYCRAFT_PLUGIN_API_URL . $endpoint);
        $body = curl_exec($ctx);

        // Did the request fail? If so, return an error.
        if ($body === FALSE) {
            $err = curl_error($ctx);
            curl_close($ctx);

            throw new \Exception("cURL request has failed: " . $err);
        }

        curl_close($ctx);

        // Try to deserialize the response as JSON.
        $result = json_decode($body);

        if ($result === NULL) {
            throw new \Exception("Result can't be decoded as JSON.");
        }

        if (property_exists($result, 'error_code')) {
            throw new \Exception("Error " . $result->error_code . ": " . $result->error_message);
        }

        return $result;
    }

    /**
     * Returns a cURL session ready to be configured further. This sets the required cURL options for the Buycraft API.
     * @param $url string
     * @return resource
     */
    private function initializeCurl($url)
    {
        $ctx = curl_init($url);
        curl_setopt($ctx, CURLOPT_HTTPHEADER, ["X-Buycraft-Secret: " . $this->secret, "User-Agent: BuycraftPM"]);
        curl_setopt($ctx, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ctx, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ctx, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ctx, CURLOPT_TIMEOUT, 5);
        return $ctx;
    }

    /**
     * Delete the requested commands.
     * @param $ids array|integer
     * @throws \Exception
     */
    public function deleteCommands($ids)
    {
        if (count($ids) == 0) {
            throw new \Exception("Passed ids parameter is not a non-empty array.");
        }

		$query = "ids[]=" . implode('&ids[]=', $ids);
        $ctx = $this->initializeCurl(self::BUYCRAFT_PLUGIN_API_URL . "/queue");
        curl_setopt($ctx, CURLOPT_FAILONERROR, true);
        curl_setopt($ctx, CURLOPT_POST, 1);
        curl_setopt($ctx, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ctx, CURLOPT_POSTFIELDS, $query);
        $result = curl_exec($ctx);
        curl_close($ctx);

        if ($result === FALSE) {
            throw new \Exception("Unable to delete commands.");
        }
    }
}