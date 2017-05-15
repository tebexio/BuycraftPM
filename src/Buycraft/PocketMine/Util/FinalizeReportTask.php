<?php

namespace Buycraft\PocketMine\Util;


use Buycraft\PocketMine\BuycraftPlugin;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class FinalizeReportTask extends AsyncTask
{
    private $lines;
    private $fn;

    public function __construct($lines)
    {
        //parent::__construct();
        $this->lines = $lines;
        $this->fn = BuycraftPlugin::getInstance()->getDataFolder() . 'report-' . date('Y-m-d-H-i-s') . '.txt';
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $ss = ReportUtil::generateServiceStatus();
        $result = implode("\n", array_merge((array)$this->lines, $ss));
        file_put_contents($this->fn, $result);
    }

    public function onCompletion(Server $server)
    {
        BuycraftPlugin::getInstance()->getLogger()->info("Report saved to " .  $this->fn);
    }
}