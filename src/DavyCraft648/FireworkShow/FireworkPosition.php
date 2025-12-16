<?php
declare(strict_types=1);

namespace DavyCraft648\FireworkShow;

use pocketmine\item\FireworkRocketExplosion;
use pocketmine\math\Vector3;
use pocketmine\scheduler\TaskHandler;

final class FireworkPosition{

	public ?TaskHandler $handler = null;

	/** @param FireworkRocketExplosion[] $explosions */
	public function __construct(
		public string $worldName,
		public Vector3 $pos,
		public bool $enabled = true,
		public bool $nightOnly = false,
		public int $spawnTick = 40,
		public int $flightTimeMultiplier = 1,
		public array $explosions = []
	){
	}
}
