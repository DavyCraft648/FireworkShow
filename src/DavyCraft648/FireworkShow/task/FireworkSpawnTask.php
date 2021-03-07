<?php

namespace DavyCraft648\FireworkShow\task;

use DavyCraft648\FireworkShow\FireworkShow;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class FireworkSpawnTask extends Task
{
	public function onRun(int $currentTick)
	{
		$level = Server::getInstance()->getLevelByName(FireworkShow::$world);
		if (FireworkShow::$nightOnly and !(FireworkShow::isNight($level))) return;
		if (count($level->getPlayers()) >= 1) {
			foreach (FireworkShow::$positions as $pos) {
				FireworkShow::spawnFirework($pos);
			}
		}
	}
}