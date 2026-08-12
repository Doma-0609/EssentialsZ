<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\land;

use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\DustParticle;

/**
 * Outlines a claim with coloured dust particles, shown only to one player.
 * Green marks land the player may build in, red marks someone else's.
 */
final class LandBorder{

	private function __construct(){
	}

	public static function show(Player $player, Land $land) : void{
		$world = $player->getWorld();
		if($world->getFolderName() !== $land->world){
			return;
		}
		$trusted = $land->isOwner($player->getName()) || $land->levelOf($player->getName()) !== null;
		$colour = $trusted ? new Color(255, 80, 220, 80) : new Color(255, 235, 70, 70);
		$particle = new DustParticle($colour);
		$y = $player->getPosition()->getFloorY() + 1;

		for($x = $land->minX; $x <= $land->maxX; $x++){
			$world->addParticle(new Vector3($x + 0.5, $y, $land->minZ + 0.5), $particle, [$player]);
			$world->addParticle(new Vector3($x + 0.5, $y, $land->maxZ + 0.5), $particle, [$player]);
		}
		for($z = $land->minZ + 1; $z < $land->maxZ; $z++){
			$world->addParticle(new Vector3($land->minX + 0.5, $y, $z + 0.5), $particle, [$player]);
			$world->addParticle(new Vector3($land->maxX + 0.5, $y, $z + 0.5), $particle, [$player]);
		}
	}
}
