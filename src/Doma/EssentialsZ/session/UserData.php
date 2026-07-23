<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\session;

use Doma\EssentialsZ\storage\PlayerData;
use Doma\EssentialsZ\session\UserStorageKey;
use Doma\EssentialsZ\utils\LocationUtil;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use function array_keys;
use function array_unshift;
use function mb_strtolower;
use Doma\EssentialsZ\IEssentials;

/**
 * Persistent per-player data. The whole record lives in one storage entry
 * (see the DataProvider) keyed by the user-storage-key config option
 * (name, uuid or xuid): last-account-name, ip-address, timestamps, homes,
 * money, kit cooldowns and the Xbox user id (xuid).
 */
abstract class UserData extends PlayerExtension{

	protected IEssentials $ess;
	protected PlayerData $config;

	protected function __construct(Player $base, IEssentials $ess){
		parent::__construct($base);
		$this->ess = $ess;

		$key = UserStorageKey::resolve($ess->getSettings(), $base);
		$this->config = new PlayerData($ess->getDataProvider(), $key);
	}

	public function getData() : PlayerData{
		return $this->config;
	}

	public function getStorageKey() : string{
		return $this->config->getStorageKey();
	}

	public function reloadConfig() : void{
		$this->config->reload();
	}

	public function save() : void{
		$this->config->save();
	}

	public function getLastLogin() : int{
		return $this->config->getLong("timestamps.login", 0);
	}

	/**
	 * Sets the login timestamp (milliseconds) and refreshes the stored
	 * IP address at the same time.
	 */
	public function setLastLogin(int $time) : void{
		$this->config->setProperty("timestamps.login", $time);
		if($this->base->isConnected()){
			$this->config->setProperty("ip-address", $this->base->getNetworkSession()->getIp());
		}
	}

	public function getLastLogout() : int{
		return $this->config->getLong("timestamps.logout", 0);
	}

	public function setLastLogout(int $time) : void{
		$this->config->setProperty("timestamps.logout", $time);
		$this->save();
	}

	public function getLastLoginAddress() : string{
		return $this->config->getString("ip-address", "") ?? "";
	}

	public function getLastAccountName() : ?string{
		return $this->config->getString("last-account-name", null);
	}

	public function setLastAccountName(string $lastAccountName) : void{
		$previous = $this->getLastAccountName();
		if($previous !== null && $previous !== $lastAccountName){
			$usernames = $this->getPastUsernames();
			array_unshift($usernames, $previous);
			$this->setPastUsernames($usernames);
		}
		$this->config->setProperty("last-account-name", $lastAccountName);
	}

	/**
	 * @return list<string>
	 */
	public function getPastUsernames() : array{
		return $this->config->getList("past-usernames", []);
	}

	/**
	 * @param list<string> $usernames
	 */
	public function setPastUsernames(array $usernames) : void{
		$this->config->setProperty("past-usernames", $usernames);
	}

	/**
	 * Bedrock-specific: the Xbox user id of the account.
	 */
	public function getXuid() : string{
		return $this->config->getString("xuid", "") ?? "";
	}

	public function setXuid(string $xuid) : void{
		if($xuid !== ""){
			$this->config->setProperty("xuid", $xuid);
		}
	}

	/**
	 * @return list<string>
	 */
	public function getHomes() : array{
		return array_keys($this->config->getMap("homes"));
	}

	public function hasHome(string $name) : bool{
		return $this->config->getMap("homes." . $name) !== [];
	}

	public function getHome(string $name) : ?Location{
		return LocationUtil::fromMap($this->config->getMap("homes." . $name), $this->getServer());
	}

	public function setHome(string $name, Location $location) : void{
		$this->config->setProperty("homes." . $name, LocationUtil::toMap($location));
		$this->save();
	}

	public function delHome(string $name) : void{
		$this->config->removeProperty("homes." . $name);
		$this->save();
	}

	public function getKitTimestamp(string $name) : int{
		return $this->config->getLong("timestamps.kits." . mb_strtolower($name), 0);
	}

	public function setKitTimestamp(string $name, int $time) : void{
		$this->config->setProperty("timestamps.kits." . mb_strtolower($name), $time);
		$this->save();
	}

	public function getLastDeathLocation() : ?Location{
		return LocationUtil::fromMap($this->config->getMap("last-death-location"), $this->getServer());
	}

	public function getLastDeathTime() : int{
		return $this->config->getLong("timestamps.death", 0);
	}

	public function setLastDeathLocation(Location $location, int $time) : void{
		$this->config->setProperty("last-death-location", LocationUtil::toMap($location));
		$this->config->setProperty("timestamps.death", $time);
		$this->save();
	}
}
