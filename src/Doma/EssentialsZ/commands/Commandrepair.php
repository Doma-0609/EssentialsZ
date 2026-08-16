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
use pocketmine\item\Durable;
use pocketmine\Server;

class Commandrepair extends EssentialsCommand{

	public function __construct(){
		parent::__construct("repair");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$inventory = $user->getBase()->getInventory();
		$item = $inventory->getItemInHand();

		if($item->isNull()){
			throw new TranslatableException("repairNoItem");
		}
		if(!($item instanceof Durable)){
			throw new TranslatableException("repairInvalidItem");
		}
		if($item->getDamage() <= 0){
			throw new TranslatableException("repairNotDamaged");
		}

		$item->setDamage(0);
		$inventory->setItemInHand($item);
		$user->sendTl("repaired", $item->getName());
	}
}
