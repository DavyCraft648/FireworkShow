<?php
declare(strict_types=1);

namespace DavyCraft648\FireworkShow\command;

use DavyCraft648\FireworkShow\FireworkPosition;
use DavyCraft648\FireworkShow\FireworkShow;
use DavyCraft648\FireworkShow\ui\FireworkShowUI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function strtolower;

final class FireworkShowCommand extends Command{

	public function __construct(
		private readonly FireworkShow $plugin,
		private readonly FireworkShowUI $ui,
		string $name,
		string $description,
		string $usage = "",
		array $aliases = []
	){
		parent::__construct($name, $description, $usage, $aliases);
		$this->setPermission("fireworkshow.command.fireworkshow");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!isset($args[0])){
			if($sender instanceof Player){
				$this->ui->openMainForm($sender);
				return true;
			}
			return false;
		}

		switch(strtolower($args[0])){
			case 'list':
				if($sender instanceof Player){
					$this->ui->openMainForm($sender);
					return true;
				}
				$positions = $this->plugin->getPositionsByWorld();
				foreach($positions as $world => $list){
					$sender->sendMessage("World: $world");
					foreach($list as $i => $pos){
						$sender->sendMessage("  [$i] {$pos->pos->getFloorX()},{$pos->pos->getFloorY()},{$pos->pos->getFloorZ()} enabled=" . ($pos->enabled ? 'true' : 'false'));
					}
				}
				return true;
			case 'add':
				if(!isset($args[1], $args[2], $args[3], $args[4])){
					if($sender instanceof Player){
						$this->ui->openAddForm($sender);
						return true;
					}
					$sender->sendMessage("Usage: /$commandLabel add <world> <x> <y> <z>");
					return true;
				}
				$world = $args[1];
				$x = (int) $args[2];
				$y = (int) $args[3];
				$z = (int) $args[4];
				$pos = new FireworkPosition($world, new Vector3($x, $y, $z));
				$this->plugin->addPosition($pos);
				$sender->sendMessage("Added position to $world");
				return true;
			case 'remove':
				if(!isset($args[1], $args[2])){
					$sender->sendMessage("Usage: /$commandLabel remove <world> <index>");
					return true;
				}
				if($this->plugin->removePosition($args[1], (int) $args[2])){
					$sender->sendMessage("Removed position.");
				}else{
					$sender->sendMessage("Position not found.");
				}
				return true;
			case 'toggle':
				if(!isset($args[1], $args[2])){
					$sender->sendMessage("Usage: /$commandLabel toggle <world> <index>");
					return true;
				}
				if($this->plugin->togglePosition($args[1], (int) $args[2])){
					$sender->sendMessage("Toggled position.");
				}else{
					$sender->sendMessage("Position not found.");
				}
				return true;
			default:
				return false;
		}
	}
}
