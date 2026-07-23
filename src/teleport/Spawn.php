<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\teleport;

use Doma\EssentialsZ\config\EssentialsConfiguration;
use Doma\EssentialsZ\utils\LocationUtil;
use pocketmine\entity\Location;
use pocketmine\Server;
use Doma\EssentialsZ\IEssentials;

final class Spawn{

	private EssentialsConfiguration $config;

	public function __construct(IEssentials $ess){
		$this->config = new EssentialsConfiguration($ess->getDataFolder() . "spawn.yml");
	}

	public function reloadConfig() : void{
		$this->config->load();
	}

	public function getSpawn(Server $server) : ?Location{
		return LocationUtil::fromMap($this->config->getMap("spawns.default"), $server);
	}

	public function setSpawn(Location $location) : void{
		$this->config->setProperty("spawns.default", LocationUtil::toMap($location));
		$this->config->save();
	}
}
