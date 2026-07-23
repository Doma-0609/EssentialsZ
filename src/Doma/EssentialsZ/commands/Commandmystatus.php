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
use function round;

class Commandmystatus extends EssentialsCommand{

	public function __construct(){
		parent::__construct("mystatus");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$economy = $this->ess->getEconomy();
		$name = $user->getName();
		$balance = $economy->getBalance($name) ?? 0.0;
		$total = $economy->getTotalMoney();
		$percent = $total > 0.0 ? round($balance / $total * 100, 2) : 0.0;
		$rank = $economy->getRank($name) ?? $economy->countAccounts();

		$user->sendTl("mystatus", $rank, $economy->formatMoney($balance), $percent);
	}
}
