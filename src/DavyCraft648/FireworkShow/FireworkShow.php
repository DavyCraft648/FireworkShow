<?php

namespace DavyCraft648\FireworkShow;

use DavyCraft648\FireworkShow\entity\FireworksRocket;
use DavyCraft648\FireworkShow\item\Fireworks;
use DavyCraft648\FireworkShow\task\FireworkSpawnTask;
use Exception;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class FireworkShow extends PluginBase
{
	public static bool $nightOnly = false;
	public static array $positions = [];
	public static string $world;
	private bool $enabled = true;

	public function onEnable()
	{
		$this->checkConfig();
		$plMgr = $this->getServer()->getPluginManager();
		if ($this->enabled) {
			try {
				ItemFactory::registerItem(new Fireworks());
				Item::initCreativeItems();
				Entity::registerEntity(FireworksRocket::class, false, ["FireworksRocket"]);
			} catch (Exception $e) {
				$this->getLogger()->alert("Error: {$e->getMessage()}");
				$plMgr->disablePlugin($this);
			}
		} else $plMgr->disablePlugin($this);
	}

	private function checkConfig()
	{
		$this->saveDefaultConfig();
		$config = $this->getConfig();

		if ((string)$config->get("configVersion") !== $this->getFullName()) {
			$this->getLogger()->warning("Plugin not enabled due to invalid or outdated config");
			$this->enabled = false;
		}

		self::$nightOnly = (bool)$config->get("nightOnly", false);

		if (!is_null($this->getServer()->getLevelByName((string)$config->get("worldName")))) {
			self::$world = (string)$config->get("worldName");
			$this->getScheduler()->scheduleRepeatingTask(new FireworkSpawnTask(), (int)$config->get("spawnTick"));
		} else {
			$this->getLogger()->warning("Plugin not enabled due to invalid world name in config");
			$this->enabled = false;
		}

		for ($i = 1; $i <= (int)$config->get("positionCount"); $i++) {
			self::$positions[] = new Vector3((int)$config->getNested("pos$i.x"), (int)$config->getNested("pos$i.y"), (int)$config->getNested("pos$i.z"));
		}
	}

	public static function spawnFirework(Vector3 $pos)
	{
		$level = Server::getInstance()->getLevelByName(self::$world);
		$chunkX = $pos->getFloorX() >> 4;
		$chunkY = $pos->getFloorZ() >> 4;
		if ($level->isChunkLoaded($chunkX, $chunkY)) {
			/** @var Fireworks $item */
			$item = ItemFactory::get(Item::FIREWORKS);
			$item->addExplosion(mt_rand(0, 4), self::getFireworksColor(), "", (bool)mt_rand(0, 1), (bool)mt_rand(0, 1));
			$item->setFlightDuration(mt_rand(1, 2));

			$nbt = Entity::createBaseNBT($pos->add(0.5, 1, 0.5), new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
			$entity = Entity::createEntity("FireworksRocket", $level, $nbt, $item);
			$entity->spawnToAll();
		}
	}

	public static function isNight($level)
	{
		return $level->getTime() >= Level::TIME_NIGHT and $level->getTime() <= Level::TIME_SUNRISE;
	}

	private static function getFireworksColor(): string
	{
		$color_array = [
			Fireworks::COLOR_BLACK,
			Fireworks::COLOR_RED,
			Fireworks::COLOR_DARK_GREEN,
			Fireworks::COLOR_BROWN,
			Fireworks::COLOR_BLUE,
			Fireworks::COLOR_DARK_PURPLE,
			Fireworks::COLOR_DARK_AQUA,
			Fireworks::COLOR_GRAY,
			Fireworks::COLOR_DARK_GRAY,
			Fireworks::COLOR_PINK,
			Fireworks::COLOR_GREEN,
			Fireworks::COLOR_YELLOW,
			Fireworks::COLOR_LIGHT_AQUA,
			Fireworks::COLOR_DARK_PINK,
			Fireworks::COLOR_GOLD,
			Fireworks::COLOR_WHITE
		];

		return $color_array[array_rand($color_array)];
	}
}