<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ;

use Doma\EssentialsZ\economy\EssentialsEconomy;
use Doma\EssentialsZ\storage\DataProvider;
use Doma\EssentialsZ\session\ModernUserMap;
use pocketmine\player\Player;
use pocketmine\Server;
use Doma\EssentialsZ\kit\Categories;
use Doma\EssentialsZ\kit\Kits;
use Doma\EssentialsZ\rtl\RtlProcessor;
use Doma\EssentialsZ\session\User;
use Doma\EssentialsZ\teleport\RandomTeleport;
use Doma\EssentialsZ\teleport\Spawn;
use Doma\EssentialsZ\warp\Warps;

interface IEssentials{

	public function getSettings() : ISettings;

	public function getI18n() : I18n;

	/**
	 * The backing store for all persistent player data.
	 */
	public function getDataProvider() : DataProvider;

	public function getUsers() : ModernUserMap;

	public function getWarps() : Warps;

	public function getKits() : Kits;

	public function getCategories() : Categories;

	/**
	 * The economy API, or null when the economy module is disabled.
	 */
	public function getEconomy() : ?EssentialsEconomy;

	/**
	 * The right-to-left text API, or null when the RTL module is disabled.
	 */
	public function getRtl() : ?RtlProcessor;

	public function getSpawn() : Spawn;

	public function getRandomTeleport() : RandomTeleport;

	public function getUser(Player $base) : User;

	/**
	 * @return list<User>
	 */
	public function getOnlineUsers() : array;

	public function reload() : void;

	public function getServer() : Server;

	public function getDataFolder() : string;
}
