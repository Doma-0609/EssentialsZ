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

class PlayerNotFoundException extends TranslatableException{

	public function __construct(){
		parent::__construct("playerNotFound");
	}
}
