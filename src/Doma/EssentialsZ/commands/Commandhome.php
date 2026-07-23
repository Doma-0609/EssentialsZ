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

use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function count;
use function explode;
use function implode;
use function mb_strtolower;

class Commandhome extends EssentialsCommand{

	public function __construct(){
		parent::__construct("home");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$player = $user;
		$homeName = "";
		if(count($args) > 0){
			$nameParts = explode(":", $args[0], 2);
			if(!isset($nameParts[1]) || !$user->isAuthorized("essentialsz.home.others")){
				$homeName = mb_strtolower($nameParts[0]);
			}else{
				$player = $this->getPlayer($server, $user->getSource(), $nameParts[0]);
				$homeName = mb_strtolower($nameParts[1]);
			}
		}

		if($homeName === ""){
			$homes = $player->getHomes();
			if($homes === []){
				throw new TranslatableException("noHomeSetPlayer");
			}
			if(count($homes) === 1 && $player === $user){
				$this->goHome($user, $player, $homes[0]);
				return;
			}
			$user->sendTl("homes", implode(", ", $homes), count($homes), $this->ess->getSettings()->getMaxHomes());
			return;
		}
		$this->goHome($user, $player, $homeName);
	}

	private function goHome(User $user, User $player, string $name) : void{
		if(!$player->hasHome($name)){
			throw new TranslatableException("invalidHome", $name);
		}
		$location = $player->getHome($name);
		if($location === null){
			throw new TranslatableException("invalidWorld");
		}
		$user->getBase()->teleport($location);
		$user->sendTl("teleportHome", $name);
	}
}
