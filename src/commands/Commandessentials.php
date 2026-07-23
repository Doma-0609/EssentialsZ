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
use Doma\EssentialsZ\I18n;
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function str_replace;
use function strtolower;

/**
 * The plugin's admin command: /essentials reload | version | debug.
 */
class Commandessentials extends EssentialsCommand{

	public function __construct(
		private string $version
	){
		parent::__construct("essentials");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$this->runConsole($server, $user->getSource(), $commandLabel, $args);
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if($args === []){
			$this->showUsage($sender, $commandLabel);
			return;
		}

		switch(strtolower($args[0])){
			case "reload":
				$this->ess->reload();
				$sender->sendTl("essentialsReload", $this->version);
				break;
			case "version":
			case "ver":
				$sender->sendTl("versionOutputFine", "EssentialsZ", $this->version);
				break;
			case "debug":
				$settings = $this->ess->getSettings();
				$settings->setDebug(!$settings->isDebug());
				$sender->sendTl("debugMode", $settings->isDebug() ? "enabled" : "disabled");
				break;
			default:
				$this->showUsage($sender, $commandLabel);
		}
	}

	private function showUsage(CommandSource $sender, string $commandLabel) : void{
		$sender->sendTl("versionOutputFine", "EssentialsZ", $this->version);
		foreach($this->getUsageStrings() as $usage => $descriptionKey){
			$sender->sendTl("commandHelpLineUsage", str_replace("<command>", $commandLabel, $usage), I18n::tlLiteral($descriptionKey));
		}
	}
}
