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
use function is_numeric;

/**
 * Shared logic for the balance-admin commands (/eco and the standalone
 * /givemoney, /takemoney, /setmoney). Online targets are resolved by name,
 * offline targets need an existing account.
 */
abstract class EconomyAdminCommand extends EssentialsCommand{

	protected function applyOperation(Server $server, CommandSource $sender, string $op, string $targetArg, ?string $amountArg) : void{
		$economy = $this->ess->getEconomy();

		$target = null;
		try{
			$target = $this->getPlayer($server, $sender, $targetArg);
			$name = $target->getName();
			$displayName = $target->getDisplayName();
		}catch(PlayerNotFoundException $e){
			if(!$economy->hasAccount($targetArg)){
				throw $e;
			}
			$name = $targetArg;
			$displayName = $targetArg;
		}
		$economy->createAccount($name);

		if($op === "reset"){
			$amount = $economy->getSettings()->startMoney;
		}else{
			if($amountArg === null || !is_numeric($amountArg)){
				throw new NotEnoughArgumentsException();
			}
			$amount = (float) $amountArg;
			if($amount < 0.0){
				throw new TranslatableException("payMustBePositive");
			}
		}

		switch($op){
			case "give":
				if(!$economy->addBalance($name, $amount)){
					throw new TranslatableException("maxMoney");
				}
				$sender->sendTl("addedToOthersAccount", $economy->formatMoney($amount), $displayName, $economy->formatMoney($economy->getBalance($name) ?? 0.0));
				$target?->sendTl("addedToAccount", $economy->formatMoney($amount));
				break;
			case "take":
				if(!$economy->subtractBalance($name, $amount)){
					throw new TranslatableException("negativeBalanceError");
				}
				$sender->sendTl("takenFromOthersAccount", $economy->formatMoney($amount), $displayName, $economy->formatMoney($economy->getBalance($name) ?? 0.0));
				$target?->sendTl("takenFromAccount", $economy->formatMoney($amount));
				break;
			case "set":
			case "reset":
				if(!$economy->setBalance($name, $amount)){
					throw new TranslatableException("maxMoney");
				}
				$sender->sendTl("setBalOthers", $displayName, $economy->formatMoney($amount));
				$target?->sendTl("setBal", $economy->formatMoney($amount));
				break;
			default:
				throw new NotEnoughArgumentsException();
		}
	}
}
