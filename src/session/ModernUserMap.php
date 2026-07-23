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

use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use function count;
use function is_string;

/**
 * Cache of live User sessions, keyed by the configured user-storage-key.
 */
final class ModernUserMap{

	/** @var array<string, User> */
	private array $userCache = [];

	public function __construct(private IEssentials $ess){}

	public function getUser(Player $base) : User{
		$key = UserStorageKey::resolve($this->ess->getSettings(), $base);
		$user = $this->userCache[$key] ?? null;
		if($user !== null && $user->getBase() === $base){
			return $user;
		}
		return $this->userCache[$key] = new User($base, $this->ess);
	}

	public function getCachedUser(string $key) : ?User{
		return $this->userCache[$key] ?? null;
	}

	public function invalidate(Player|string $key) : void{
		if(!is_string($key)){
			$key = UserStorageKey::resolve($this->ess->getSettings(), $key);
		}
		unset($this->userCache[$key]);
	}

	/**
	 * @return list<User>
	 */
	public function getOnlineUsers() : array{
		$users = [];
		foreach($this->ess->getServer()->getOnlinePlayers() as $player){
			$users[] = $this->getUser($player);
		}
		return $users;
	}

	public function getCachedCount() : int{
		return count($this->userCache);
	}

	/**
	 * Storage keys of every stored player record.
	 *
	 * @return list<string>
	 */
	public function getAllUserKeys() : array{
		return $this->ess->getDataProvider()->getKeys();
	}

	public function getUserCount() : int{
		return count($this->getAllUserKeys());
	}

	public function shutdown() : void{
		foreach($this->userCache as $user){
			$user->save();
		}
		$this->userCache = [];
	}
}
