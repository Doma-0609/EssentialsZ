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
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use pocketmine\Server;
use function count;
use function is_numeric;
use function str_contains;
use function strlen;
use function strtolower;
use function trim;

class Commandspeed extends EssentialsCommand{

	private const DEFAULT_FLY_SPEED = Player::DEFAULT_FLIGHT_SPEED_MULTIPLIER;
	private const DEFAULT_WALK_SPEED = 0.1;

	public function __construct(){
		parent::__construct("speed");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 3){
			throw new NotEnoughArgumentsException();
		}
		$this->speedOtherPlayers($server, $sender, $this->isFlyMode($args[0]), true, $this->getMoveSpeed($args[1]), $args[2]);
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}

		$isBypass = $user->isAuthorized("essentialsz.speed.bypass");
		if(count($args) === 1){
			$inferredFly = $this->isFlyAlias($commandLabel)
				? true
				: ($this->isWalkAlias($commandLabel) ? false : $user->getBase()->isFlying());
			$isFly = $this->flyPermCheck($user, $inferredFly);
			$speed = $this->getMoveSpeed($args[0]);
		}else{
			$isFly = $this->flyPermCheck($user, $this->isFlyMode($args[0]));
			$speed = $this->getMoveSpeed($args[1]);
			if(count($args) > 2 && $user->isAuthorized("essentialsz.speed.others")){
				if(strlen(trim($args[2])) < 2){
					throw new PlayerNotFoundException();
				}
				$this->speedOtherPlayers($server, $user->getSource(), $isFly, $isBypass, $speed, $args[2]);
				return;
			}
		}

		if($isFly){
			$user->getBase()->setFlightSpeedMultiplier($this->getRealMoveSpeed($speed, true, $isBypass));
			$user->sendTl("moveSpeed", $user->playerTl("flying"), $speed, $user->getDisplayName());
			return;
		}
		$user->getBase()->setMovementSpeed($this->getRealMoveSpeed($speed, false, $isBypass));
		$user->sendTl("moveSpeed", $user->playerTl("walking"), $speed, $user->getDisplayName());
	}

	private function speedOtherPlayers(Server $server, CommandSource $sender, bool $isFly, bool $isBypass, float $speed, string $name) : void{
		$foundUser = false;
		foreach($this->matchPlayers($server, $name, $sender) as $matchPlayer){
			$foundUser = true;
			if($isFly){
				$matchPlayer->setFlightSpeedMultiplier($this->getRealMoveSpeed($speed, true, $isBypass));
				$sender->sendTl("moveSpeed", $sender->tl("flying"), $speed, $matchPlayer->getDisplayName());
			}else{
				$matchPlayer->setMovementSpeed($this->getRealMoveSpeed($speed, false, $isBypass));
				$sender->sendTl("moveSpeed", $sender->tl("walking"), $speed, $matchPlayer->getDisplayName());
			}
		}
		if(!$foundUser){
			throw new PlayerNotFoundException();
		}
	}

	private function flyPermCheck(User $user, bool $input) : bool{
		$canFly = $user->isAuthorized("essentialsz.speed.fly");
		$canWalk = $user->isAuthorized("essentialsz.speed.walk");
		if(($input && $canFly) || (!$input && $canWalk) || (!$canFly && !$canWalk)){
			return $input;
		}
		return !$canWalk;
	}

	private function isFlyAlias(string $label) : bool{
		$label = strtolower($label);
		return str_contains($label, "fly") || $label === "fspeed" || $label === "efspeed";
	}

	private function isWalkAlias(string $label) : bool{
		$label = strtolower($label);
		return str_contains($label, "walk") || $label === "wspeed" || $label === "ewspeed";
	}

	private function isFlyMode(string $modeString) : bool{
		$modeString = strtolower($modeString);
		if(str_contains($modeString, "fly") || $modeString === "f"){
			return true;
		}elseif(str_contains($modeString, "walk") || str_contains($modeString, "run") || $modeString === "w" || $modeString === "r"){
			return false;
		}
		throw new NotEnoughArgumentsException();
	}

	private function getMoveSpeed(string $moveSpeed) : float{
		if(!is_numeric($moveSpeed)){
			throw new NotEnoughArgumentsException();
		}
		$userSpeed = (float) $moveSpeed;
		if($userSpeed > 10.0){
			$userSpeed = 10.0;
		}elseif($userSpeed < 0.0001){
			$userSpeed = 0.0001;
		}
		return $userSpeed;
	}

	private function getRealMoveSpeed(float $userSpeed, bool $isFly, bool $isBypass) : float{
		$defaultSpeed = $isFly ? self::DEFAULT_FLY_SPEED : self::DEFAULT_WALK_SPEED;
		$maxSpeed = 1.0;
		if(!$isBypass){
			$maxSpeed = $isFly ? $this->ess->getSettings()->getMaxFlySpeed() : $this->ess->getSettings()->getMaxWalkSpeed();
		}

		if($userSpeed < 1.0){
			return $defaultSpeed * $userSpeed;
		}
		return (($userSpeed - 1.0) / 9.0) * ($maxSpeed - $defaultSpeed) + $defaultSpeed;
	}
}
