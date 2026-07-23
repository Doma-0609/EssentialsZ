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

class Commandbalance extends EssentialsCommand{

	public function __construct(){
		parent::__construct("balance");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			$economy = $this->ess->getEconomy();
			$user->sendTl("balance", $economy->formatMoney($economy->getBalance($user->getName()) ?? 0.0));
			return;
		}
		if(!$user->isAuthorized("essentialsz.balance.others")){
			throw new TranslatableException("noAccessCommand");
		}
		$this->sendOtherBalance($server, $user->getSource(), $args[0]);
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			throw new NotEnoughArgumentsException();
		}
		$this->sendOtherBalance($server, $sender, $args[0]);
	}

	private function sendOtherBalance(Server $server, CommandSource $sender, string $name) : void{
		$economy = $this->ess->getEconomy();
		try{
			$target = $this->getPlayer($server, $sender, $name);
			$sender->sendTl("balanceOther", $target->getDisplayName(), $economy->formatMoney($economy->getBalance($target->getName()) ?? 0.0));
		}catch(PlayerNotFoundException $e){
			$balance = $economy->getBalance($name);
			if($balance === null){
				throw $e;
			}
			$sender->sendTl("balanceOther", $name, $economy->formatMoney($balance));
		}
	}
}
