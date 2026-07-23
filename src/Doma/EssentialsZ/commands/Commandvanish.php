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
use pocketmine\Server;

class Commandvanish extends EssentialsToggleCommand{

	public function __construct(){
		parent::__construct("vanish", "essentialsz.vanish.others");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		$this->toggleOtherPlayers($server, $sender, $args);
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$this->handleToggleWithArgs($server, $user, $args);
	}

	protected function togglePlayer(CommandSource $sender, User $user, ?bool $enabled) : void{
		$enabled ??= !$user->isVanished();

		$user->setVanished($enabled);

		$state = $user->playerTl($enabled ? "enabled" : "disabled");
		$user->sendTl("vanish", $user->getDisplayName(), $state);
		if($enabled){
			$user->sendTl("vanished");
		}
		if(!$sender->isPlayer() || $sender->getPlayer() !== $user->getBase()){
			$sender->sendTl("vanish", $user->getDisplayName(), $sender->tl($enabled ? "enabled" : "disabled"));
		}
	}
}
