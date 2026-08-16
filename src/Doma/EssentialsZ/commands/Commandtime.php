<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\commands;

use Doma\EssentialsZ\session\CommandSource;
use Doma\EssentialsZ\utils\TickFormat;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\Server;
use pocketmine\world\World;
use function count;
use function implode;
use function is_numeric;
use function mb_strtolower;
use function preg_replace;
use function str_ends_with;
use function str_replace;
use function strtolower;
use function usort;

class Commandtime extends EssentialsCommand{

	public function __construct(){
		parent::__construct("time");
	}

	public function getAlternatePermissions() : array{
		return [
			DefaultPermissionNames::COMMAND_TIME_QUERY,
			DefaultPermissionNames::COMMAND_TIME_SET,
			DefaultPermissionNames::COMMAND_TIME_ADD
		];
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		$add = false;

		if(count($args) === 0){
			$worlds = $this->getWorlds($server, $sender, null);
			$label = strtolower($commandLabel);
			if(!str_ends_with($label, "day") && !str_ends_with($label, "night")){
				$this->sendWorldsTime($sender, $worlds);
				return;
			}
			// /day and /night (and their e-prefixed forms) set the time directly
			$ticks = TickFormat::parse(str_replace("e", "", $label));
		}elseif(self::isFlowKeyword(mb_strtolower($args[0]))){
			// /time stop|start [world] freezes or resumes the daylight cycle
			$this->toggleTimeFlow($server, $sender, mb_strtolower($args[0]), $args[1] ?? null);
			return;
		}elseif(count($args) === 1){
			$worlds = $this->getWorlds($server, $sender, null);
			$ticks = self::parseTime($args[0]);
		}elseif(mb_strtolower($args[0]) === "set" || mb_strtolower($args[0]) === "add"){
			$add = mb_strtolower($args[0]) === "add";
			$ticks = self::parseTime($args[1]);
			$worlds = $this->getWorlds($server, $sender, $args[2] ?? null);
		}else{
			$ticks = self::parseTime($args[0]);
			$worlds = $this->getWorlds($server, $sender, $args[1]);
		}

		if($ticks === null){
			throw new NotEnoughArgumentsException();
		}
		if(!$this->canSetTime($sender)){
			throw new TranslatableException("timeSetPermission");
		}
		foreach($worlds as $world){
			if(!$this->canUpdateWorld($sender, $world)){
				throw new TranslatableException("timeSetWorldPermission", $world->getDisplayName());
			}
		}

		$names = [];
		foreach($worlds as $world){
			$current = $world->getTime();
			$world->setTime($add ? $current + $ticks : $current - ($current % TickFormat::TICKS_PER_DAY) + TickFormat::TICKS_PER_DAY + $ticks);
			$names[] = $world->getDisplayName();
		}
		$sender->sendTl($add ? "timeWorldAdd" : "timeWorldSet", TickFormat::formatTicks($ticks), implode(", ", $names));
	}

	private static function isFlowKeyword(string $word) : bool{
		return $word === "stop" || $word === "freeze" || $word === "start" || $word === "resume" || $word === "unfreeze";
	}

	/**
	 * /time stop (freeze/…) halts the daylight cycle; /time start (resume/…)
	 * lets it advance again. Both accept an optional world selector.
	 */
	private function toggleTimeFlow(Server $server, CommandSource $sender, string $keyword, ?string $selector) : void{
		$stop = $keyword === "stop" || $keyword === "freeze";
		$worlds = $this->getWorlds($server, $sender, $selector);

		if(!$this->canSetTime($sender)){
			throw new TranslatableException("timeSetPermission");
		}
		foreach($worlds as $world){
			if(!$this->canUpdateWorld($sender, $world)){
				throw new TranslatableException("timeSetWorldPermission", $world->getDisplayName());
			}
		}

		$names = [];
		foreach($worlds as $world){
			if($stop){
				$world->stopTime();
			}else{
				$world->startTime();
			}
			$names[] = $world->getDisplayName();
		}
		$sender->sendTl($stop ? "timeWorldStopped" : "timeWorldStarted", implode(", ", $names));
	}

	/**
	 * A bare number is read as ticks, anything else as a time description.
	 */
	private static function parseTime(string $argument) : ?int{
		return TickFormat::parse(is_numeric($argument) ? $argument . "t" : $argument);
	}

	/**
	 * @param list<World> $worlds
	 */
	private function sendWorldsTime(CommandSource $sender, array $worlds) : void{
		foreach($worlds as $world){
			$sender->sendTl("timeWorldCurrent", $world->getDisplayName(), TickFormat::format($world->getTimeOfDay()));
		}
	}

	/**
	 * No selector means the sender's own world, or every world for the
	 * console. "*" and "all" always mean every world.
	 *
	 * @return list<World>
	 */
	private function getWorlds(Server $server, CommandSource $sender, ?string $selector) : array{
		$manager = $server->getWorldManager();

		if($selector === null){
			$player = $sender->getPlayer();
			$worlds = $player !== null ? [$player->getWorld()] : $manager->getWorlds();
		}else{
			$world = $manager->getWorldByName($selector);
			if($world !== null){
				$worlds = [$world];
			}elseif($selector === "*" || mb_strtolower($selector) === "all"){
				$worlds = $manager->getWorlds();
			}else{
				throw new TranslatableException("invalidWorld");
			}
		}

		$worlds = \array_values($worlds);
		usort($worlds, static fn(World $a, World $b) => \strcmp($a->getDisplayName(), $b->getDisplayName()));
		return $worlds;
	}

	private function canSetTime(CommandSource $sender) : bool{
		return $sender->hasPermission("essentialsz.time.set")
			|| $sender->hasPermission(DefaultPermissionNames::COMMAND_TIME_SET)
			|| $sender->hasPermission(DefaultPermissionNames::COMMAND_TIME_ADD);
	}

	private function canUpdateWorld(CommandSource $sender, World $world) : bool{
		if(!$this->ess->getSettings()->isWorldTimePermissions()
			|| $sender->hasPermission("essentialsz.time.world.all")){
			return true;
		}
		$name = (string) preg_replace("/\\s+/", "_", mb_strtolower($world->getDisplayName()));
		return $sender->hasPermission("essentialsz.time.world." . $name);
	}
}
