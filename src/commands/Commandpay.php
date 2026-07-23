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

use Doma\EssentialsZ\economy\EconomyForms;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function count;
use function is_numeric;
use function mb_strtolower;

class Commandpay extends EssentialsCommand{

	public function __construct(){
		parent::__construct("pay");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			(new EconomyForms($this->ess))->openPayForm($user);
			return;
		}
		if(count($args) < 2){
			throw new NotEnoughArgumentsException();
		}
		if(!is_numeric($args[1])){
			throw new NotEnoughArgumentsException();
		}
		$amount = (float) $args[1];

		$economy = $this->ess->getEconomy();
		$target = $server->getPlayerExact($args[0]) ?? $server->getPlayerByPrefix($args[0]);
		$toName = $target !== null ? $target->getName() : $args[0];

		if(mb_strtolower($toName) === mb_strtolower($user->getName())){
			throw new TranslatableException("cantPayYourself");
		}
		if($target === null){
			if(!$economy->hasAccount($toName)){
				throw new PlayerNotFoundException();
			}
			if(!$economy->getSettings()->allowPayOffline){
				throw new TranslatableException("payOffline");
			}
		}
		self::pay($this->ess, $user, $toName, $target !== null ? $target->getDisplayName() : $toName, $amount);
	}

	/**
	 * Transfers money from a player to any account (online or offline),
	 * notifying the recipient when they are online. Shared with the pay UI.
	 */
	public static function pay(IEssentials $ess, User $from, string $toName, string $toDisplayName, float $amount) : void{
		$economy = $ess->getEconomy();
		if($amount <= 0.0){
			throw new TranslatableException("payMustBePositive");
		}
		if($amount < $economy->getSettings()->minPayAmount){
			throw new TranslatableException("payTooSmall", $economy->formatMoney($economy->getSettings()->minPayAmount));
		}
		if(!$economy->hasBalance($from->getName(), $amount)){
			throw new TranslatableException("notEnoughMoney");
		}
		if(!$economy->transfer($from->getName(), $toName, $amount)){
			throw new TranslatableException("maxMoney");
		}
		$from->sendTl("moneySentTo", $economy->formatMoney($amount), $toDisplayName);

		$online = $ess->getServer()->getPlayerExact($toName);
		if($online !== null){
			$ess->getUser($online)->sendTl("moneyRecievedFrom", $economy->formatMoney($amount), $from->getDisplayName());
		}
	}
}
