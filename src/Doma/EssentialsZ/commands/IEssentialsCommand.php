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

use Doma\EssentialsZ\session\CommandSource;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\Server;

interface IEssentialsCommand{

	public function getName() : string;

	public function setEssentials(IEssentials $ess) : void;

	/**
	 * Foreign permission nodes that also unlock this command, in addition
	 * to essentialsz.<name>.
	 *
	 * @return list<string>
	 */
	public function getAlternatePermissions() : array;

	/**
	 * Usage lines built from <name>CommandUsageN message keys, mapped to
	 * their <name>CommandUsageNDescription key.
	 *
	 * @return array<string, string> usage => description translation key
	 */
	public function getUsageStrings() : array;

	/**
	 * Single dispatch entry point: the dispatcher passes both sender views
	 * and the base class routes players to run(), everything else to
	 * runConsole().
	 *
	 * @param list<string> $args
	 *
	 * @throws NotEnoughArgumentsException
	 * @throws TranslatableException
	 */
	public function execute(Server $server, CommandSource $source, ?User $user, string $commandLabel, array $args) : void;
}
