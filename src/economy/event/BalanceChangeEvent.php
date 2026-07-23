<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\economy\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

/**
 * Fired before any account balance is written, including account creation.
 * Cancelling the event discards the change.
 */
final class BalanceChangeEvent extends Event implements Cancellable{
	use CancellableTrait;

	/**
	 * @param string     $account    lowercased, trimmed player name
	 * @param float|null $oldBalance null when the account is being created
	 */
	public function __construct(
		private string $account,
		private ?float $oldBalance,
		private float $newBalance
	){}

	public function getAccount() : string{
		return $this->account;
	}

	public function getOldBalance() : ?float{
		return $this->oldBalance;
	}

	public function getNewBalance() : float{
		return $this->newBalance;
	}
}
