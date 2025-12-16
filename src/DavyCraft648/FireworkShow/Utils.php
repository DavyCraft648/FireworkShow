<?php
declare(strict_types=1);

namespace DavyCraft648\FireworkShow;

use pocketmine\block\utils\DyeColor;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\data\bedrock\FireworkRocketTypeIdMap;
use pocketmine\item\FireworkRocketExplosion;
use pocketmine\item\FireworkRocketType;
use function array_map;
use function explode;
use function is_int;
use function is_numeric;
use function is_string;
use function str_replace;
use function strtolower;
use function trim;

final class Utils{
	/**
	 * Resolve a mixed value (name, id or enum instance) to a FireworkRocketType
	 * @return FireworkRocketType|null
	 */
	public static function resolveType(FireworkRocketType|int|string $raw) : ?FireworkRocketType{
		if($raw instanceof FireworkRocketType) return $raw;
		if(is_int($raw) || is_numeric($raw)){
			return FireworkRocketTypeIdMap::getInstance()->fromId((int) $raw);
		}
		if(is_string($raw)){
			$needle = strtolower(trim($raw));
			foreach(FireworkRocketType::cases() as $case){
				if(strtolower($case->name) === $needle) return $case;
			}
			if(is_numeric($raw)){
				return FireworkRocketTypeIdMap::getInstance()->fromId((int) $raw);
			}
		}
		return null;
	}

	/**
	 * Resolve a mixed value (name, id or enum instance) to a DyeColor
	 */
	public static function resolveDyeColor(DyeColor|int|string $raw) : ?DyeColor{
		if($raw instanceof DyeColor) return $raw;
		if(is_int($raw) || is_numeric($raw)){
			return DyeColorIdMap::getInstance()->fromId((int) $raw);
		}
		if(is_string($raw)){
			$needle = strtolower(trim($raw));
			foreach(DyeColor::cases() as $case){
				if(strtolower($case->name) === $needle) return $case;
				if(strtolower(str_replace([' ', '-', '_'], '', $case->name)) === str_replace([' ', '-', '_'], '', $needle)) return $case;
			}
			if(is_numeric($raw)){
				return DyeColorIdMap::getInstance()->fromId((int) $raw);
			}
		}
		return null;
	}

	/**
	 * Parse a comma-separated color string to an array of DyeColor instances
	 * Returns an array of DyeColor
	 */
	public static function parseDyeColorList(string $raw) : array{
		$out = [];
		foreach(array_map('trim', explode(',', $raw)) as $c){
			if($c === '') continue;
			$col = self::resolveDyeColor($c);
			if($col !== null) $out[] = $col;
		}
		return $out;
	}

	public static function serializeExplosion(FireworkRocketExplosion $e) : array{
		$cols = [];
		foreach($e->getColors() as $c) $cols[] = $c->name;
		$fade = [];
		foreach($e->getFadeColors() as $c) $fade[] = $c->name;
		return ['type' => $e->getType()->name, 'colors' => $cols, 'fade' => $fade, 'twinkle' => $e->willTwinkle(), 'trail' => $e->getTrail()];
	}
}
