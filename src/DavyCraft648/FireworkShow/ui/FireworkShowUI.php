<?php
declare(strict_types=1);

namespace DavyCraft648\FireworkShow\ui;

use DavyCraft648\FireworkShow\FireworkPosition;
use DavyCraft648\FireworkShow\FireworkShow;
use DavyCraft648\PMServerUI\ActionFormData;
use DavyCraft648\PMServerUI\ActionFormResponse;
use DavyCraft648\PMServerUI\ModalFormData;
use DavyCraft648\PMServerUI\ModalFormResponse;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\FireworkRocketExplosion;
use pocketmine\item\FireworkRocketType;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function array_map;
use function count;
use function explode;
use function implode;
use function str_replace;
use function strtolower;
use function trim;

final class FireworkShowUI{

	public function __construct(private readonly FireworkShow $plugin){ }

	public function openMainForm(Player $player) : void{
		$positions = $this->plugin->getPositionsByWorld();
		$form = ActionFormData::create()
			->title("FireworkShow")
			->body("Select position or add");
		$items = [];
		foreach($positions as $world => $list){
			foreach($list as $i => $pos){
				$form->button("$world #$i ({$pos->pos->getFloorX()},{$pos->pos->getFloorY()},{$pos->pos->getFloorZ()})");
				$items[] = [$world, $i];
			}
		}
		if($items !== []){
			$form->divider();
		}
		$form->button("Add Position");

		$form->show($player)->then(function(Player $p, ActionFormResponse $res) use ($items) : void{
			if($res->canceled || $res->selection === null) return;
			$index = $res->selection;
			if($index >= count($items)){
				$this->openAddForm($p);
				return;
			}
			[$world, $i] = $items[$index];
			$this->openEditForm($p, $world, $i);
		});
	}

	public function openAddForm(Player $player) : void{
		$pos = $player->getPosition();
		$modal = ModalFormData::create()->title("Add Firework Position");
		$modal->textField("World name", $pos->getWorld()->getFolderName(), $pos->getWorld()->getFolderName());
		$modal->textField("X", (string) $pos->getFloorX(), (string) $pos->getFloorX());
		$modal->textField("Y", (string) $pos->getFloorY(), (string) $pos->getFloorY());
		$modal->textField("Z", (string) $pos->getFloorZ(), (string) $pos->getFloorZ());
		$modal->show($player)->then(function(Player $p, ModalFormResponse $data) : void{
			if($data->canceled){
				$this->openMainForm($p);
				return;
			}
			[$world, $x, $y, $z] = $data->formValues;
			$pos = new FireworkPosition($world, new Vector3((int) $x, (int) $y, (int) $z));
			$this->plugin->addPosition($pos);
			$this->plugin->savePositionsToConfig();
			$p->sendMessage("Added position to $world");
			$this->openMainForm($p);
		});
	}

	private function openEditForm(Player $player, string $world, int $index) : void{
		$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
		if($pos === null) return;

		$form = ActionFormData::create()->title("Edit Position")->body("Choose action for $world #$index");
		$form->button("Edit basic (world/x/y/z/flags)");
		$form->button("Edit explosions");
		$form->button($pos->enabled ? "Disable" : "Enable");
		$form->button("Delete position");
		$form->button("Back");

		$form->show($player)->then(function(Player $p, ActionFormResponse $res) use ($world, $index) : void{
			if($res->canceled || $res->selection === null) return;
			switch($res->selection){
				case 0:
					$this->openEditBasicForm($p, $world, $index);
					break;
				case 1:
					$this->openExplosionsForm($p, $world, $index);
					break;
				case 2:
					if(!$this->plugin->togglePosition($world, $index)) return;
					$this->plugin->savePositionsToConfig();
					$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
					$p->sendMessage($pos->enabled ? "Position enabled." : "Position disabled.");
					$this->openEditForm($p, $world, $index);
					break;
				case 3:
					if($this->plugin->removePosition($world, $index)){
						$this->plugin->savePositionsToConfig();
						$p->sendMessage("Position removed.");
					}else{
						$p->sendMessage("Failed to remove position.");
					}
					$this->openEditForm($p, $world, $index);
					break;
				default:
					$this->openMainForm($p);
			}
		});
	}

	private function openEditBasicForm(Player $player, string $world, int $index) : void{
		$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
		if($pos === null) return;
		$modal = ModalFormData::create()->title("Edit Position");
		$modal->textField("World name", $pos->worldName, $pos->worldName);
		$modal->textField("X", (string) $pos->pos->getFloorX(), (string) $pos->pos->getFloorX());
		$modal->textField("Y", (string) $pos->pos->getFloorY(), (string) $pos->pos->getFloorY());
		$modal->textField("Z", (string) $pos->pos->getFloorZ(), (string) $pos->pos->getFloorZ());
		$modal->toggle("Night Only", default: $pos->nightOnly);
		$modal->textField("Spawn Tick", (string) $pos->spawnTick, (string) $pos->spawnTick, "20 ticks = ~1 second");
		$modal->show($player)->then(function(Player $p, ModalFormResponse $data) use ($world, $index) : void{
			if($data->canceled){
				$this->openEditForm($p, $world, $index);
				return;
			}
			[$newWorld, $x, $y, $z, $nightOnly, $spawnTick] = $data->formValues;
			$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
			if($pos === null) return;

			$oldWorld = $pos->worldName;
			$pos->pos = new Vector3((int) $x, (int) $y, (int) $z);
			$pos->nightOnly = (bool) $nightOnly;
			$pos->spawnTick = (int) $spawnTick;

			if($newWorld !== $oldWorld){
				$this->plugin->removePosition($oldWorld, $index);
				$pos->worldName = $newWorld;
				$this->plugin->addPosition($pos);
			}

			$this->plugin->savePositionsToConfig();
			$p->sendMessage("Updated position.");
			$this->openEditForm($p, $world, $index);
		});
	}

	private function openExplosionsForm(Player $player, string $world, int $index) : void{
		$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
		if($pos === null) return;

		$form = ActionFormData::create()->title("Explosions for $world #$index")->body("Select explosion to edit or add new");
		$items = [];
		foreach($pos->explosions as $i => $e){
			$cols = [];
			foreach($e->getColors() as $c) $cols[] = $c->name;
			$fade = [];
			foreach($e->getFadeColors() as $c) $fade[] = $c->name;
			$items[] = $i;
			$form->button("#$i: {$e->getType()->name} colors:" . implode(',', $cols) . " fade:" . implode(',', $fade) . " twinkle:" . ($e->willTwinkle() ? "yes" : "no") . " trail:" . ($e->getTrail() ? "yes" : "no"));
		}
		if($items !== []){
			$form->divider();
		}
		$form->button("Add Explosion");

		$form->show($player)->then(function(Player $p, ActionFormResponse $res) use ($world, $index, $items) : void{
			if($res->canceled || $res->selection === null){
				$this->openEditForm($p, $world, $index);
				return;
			}
			$sel = $res->selection;
			if($sel >= count($items)){
				$this->openAddExplosionForm($p, $world, $index);
				return;
			}
			$explIndex = $items[$sel];
			$this->openEditExplosionForm($p, $world, $index, $explIndex);
		});
	}

	private function openAddExplosionForm(Player $player, string $world, int $index) : void{
		$modal = ModalFormData::create()->title("Add Explosion");
		$modal->textField("Shape", "small_ball", tooltip: "The shape of the explosion. Can be small_ball, large_ball, star, creeper, or burst");
		$modal->textField("Colors (comma separated, e.g. red,blue)", "light_blue", tooltip: "The colors of the initial particles of the explosion, randomly selected from");
		$modal->textField("Fade (comma separated)", tooltip: "The colors of the fading particles of the explosion, randomly selected from");
		$modal->toggle("Twinkle", default: false, tooltip: "Whether or not the explosion has a twinkle effect (glowstone dust)");
		$modal->toggle("Trail", default: false, tooltip: "Whether or not the explosion has a trail effect (diamond)");
		$modal->show($player)->then(function(Player $p, ModalFormResponse $data) use ($world, $index) : void{
			if($data->canceled){
				$this->openExplosionsForm($p, $world, $index);
				return;
			}
			[$typeRaw, $colorsRaw, $fadeRaw, $twinkle, $trail] = $data->formValues;
			$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
			if($pos === null) return;
			$type = $this->resolveType($typeRaw);
			if($type === null){
				$p->sendMessage("Invalid firework type: $typeRaw");
				return;
			}
			$colors = [];
			foreach(array_map('trim', explode(',', $colorsRaw)) as $c){
				if($c === '') continue;
				$col = $this->resolveDyeColor($c);
				if($col !== null) $colors[] = $col;
			}
			if($colors === []){
				$p->sendMessage("At least one valid color required");
				return;
			}
			$fade = [];
			foreach(array_map('trim', explode(',', $fadeRaw)) as $f){
				if($f === '') continue;
				$col = $this->resolveDyeColor($f);
				if($col !== null) $fade[] = $col;
			}
			$expl = new FireworkRocketExplosion($type, $colors, $fade, (bool) $twinkle, (bool) $trail);
			$pos->explosions[] = $expl;
			$this->plugin->savePositionsToConfig();
			$p->sendMessage("Added explosion.");
			$this->openExplosionsForm($p, $world, $index);
		});
	}

	private function openEditExplosionForm(Player $player, string $world, int $index, int $explIndex) : void{
		$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
		if($pos === null) return;
		$expl = $pos->explosions[$explIndex] ?? null;
		if($expl === null) return;
		$cols = [];
		foreach($expl->getColors() as $c) $cols[] = $c->name;
		$fade = [];
		foreach($expl->getFadeColors() as $c) $fade[] = $c->name;

		$modal = ModalFormData::create()->title("Edit Explosion #$explIndex");
		$modal->textField("Shape", $expl->getType()->name, $expl->getType()->name, "The shape of the explosion. Can be small_ball, large_ball, star, creeper, or burst");
		$modal->textField("Colors (comma separated)", implode(',', $cols), implode(',', $cols), "The colors of the initial particles of the explosion, randomly selected from");
		$modal->textField("Fade (comma separated)", implode(',', $fade), implode(',', $fade), "The colors of the fading particles of the explosion, randomly selected from");
		$modal->toggle("Twinkle", default: $expl->willTwinkle(), tooltip: "Whether or not the explosion has a twinkle effect (glowstone dust)");
		$modal->toggle("Trail", default: $expl->getTrail(), tooltip: "Whether or not the explosion has a trail effect (diamond)");
		$modal->show($player)->then(function(Player $p, ModalFormResponse $data) use ($world, $index, $explIndex) : void{
			if($data->canceled){
				$this->openExplosionsForm($p, $world, $index);
				return;
			}
			[$typeRaw, $colorsRaw, $fadeRaw, $twinkle, $trail] = $data->formValues;
			$pos = $this->plugin->getPositionsByWorld()[$world][$index] ?? null;
			if($pos === null) return;
			$type = $this->resolveType($typeRaw);
			if($type === null){
				$p->sendMessage("Invalid firework type: $typeRaw");
				return;
			}
			$colors = [];
			foreach(array_map('trim', explode(',', $colorsRaw)) as $c){
				if($c === '') continue;
				$col = $this->resolveDyeColor($c);
				if($col !== null) $colors[] = $col;
			}
			if($colors === []){
				$p->sendMessage("At least one valid color required");
				return;
			}
			$fade = [];
			foreach(array_map('trim', explode(',', $fadeRaw)) as $f){
				if($f === '') continue;
				$col = $this->resolveDyeColor($f);
				if($col !== null) $fade[] = $col;
			}
			$expl = new FireworkRocketExplosion($type, $colors, $fade, (bool) $twinkle, (bool) $trail);
			$pos->explosions[$explIndex] = $expl;
			$this->plugin->savePositionsToConfig();
			$p->sendMessage("Updated explosion #$explIndex");
			$this->openExplosionsForm($p, $world, $index);
		});
	}

	private function resolveType(string $raw) : ?FireworkRocketType{
		$needle = strtolower(trim($raw));
		foreach(FireworkRocketType::cases() as $case){
			if(strtolower($case->name) === $needle) return $case;
		}
		return null;
	}

	private function resolveDyeColor(string $raw) : ?DyeColor{
		$needle = strtolower(trim($raw));
		foreach(DyeColor::cases() as $case){
			if(strtolower($case->name) === $needle) return $case;
			if(strtolower(str_replace([' ', '-', '_'], '', $case->name)) === str_replace([' ', '-', '_'], '', $needle)) return $case;
		}
		return null;
	}
}
