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
use function count;

class Commandafk extends EssentialsCommand{

	public function __construct(){
		parent::__construct("afk");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) > 0 && $user->isAuthorized("essentialsz.afk.others")){
			$afkUser = $user;
			try{
				$afkUser = $this->getPlayer($server, $user->getSource(), $args[0]);
				$message = count($args) > 1 ? self::getFinalArg($args, 1) : null;
			}catch(PlayerNotFoundException $e){
				$message = self::getFinalArg($args, 0);
			}
			$this->toggleAfk($user, $afkUser, $message === "" ? null : $message);
		}else{
			$message = count($args) > 0 ? self::getFinalArg($args, 0) : null;
			$this->toggleAfk($user, $user, $message);
		}
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}
		$afkUser = $this->getPlayer($server, $sender, $args[0]);
		$message = count($args) > 1 ? self::getFinalArg($args, 1) : null;
		$this->toggleAfk(null, $afkUser, $message);
	}

	private function toggleAfk(?User $sender, User $user, ?string $message) : void{
		if($message !== null && $sender !== null && !$sender->isAuthorized("essentialsz.afk.message")){
			throw new TranslatableException("noPermToAFKMessage");
		}

		$user->updateAfkStatus(!$user->isAfk(), $message);
	}
}
