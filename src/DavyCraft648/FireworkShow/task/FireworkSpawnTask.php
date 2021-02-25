<?php

namespace DavyCraft648\FireworkShow\task;

use DavyCraft648\FireworkShow\FireworkShow;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class FireworkSpawnTask extends Task
{
	public function onRun(int $currentTick)
	{
		if (count(Server::getInstance()->getLevelByName(FireworkShow::$world)->getPlayers()) >= 1) {
			foreach (FireworkShow::$positions as $pos) {
				FireworkShow::spawnFirework($pos);
			}
		}
	}
}