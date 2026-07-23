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

use pocketmine\block\Liquid;
use pocketmine\entity\Location;
use pocketmine\world\World;
use function cos;
use function deg2rad;
use function floor;
use function mt_rand;
use function sin;
use Doma\EssentialsZ\IEssentials;

final class RandomTeleport{

	public function __construct(private IEssentials $ess){}

	public function getRandomLocation(World $world) : ?Location{
		$settings = $this->ess->getSettings();
		$minRange = $settings->getRandomTeleportMinRange();
		$maxRange = $settings->getRandomTeleportMaxRange();
		$attempts = $settings->getRandomTeleportAttempts();
		$center = $world->getSpawnLocation();

		for($i = 0; $i < $attempts; $i++){
			$distance = mt_rand($minRange, $maxRange);
			$angle = deg2rad(mt_rand(0, 359));
			$x = (int) floor($center->getX() + cos($angle) * $distance);
			$z = (int) floor($center->getZ() + sin($angle) * $distance);

			$world->loadChunk($x >> 4, $z >> 4);
			$y = $world->getHighestBlockAt($x, $z);
			if($y === null || $y <= $world->getMinY()){
				continue;
			}
			if($world->getBlockAt($x, $y, $z) instanceof Liquid){
				continue;
			}
			return new Location($x + 0.5, $y + 1, $z + 0.5, $world, 0.0, 0.0);
		}
		return null;
	}
}
