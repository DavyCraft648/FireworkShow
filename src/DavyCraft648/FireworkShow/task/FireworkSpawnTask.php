<?php

namespace DavyCraft648\FireworkShow\task;

use DavyCraft648\FireworkShow\FireworkShow;
use pocketmine\level\Level;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class FireworkSpawnTask extends Task
{
	public function onRun(int $currentTick)
	{
		$level = Server::getInstance()->getLevelByName(FireworkShow::$world);
		if (FireworkShow::$nightOnly and !(FireworkShow::isNight($level))) return;
		if (!is_null(count($level->getPlayers()))) {
			foreach (FireworkShow::$positions as $pos) {
				FireworkShow::spawnFirework($pos);
			}
		}
	}
}