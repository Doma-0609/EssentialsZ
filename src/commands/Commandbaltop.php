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
use pocketmine\Server;
use function ceil;
use function count;
use function is_numeric;
use function max;
use function min;

class Commandbaltop extends EssentialsCommand{

	private const ENTRIES_PER_PAGE = 10;

	public function __construct(){
		parent::__construct("balancetop");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		$page = count($args) > 0 && is_numeric($args[0]) ? max(1, (int) $args[0]) : 1;

		$economy = $this->ess->getEconomy();
		$pages = max(1, (int) ceil($economy->countAccounts() / self::ENTRIES_PER_PAGE));
		$page = min($page, $pages);

		$sender->sendTl("balanceTop", $page . "/" . $pages);
		$rank = ($page - 1) * self::ENTRIES_PER_PAGE;
		foreach($economy->getTop(self::ENTRIES_PER_PAGE, $rank) as $account => $balance){
			$sender->sendTl("balanceTopLine", ++$rank, $account, $economy->formatMoney($balance));
		}
	}
}
