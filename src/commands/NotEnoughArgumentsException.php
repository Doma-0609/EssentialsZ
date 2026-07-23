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

/**
 * Thrown when a command is invoked with missing/invalid arguments.
 * The dispatcher responds by showing the command's usage help, followed by
 * this exception's message when one is set.
 */
class NotEnoughArgumentsException extends \Exception{

	public function __construct(string $message = ""){
		parent::__construct($message);
	}
}
