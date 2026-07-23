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
use function array_merge;
use function array_slice;
use function count;
use function explode;
use function mb_strtolower;

class Commanddelhome extends EssentialsCommand{

	public function __construct(){
		parent::__construct("delhome");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}

		$nameParts = explode(":", $args[0], 2);
		if(isset($nameParts[1])){
			$args = array_merge($nameParts, array_slice($args, 1));
		}

		$target = $user;
		if(count($args) > 1 && $user->isAuthorized("essentialsz.delhome.others")){
			$target = $this->getPlayer($server, $user->getSource(), $args[0]);
			$name = mb_strtolower($args[1]);
		}else{
			$name = mb_strtolower($args[0]);
		}
		$this->deleteHomes($user->getSource(), $target, $name);
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}

		$consoleParts = explode(":", $args[0], 2);
		if(isset($consoleParts[1])){
			$args = array_merge($consoleParts, array_slice($args, 1));
		}
		if(count($args) < 2){
			throw new NotEnoughArgumentsException();
		}
		$target = $this->getPlayer($server, $sender, $args[0]);
		$this->deleteHomes($sender, $target, mb_strtolower($args[1]));
	}

	private function deleteHomes(CommandSource $sender, User $target, string $name) : void{
		if($name === "bed"){
			throw new TranslatableException("invalidHomeName");
		}
		if($name === "*"){
			foreach($target->getHomes() as $home){
				$this->deleteHome($sender, $target, $home);
			}
			return;
		}
		if(!$target->hasHome($name)){
			throw new TranslatableException("invalidHome", $name);
		}
		$this->deleteHome($sender, $target, $name);
	}

	private function deleteHome(CommandSource $sender, User $target, string $name) : void{
		$target->delHome($name);
		$sender->sendTl("deleteHome", $name);
	}
}
