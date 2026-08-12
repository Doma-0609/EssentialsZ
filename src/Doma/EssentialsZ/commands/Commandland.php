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

use Doma\EssentialsZ\land\Land;
use Doma\EssentialsZ\land\LandBorder;
use Doma\EssentialsZ\land\LandForm;
use Doma\EssentialsZ\land\LandManager;
use Doma\EssentialsZ\session\User;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_shift;
use function count;
use function implode;
use function intdiv;
use function is_numeric;
use function mb_strtolower;

class Commandland extends EssentialsCommand{

	public function __construct(){
		parent::__construct("land");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$land = $this->ess->getLand();
		$sub = count($args) > 0 ? mb_strtolower($args[0]) : "";
		$rest = \array_slice($args, 1);

		match($sub){
			"" => LandForm::openMenu($this->ess, $user),
			"pos1", "startp" => $this->setPos($land, $user, true),
			"pos2", "endp" => $this->setPos($land, $user, false),
			"buy" => $this->buy($land, $user),
			"here" => $this->here($land, $user),
			"list" => $this->listLands($land, $user),
			"whose" => $this->whose($land, $user, $rest[0] ?? ""),
			"move" => $this->move($server, $land, $user, $rest[0] ?? null),
			"give" => $this->give($server, $land, $user, $rest),
			"sell" => $this->sell($land, $user, $rest),
			"invite" => $this->invite($land, $user, $rest),
			"kick" => $this->kick($land, $user, $rest),
			"invitee" => $this->invitee($land, $user, $rest),
			default => throw new NotEnoughArgumentsException()
		};
	}

	public function setPos(LandManager $land, User $user, bool $first) : void{
		$position = $user->getBase()->getPosition();
		$land->setSelection($user->getName(), $user->getBase()->getWorld()->getFolderName(), $first, $position->getFloorX(), $position->getFloorZ());
		$user->sendTl($first ? "landPos1" : "landPos2", $position->getFloorX(), $position->getFloorZ());
	}

	private function buy(LandManager $land, User $user) : void{
		$selection = $land->getSelection($user->getName());
		if($selection === null || $selection["world"] !== $user->getBase()->getWorld()->getFolderName()){
			throw new TranslatableException("landNoSelection");
		}
		[$x1, $z1] = $selection["first"];
		[$x2, $z2] = $selection["second"];
		$world = $selection["world"];
		$result = $land->buy($user->getBase(), $world, $x1, $z1, $x2, $z2);
		match($result){
			LandManager::RESULT_OK => $this->onBought($land, $user, $x1, $z1, $x2, $z2),
			LandManager::RESULT_OVERLAP => $user->sendTl("landOverlap"),
			LandManager::RESULT_TOO_SMALL => $user->sendTl("landTooSmall", $land->getSettings()->minSize),
			LandManager::RESULT_TOO_BIG => $user->sendTl("landTooBig", $land->getSettings()->maxSize),
			LandManager::RESULT_LIMIT => $user->sendTl("landLimit", $land->getSettings()->maxPerPlayer),
			LandManager::RESULT_WORLD_DISALLOWED => $user->sendTl("landWorldDisallowed"),
			LandManager::RESULT_NOT_ENOUGH_MONEY => $user->sendTl("notEnoughMoney"),
			LandManager::RESULT_NO_ECONOMY => $user->sendTl("landNoEconomy"),
			default => null
		};
	}

	private function onBought(LandManager $land, User $user, int $x1, int $z1, int $x2, int $z2) : void{
		$land->clearSelection($user->getName());
		$blocks = (\abs($x2 - $x1) + 1) * (\abs($z2 - $z1) + 1);
		$user->sendTl("landBought", $blocks);
	}

	private function here(LandManager $land, User $user) : void{
		$found = $this->landHere($land, $user->getBase());
		if($found === null){
			$user->sendTl("landHereNone");
			return;
		}
		$user->sendTl("landHere", $found->id, $found->owner, $found->minX, $found->minZ, $found->maxX, $found->maxZ);
		if($land->getSettings()->showBorder){
			LandBorder::show($user->getBase(), $found);
		}
	}

	private function listLands(LandManager $land, User $user) : void{
		$lands = $land->getLandsOfOwner($user->getName());
		if($lands === []){
			$user->sendTl("landListEmpty");
			return;
		}
		$user->sendTl("landListHeader", count($lands));
		foreach($lands as $entry){
			$user->sendTl("landListEntry", $entry->id, $entry->world, $entry->minX, $entry->minZ, $entry->maxX, $entry->maxZ);
		}
	}

	private function whose(LandManager $land, User $user, string $keyword) : void{
		$lands = $land->getLandsByKeyword($keyword);
		if($lands === []){
			$user->sendTl("landWhoseEmpty");
			return;
		}
		$user->sendTl("landWhoseHeader", count($lands));
		foreach($lands as $entry){
			$user->sendTl("landWhoseEntry", $entry->id, $entry->owner, $entry->world, $entry->minX, $entry->minZ, $entry->maxX, $entry->maxZ);
		}
	}

	private function move(Server $server, LandManager $land, User $user, ?string $numArg) : void{
		if(!$land->getSettings()->allowMove){
			throw new TranslatableException("landMoveDisabled");
		}
		if($numArg === null || !is_numeric($numArg)){
			throw new NotEnoughArgumentsException();
		}
		$found = $land->getLandById((int) $numArg);
		if($found === null){
			throw new TranslatableException("landInvalidLand");
		}
		$world = $server->getWorldManager()->getWorldByName($found->world);
		if($world === null){
			throw new TranslatableException("invalidWorld");
		}
		$cx = intdiv($found->minX + $found->maxX, 2);
		$cz = intdiv($found->minZ + $found->maxZ, 2);
		$y = ($world->getHighestBlockAt($cx, $cz) ?? $world->getSpawnLocation()->getFloorY()) + 1;
		$user->getBase()->teleport($world->getSafeSpawn(new Vector3($cx + 0.5, $y, $cz + 0.5)));
		$user->sendTl("landMoved", $found->id);
	}

	/**
	 * @param list<string> $rest [player, num?]
	 */
	private function give(Server $server, LandManager $land, User $user, array $rest) : void{
		if(!isset($rest[0])){
			throw new NotEnoughArgumentsException();
		}
		$target = $this->getPlayer($server, $user->getSource(), $rest[0]);
		if($target->getBase() === $user->getBase()){
			throw new TranslatableException("landCantGiveSelf");
		}
		$found = $this->ownedLandFrom($land, $user, \array_slice($rest, 1));
		$land->setOwner($found, $target->getName());
		$user->sendTl("landGiven", $found->id, $target->getDisplayName());
		$target->sendTl("landReceived", $found->id, $user->getDisplayName());
	}

	/**
	 * @param list<string> $rest [num?]
	 */
	private function sell(LandManager $land, User $user, array $rest) : void{
		$found = $this->ownedLandFrom($land, $user, $rest);
		$land->remove($found);
		$user->sendTl("landSold", $found->id);
	}

	/**
	 * @param list<string> $rest [num?, player, level?] or [player, level?]
	 */
	private function invite(LandManager $land, User $user, array $rest) : void{
		[$found, $rest] = $this->landAndRest($land, $user, $rest);
		$player = array_shift($rest);
		if($player === null){
			throw new NotEnoughArgumentsException();
		}
		$level = ($rest[0] ?? null) !== null && mb_strtolower($rest[0]) === Land::LEVEL_CONTAINER ? Land::LEVEL_CONTAINER : Land::LEVEL_BUILD;
		$land->setInvited($found, $player, $level);
		$user->sendTl("landInvited", $player, $found->id);
	}

	/**
	 * @param list<string> $rest [num?, player]
	 */
	private function kick(LandManager $land, User $user, array $rest) : void{
		[$found, $rest] = $this->landAndRest($land, $user, $rest);
		$player = array_shift($rest);
		if($player === null){
			throw new NotEnoughArgumentsException();
		}
		$land->setInvited($found, $player, null);
		$user->sendTl("landKicked", $player, $found->id);
	}

	/**
	 * @param list<string> $rest [num?]
	 */
	private function invitee(LandManager $land, User $user, array $rest) : void{
		$found = $this->ownedLandFrom($land, $user, $rest);
		$names = [];
		foreach($found->invitees as $name => $level){
			$names[] = $name . " (" . $level . ")";
		}
		$user->sendTl("landInvitees", $names === [] ? "-" : implode(", ", $names));
	}

	/**
	 * Resolves a land the player owns: a leading numeric id, otherwise the land
	 * they are standing in.
	 *
	 * @param list<string> $rest
	 */
	private function ownedLandFrom(LandManager $land, User $user, array $rest) : Land{
		[$found] = $this->landAndRest($land, $user, $rest);
		return $found;
	}

	/**
	 * @param list<string> $rest
	 * @return array{Land, list<string>}
	 */
	private function landAndRest(LandManager $land, User $user, array $rest) : array{
		if(isset($rest[0]) && is_numeric($rest[0])){
			$found = $land->getLandById((int) $rest[0]);
			array_shift($rest);
		}else{
			$found = $this->landHere($land, $user->getBase());
		}
		if($found === null){
			throw new TranslatableException("landInvalidLand");
		}
		if(!$found->isOwner($user->getName()) && !$user->isAuthorized("essentialsz.land.bypass")){
			throw new TranslatableException("landNotYours");
		}
		return [$found, $rest];
	}

	private function landHere(LandManager $land, Player $player) : ?Land{
		$position = $player->getPosition();
		return $land->getLandAt($player->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorZ());
	}
}
