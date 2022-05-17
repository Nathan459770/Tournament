<?php

namespace Nathan45\Tournament\Tasks;

use Nathan45\Tournament\Utils\DataManager;
use pocketmine\scheduler\AsyncTask;

class LoadItTask extends AsyncTask
{
    public function __construct(private string $path)
    {
    }

    public function onRun(): void
    {
        $results = [];
        $db = new \SQLite3($this->path);
        $data = $db->query("SELECT * FROM `ranks`");
        while($resultArr = $data->fetchArray(SQLITE3_ASSOC)) $results[$resultArr["username"]] = [$resultArr["rank"], $resultArr["scoreboard"]];
        $this->setResult($results);
        $db->close();
    }

    public function onCompletion(): void
    {
        DataManager::getInstance()->setAllData($this->getResult());
    }

}