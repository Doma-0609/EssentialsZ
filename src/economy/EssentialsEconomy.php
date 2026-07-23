<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\economy;

use Doma\EssentialsZ\economy\event\BalanceChangeEvent;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\storage\PlayerData;
use Doma\EssentialsZ\session\UserStorageKey;
use function arsort;
use function array_slice;
use function is_numeric;
use function mb_strtolower;
use function number_format;
use function round;
use function trim;

/**
 * The EssentialsZ economy API.
 *
 * Balances live in each player's shared storage record under "money", so an
 * account exists once a player has joined (or once a balance is set). Obtain
 * this through EssentialsZ::getEconomy(), which returns null when the economy
 * module is disabled in the config - always null-check before use.
 *
 * Amounts are rounded to the configured number of decimals and clamped to
 * [0, max-money]; every write fires a cancellable BalanceChangeEvent. Offline
 * players are reachable by name only while user-storage-key is "name".
 */
final class EssentialsEconomy{

	private const FIELD = "money";

	public function __construct(
		private IEssentials $ess,
		private EconomySettings $settings
	){}

	public function getSettings() : EconomySettings{
		return $this->settings;
	}

	public function hasAccount(string $player) : bool{
		$data = $this->dataFor($player);
		return $data !== null && $data->hasProperty(self::FIELD);
	}

	/**
	 * @return float|null null when the player has no account
	 */
	public function getBalance(string $player) : ?float{
		$data = $this->dataFor($player);
		if($data === null || !$data->hasProperty(self::FIELD)){
			return null;
		}
		return $data->getDouble(self::FIELD);
	}

	public function hasBalance(string $player, float $amount) : bool{
		$balance = $this->getBalance($player);
		return $balance !== null && $balance >= $this->round($amount);
	}

	/**
	 * Creates the account with the given balance (start-money when null).
	 * Returns false when it already exists or cannot be resolved.
	 */
	public function createAccount(string $player, ?float $balance = null) : bool{
		$data = $this->dataFor($player);
		if($data === null || $data->hasProperty(self::FIELD)){
			return false;
		}
		return $this->write($data, $balance ?? $this->settings->startMoney);
	}

	/**
	 * Sets the balance, creating the account when missing. Returns false when
	 * out of range, unresolvable, or cancelled.
	 */
	public function setBalance(string $player, float $amount) : bool{
		$amount = $this->round($amount);
		$data = $this->dataFor($player);
		if($data === null || $amount < 0.0 || $amount > $this->settings->maxMoney){
			return false;
		}
		return $this->write($data, $amount);
	}

	/**
	 * Adds to an existing account. Returns false when the account is missing,
	 * the result exceeds max-money, or the change was cancelled.
	 */
	public function addBalance(string $player, float $amount) : bool{
		$amount = $this->round($amount);
		$data = $this->dataFor($player);
		if($data === null || !$data->hasProperty(self::FIELD)){
			return false;
		}
		$balance = $data->getDouble(self::FIELD);
		if($amount <= 0.0 || $balance + $amount > $this->settings->maxMoney){
			return false;
		}
		return $this->write($data, $balance + $amount);
	}

	/**
	 * Subtracts from an existing account. Returns false when the account is
	 * missing, funds are insufficient, or the change was cancelled.
	 */
	public function subtractBalance(string $player, float $amount) : bool{
		$amount = $this->round($amount);
		$data = $this->dataFor($player);
		if($data === null || !$data->hasProperty(self::FIELD)){
			return false;
		}
		$balance = $data->getDouble(self::FIELD);
		if($amount <= 0.0 || $balance - $amount < 0.0){
			return false;
		}
		return $this->write($data, $balance - $amount);
	}

	/**
	 * Moves money between two existing accounts atomically.
	 */
	public function transfer(string $from, string $to, float $amount) : bool{
		$amount = $this->round($amount);
		$fromData = $this->dataFor($from);
		$toData = $this->dataFor($to);
		if($fromData === null || $toData === null || !$fromData->hasProperty(self::FIELD) || !$toData->hasProperty(self::FIELD)){
			return false;
		}
		$fromBalance = $fromData->getDouble(self::FIELD);
		$toBalance = $toData->getDouble(self::FIELD);
		if($amount <= 0.0 || $fromBalance - $amount < 0.0 || $toBalance + $amount > $this->settings->maxMoney){
			return false;
		}
		if(!$this->write($fromData, $fromBalance - $amount)){
			return false;
		}
		if(!$this->write($toData, $toBalance + $amount)){
			$this->write($fromData, $fromBalance);
			return false;
		}
		return true;
	}

	/**
	 * @return array<string, float> display name => balance, highest first
	 */
	public function getTop(int $limit = 10, int $offset = 0) : array{
		$balances = [];
		foreach($this->ess->getDataProvider()->getAll() as $key => $record){
			if(isset($record[self::FIELD]) && is_numeric($record[self::FIELD])){
				$label = isset($record["last-account-name"]) ? (string) $record["last-account-name"] : $key;
				$balances[$label] = (float) $record[self::FIELD];
			}
		}
		arsort($balances);
		return array_slice($balances, $offset, $limit, true);
	}

	public function countAccounts() : int{
		$count = 0;
		foreach($this->ess->getDataProvider()->getAll() as $record){
			if(isset($record[self::FIELD])){
				$count++;
			}
		}
		return $count;
	}

	/**
	 * 1-based wealth rank of the player (1 = richest), or null when they have
	 * no account.
	 */
	public function getRank(string $player) : ?int{
		$balance = $this->getBalance($player);
		if($balance === null){
			return null;
		}
		$rank = 1;
		foreach($this->ess->getDataProvider()->getAll() as $record){
			if(isset($record[self::FIELD]) && is_numeric($record[self::FIELD]) && (float) $record[self::FIELD] > $balance){
				$rank++;
			}
		}
		return $rank;
	}

	/**
	 * Sum of every account's balance.
	 */
	public function getTotalMoney() : float{
		$total = 0.0;
		foreach($this->ess->getDataProvider()->getAll() as $record){
			if(isset($record[self::FIELD]) && is_numeric($record[self::FIELD])){
				$total += (float) $record[self::FIELD];
			}
		}
		return $total;
	}

	/**
	 * Formats an amount with the currency symbol, e.g. 1234.5 -> "$1,234.50".
	 */
	public function formatMoney(float $amount) : string{
		return $this->settings->currencySymbol . number_format($amount, $this->settings->decimals, ".", ",");
	}

	private function round(float $amount) : float{
		return round($amount, $this->settings->decimals);
	}

	private function write(PlayerData $data, float $balance) : bool{
		$balance = $this->round($balance);
		$old = $data->hasProperty(self::FIELD) ? $data->getDouble(self::FIELD) : null;
		$event = new BalanceChangeEvent($data->getStorageKey(), $old, $balance);
		$event->call();
		if($event->isCancelled()){
			return false;
		}
		$data->setProperty(self::FIELD, $balance);
		$data->save();
		return true;
	}

	/**
	 * Resolves a player's record. Online players use their live (cached)
	 * record; offline players resolve by name only under the "name" storage
	 * key, returning null otherwise.
	 */
	private function dataFor(string $player) : ?PlayerData{
		$online = $this->ess->getServer()->getPlayerExact($player);
		if($online !== null){
			return $this->ess->getUser($online)->getData();
		}
		if($this->ess->getSettings()->getUserStorageKey() !== UserStorageKey::NAME){
			return null;
		}
		$key = mb_strtolower(trim($player));
		if($key === ""){
			return null;
		}
		$cached = $this->ess->getUsers()->getCachedUser($key);
		return $cached !== null ? $cached->getData() : new PlayerData($this->ess->getDataProvider(), $key);
	}
}
