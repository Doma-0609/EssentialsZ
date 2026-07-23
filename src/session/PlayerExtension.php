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

use pocketmine\player\Player;
use pocketmine\Server;

/**
 * Base wrapper around the underlying PocketMine Player.
 */
class PlayerExtension{

	protected Player $base;

	public function __construct(Player $base){
		$this->base = $base;
	}

	final public function getBase() : Player{
		return $this->base;
	}

	final public function setBase(Player $base) : void{
		$this->base = $base;
	}

	final public function getServer() : Server{
		return $this->base->getServer();
	}
}
