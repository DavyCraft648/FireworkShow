<?php
declare(strict_types=1);

namespace DavyCraft648\FireworkShow;

use pocketmine\block\utils\DyeColor;
use pocketmine\command\ClosureCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\FireworkRocketTypeIdMap;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\entity\Location;
use pocketmine\entity\object\FireworkRocket;
use pocketmine\event\EventPriority;
use pocketmine\item\FireworkRocketExplosion;
use pocketmine\item\FireworkRocketType;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\scheduler\ClosureTask;
use function array_keys;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function lcg_value;
use function max;
use function mt_rand;
use function str_replace;
use function strtolower;
use function trim;

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
		$this->getServer()->getCommandMap()->register(
			$this->getName(),
			new ClosureCommand(
				$commandName,
				function(CommandSender $sender, Command $command, string $commandLabel, array $args) : mixed{
					//Todo: Implement command functionality
					return null;
				},
				["fireworkshow.command.fireworkshow"],
				$commandDescription,
				$commandUsage,
				$commandAliases
			)
		);

		$this->getServer()->getPluginManager()->registerEvent(WorldLoadEvent::class, fn(WorldLoadEvent $event) => $this->onWorldLoaded($event->getWorld()), EventPriority::NORMAL, $this);
		$this->getServer()->getPluginManager()->registerEvent(WorldUnloadEvent::class, fn(WorldUnloadEvent $event) => $this->onWorldUnloaded($event->getWorld()), EventPriority::NORMAL, $this);
		$this->loadConfigPositions();

		foreach(array_keys($this->positionsByWorld) as $worldName){
			$world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
			if($world instanceof World){
				$this->onWorldLoaded($world);
			}
		}
	}

	private function loadConfigPositions() : void{
		$config = $this->getConfig();
		$entries = $config->get("positions", []);
		if(!is_array($entries)) return;

		$resolveType = function($raw) : ?FireworkRocketType{
			if($raw instanceof FireworkRocketType) return $raw;
			if(is_int($raw) || is_numeric($raw)){
				return FireworkRocketTypeIdMap::getInstance()->fromId((int) $raw);
			}
			if(is_string($raw)){
				$needle = strtolower(trim((string) $raw));
				foreach(FireworkRocketType::cases() as $case){
					if(strtolower($case->name) === $needle) return $case;
				}
				if(is_numeric($raw)){
					return FireworkRocketTypeIdMap::getInstance()->fromId((int) $raw);
				}
			}
			return null;
		};

		$resolveDyeColor = function($raw) : ?DyeColor{
			if($raw instanceof DyeColor) return $raw;
			if(is_int($raw) || is_numeric($raw)){
				return DyeColorIdMap::getInstance()->fromId((int) $raw);
			}
			if(is_string($raw)){
				$needle = strtolower(trim((string) $raw));
				foreach(DyeColor::cases() as $case){
					if(strtolower($case->name) === $needle) return $case;
					if(strtolower(str_replace([' ', '-', '_'], '', $case->name)) === str_replace([' ', '-', '_'], '', $needle)) return $case;
				}
				if(is_numeric($raw)){
					return DyeColorIdMap::getInstance()->fromId((int) $raw);
				}
			}
			return null;
		};

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

						$type = $resolveType($typeRaw);
						if($type === null) continue;

						$colors = [];
						foreach($colorsRaw as $c){
							$col = $resolveDyeColor($c);
							if($col !== null) $colors[] = $col;
						}

						$fade = [];
						foreach($fadeRaw as $f){
							$col = $resolveDyeColor($f);
							if($col !== null) $fade[] = $col;
						}

						if($colors === []) continue;

						$explosions[] = new FireworkRocketExplosion($type, $colors, $fade, $twinkle, $trail);
					}catch(\Throwable $e){
						$this->getLogger()->debug("Invalid explosion config: " . $e->getMessage());
					}
				}
			}

			$pos = new FireworkPosition($worldName, new Vector3($x, $y, $z), $enabled, $nightOnly, $spawnTick, $explosions);
			$this->positionsByWorld[$worldName][] = $pos;
			$counter++;
		}
		$this->getLogger()->debug("Loaded $counter firework positions from config.");
	}

	public function onWorldLoaded(World $world) : void{
		$worldName = $world->getFolderName();
		if(!isset($this->positionsByWorld[$worldName])) return;

		if(isset($this->worldLoaded[$worldName])) return;

		$this->worldLoaded[$worldName] = $world->getId();

		foreach($this->positionsByWorld[$worldName] as $pos){
			if(!$pos->enabled) continue;

			$pos->handler = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($worldName, $pos) : void{
				$server = Server::getInstance();
				$worldObj = $server->getWorldManager()->getWorld($this->worldLoaded[$worldName]);
				if(!$worldObj instanceof World) return;

				if($pos->nightOnly){
					$time = $worldObj->getTime();
					if($time < World::TIME_NIGHT || $time > World::TIME_SUNRISE) return;
				}

				$this->spawnFireworkOnWorld($worldObj, $pos->pos, $pos->explosions);
			}), max(1, $pos->spawnTick));
		}
	}

	public function onWorldUnloaded(World $world) : void{
		$worldName = $world->getFolderName();
		if(!isset($this->positionsByWorld[$worldName])) return;

		foreach($this->positionsByWorld[$worldName] as $pos){
			if($pos->handler !== null){
				$pos->handler->cancel();
				$pos->handler = null;
			}
		}

		unset($this->worldLoaded[$worldName]);
	}

	public function spawnFireworkOnWorld(World $world, Vector3 $pos, array $explosions = []) : void{
		$chunkX = $pos->getFloorX() >> 4;
		$chunkZ = $pos->getFloorZ() >> 4;
		if(!$world->isChunkLoaded($chunkX, $chunkZ)) return;

		$randomDuration = (10 * 1) + mt_rand(0, 12);
		$entity = new FireworkRocket(Location::fromObject($pos->add(0.5, 1, 0.5), $world, (float) (lcg_value() * 360), 90), $randomDuration, $explosions);
		$entity->spawnToAll();
	}
}
