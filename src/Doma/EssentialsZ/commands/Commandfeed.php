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

class Commandfeed extends EssentialsLoopCommand{

	public function __construct(){
		parent::__construct("feed");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) > 0 && $user->isAuthorized("essentialsz.feed.others")){
			$this->loopOnlinePlayersConsumer($server, $user->getSource(), true, true, $args[0],
				fn(User $target) => $this->updatePlayer($server, $user->getSource(), $target, $args));
			return;
		}

		$this->feedPlayer($user->getBase());
		$user->sendTl("feed");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}

		$this->loopOnlinePlayersConsumer($server, $sender, true, true, $args[0],
			fn(User $target) => $this->updatePlayer($server, $sender, $target, $args));
	}

	protected function updatePlayer(Server $server, CommandSource $sender, User $user, array $args) : void{
		$this->feedPlayer($user->getBase());
		$user->sendTl("feed");
		if(!$sender->isPlayer() || $sender->getPlayer() !== $user->getBase()){
			$sender->sendTl("feedOther", $user->getDisplayName());
		}
	}

	private function feedPlayer(Player $player) : void{
		$hungerManager = $player->getHungerManager();
		$hungerManager->setFood(20.0);
		$hungerManager->setSaturation(10.0);
		$hungerManager->setExhaustion(0.0);
	}
}
