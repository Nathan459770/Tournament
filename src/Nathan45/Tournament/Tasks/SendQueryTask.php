<?php

namespace Nathan45\Tournament\Tasks;

use pocketmine\scheduler\AsyncTask;

class SendQueryTask extends AsyncTask
{
    public function __construct(private string $path, private string $query)
    {
    }

    public function onRun(): void
    {
        $db = new \SQLite3($this->path);
        if($db->query($this->query) == false){
            var_dump("ERROR on line 17, SendQueryTask.php: \n" . mysqli_error($db) . "\n\n query: " . $this->query);
        }
        $db->close();
    }

}