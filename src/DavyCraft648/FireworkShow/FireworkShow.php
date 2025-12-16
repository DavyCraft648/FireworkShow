<?php
declare(strict_types=1);

namespace DavyCraft648\FireworkShow;

use DavyCraft648\FireworkShow\command\FireworkShowCommand;
use DavyCraft648\FireworkShow\ui\FireworkShowUI;
use DavyCraft648\PMServerUI\PMServerUI;
use pocketmine\entity\Location;
use pocketmine\entity\object\FireworkRocket;
use pocketmine\event\EventPriority;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\item\FireworkRocketExplosion;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\World;
use function array_map;
use function array_splice;
use function array_values;
use function is_array;
use function lcg_value;
use function max;
use function mt_rand;

final class FireworkShow extends PluginBase{
	/** @var array<string, FireworkPosition[]> */
	private array $positionsByWorld = [];

	/** @var array<string, int> */
	private array $worldLoaded = [];

	protected function onEnable() : void{
		$this->saveDefaultConfig();

		$config = $this->getConfig();
		$commandCfg = $config->get("command", []);
		$commandName = (string) ($commandCfg["name"] ?? "fireworkshow");
		$commandDescription = (string) ($commandCfg["description"] ?? "Manage firework shows");
		$commandUsage = (string) ($commandCfg["usage"] ?? ("/" . $commandName));
		$commandAliases = is_array($commandCfg["aliases"] ?? null) ? array_map('strval', array_values($commandCfg["aliases"])) : [];

		PMServerUI::register($this);
		$ui = new FireworkShowUI($this);
		$this->getServer()->getCommandMap()->register($this->getName(), new FireworkShowCommand($this, $ui, $commandName, $commandDescription, $commandUsage, $commandAliases));

		$this->getServer()->getPluginManager()->registerEvent(WorldLoadEvent::class, fn(WorldLoadEvent $event) => $this->onWorldLoaded($event->getWorld()), EventPriority::NORMAL, $this);
		$this->getServer()->getPluginManager()->registerEvent(WorldUnloadEvent::class, fn(WorldUnloadEvent $event) => $this->onWorldUnloaded($event->getWorld()), EventPriority::NORMAL, $this);
		$this->loadConfigPositions();

		foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
			$this->onWorldLoaded($world);
		}
	}

	public function loadConfigPositions() : void{
		$config = $this->getConfig();
		$entries = $config->get("positions", []);
		if(!is_array($entries)) return;

		$counter = 0;
		foreach($entries as $entry){
			if(!is_array($entry)) continue;
			$worldName = (string) ($entry['worldName'] ?? $entry['world'] ?? $config->get("worldName", ""));
			$x = (int) ($entry['x'] ?? 0);
			$y = (int) ($entry['y'] ?? 0);
			$z = (int) ($entry['z'] ?? 0);
			$enabled = (bool) ($entry['enabled'] ?? true);
			$nightOnly = (bool) ($entry['nightOnly'] ?? false);
			$spawnTick = (int) ($entry['spawnTick'] ?? $config->get("spawnTick", 40));
			$flight = (int) ($entry['flightTimeMultiplier'] ?? $config->get("flightTimeMultiplier", 1));

			$explosions = [];
			$rawExplosions = is_array($entry['explosions'] ?? null) ? $entry['explosions'] : [];
			if($rawExplosions !== []){
				foreach($rawExplosions as $cfg){
					try{
						if(!is_array($cfg)) continue;
						$typeRaw = $cfg['type'] ?? 0;
						$colorsRaw = is_array($cfg['colors'] ?? null) ? $cfg['colors'] : [];
						$fadeRaw = is_array($cfg['fade'] ?? null) ? $cfg['fade'] : [];
						$twinkle = (bool) ($cfg['twinkle'] ?? false);
						$trail = (bool) ($cfg['trail'] ?? false);

						$type = Utils::resolveType($typeRaw);
						if($type === null) continue;

						$colors = [];
						foreach($colorsRaw as $c){
							$col = Utils::resolveDyeColor($c);
							if($col !== null) $colors[] = $col;
						}

						$fade = [];
						foreach($fadeRaw as $f){
							$col = Utils::resolveDyeColor($f);
							if($col !== null) $fade[] = $col;
						}

						if($colors === []) continue;

						$explosions[] = new FireworkRocketExplosion($type, $colors, $fade, $twinkle, $trail);
					}catch(\Throwable $e){
						$this->getLogger()->debug("Invalid explosion config: " . $e->getMessage());
					}
				}
			}

			$pos = new FireworkPosition($worldName, new Vector3($x, $y, $z), $enabled, $nightOnly, $spawnTick, $flight, $explosions);
			$this->positionsByWorld[$worldName][] = $pos;
			$counter++;
		}
		$this->getLogger()->debug("Loaded $counter firework positions from config.");
	}

	/** @return array<string, FireworkPosition[]> */
	public function getPositionsByWorld() : array{
		return $this->positionsByWorld;
	}

	public function addPosition(FireworkPosition $pos) : void{
		$this->positionsByWorld[$pos->worldName][] = $pos;
		if($pos->enabled){
			$this->schedulePositionHandler($pos->worldName, $pos);
		}
	}

	public function removePosition(string $worldName, int $index) : bool{
		if(!isset($this->positionsByWorld[$worldName][$index])) return false;
		$pos = $this->positionsByWorld[$worldName][$index];
		$this->cancelHandlerForPosition($pos);
		array_splice($this->positionsByWorld[$worldName], $index, 1);
		if($this->positionsByWorld[$worldName] === []) unset($this->positionsByWorld[$worldName]);
		return true;
	}

	public function togglePosition(string $worldName, int $index) : bool{
		if(!isset($this->positionsByWorld[$worldName][$index])) return false;
		$pos = $this->positionsByWorld[$worldName][$index];
		$pos->enabled = !$pos->enabled;
		if($pos->enabled){
			if(isset($this->worldLoaded[$worldName])){
				if($pos->handler === null){
					$worldObj = Server::getInstance()->getWorldManager()->getWorld($this->worldLoaded[$worldName]);
					if($worldObj instanceof World){
						$this->schedulePositionHandler($worldName, $pos);
					}
				}
			}
		}else{
			$this->cancelHandlerForPosition($pos);
		}
		return true;
	}

	private function cancelHandlerForPosition(FireworkPosition $pos) : void{
		if($pos->handler !== null){
			$pos->handler->cancel();
			$pos->handler = null;
		}
	}

	public function positionsToConfigArray() : array{
		$out = [];
		foreach($this->positionsByWorld as $world => $list){
			foreach($list as $p){
				$expl = [];
				foreach($p->explosions as $e){
					$expl[] = Utils::serializeExplosion($e);
				}
				$out[] = [
					'worldName' => $world,
					'x' => $p->pos->getFloorX(),
					'y' => $p->pos->getFloorY(),
					'z' => $p->pos->getFloorZ(),
					'enabled' => $p->enabled,
					'nightOnly' => $p->nightOnly,
					'spawnTick' => $p->spawnTick,
					'flightTimeMultiplier' => $p->flightTimeMultiplier,
					'explosions' => $expl,
				];
			}
		}
		return $out;
	}

	public function savePositionsToConfig() : void{
		$this->getConfig()->set('positions', $this->positionsToConfigArray());
		$this->getConfig()->save();
	}

	private function schedulePositionHandler(string $worldName, FireworkPosition $pos) : void{
		if($pos->handler !== null) return;
		$pos->handler = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($worldName, $pos) : void{
			$server = Server::getInstance();
			$worldObj = $server->getWorldManager()->getWorld($this->worldLoaded[$worldName] ?? -1);
			if(!$worldObj instanceof World || !$worldObj->isLoaded()){
				$this->getLogger()->debug("World $worldName is not loaded, this task should have been canceled.");
				return;
			}

			if($pos->nightOnly){
				$time = $worldObj->getTime();
				if($time < World::TIME_NIGHT || $time > World::TIME_SUNRISE) return;
			}

			$this->spawnFireworkOnWorld($worldObj, $pos->pos, $pos->explosions, $pos->flightTimeMultiplier);
		}), max(1, $pos->spawnTick));
	}

	public function onWorldLoaded(World $world) : void{
		$worldName = $world->getFolderName();
		if(isset($this->worldLoaded[$worldName])) return;

		$this->worldLoaded[$worldName] = $world->getId();

		foreach($this->positionsByWorld[$worldName] ?? [] as $pos){
			if(!$pos->enabled) continue;

			$this->schedulePositionHandler($worldName, $pos);
		}
	}

	public function onWorldUnloaded(World $world) : void{
		$worldName = $world->getFolderName();
		if(!isset($this->worldLoaded[$worldName])) return;

		foreach($this->positionsByWorld[$worldName] ?? [] as $pos){
			$this->cancelHandlerForPosition($pos);
		}

		unset($this->worldLoaded[$worldName]);
	}

	public function spawnFireworkOnWorld(World $world, Vector3 $pos, array $explosions = [], int $flight = 1) : void{
		$chunkX = $pos->getFloorX() >> 4;
		$chunkZ = $pos->getFloorZ() >> 4;
		if(!$world->isChunkLoaded($chunkX, $chunkZ)) return;

		$randomDuration = (($flight + 1) * 10) + mt_rand(0, 12);
		$entity = new FireworkRocket(Location::fromObject($pos->add(0.5, 1, 0.5), $world, (float) (lcg_value() * 360), 90), $randomDuration, $explosions);
		$entity->spawnToAll();
	}
}
