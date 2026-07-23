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
use Doma\EssentialsZ\EssentialsZ;
use Doma\EssentialsZ\I18n;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use function str_replace;

/**
 * CommandMap bridge: every EssentialsZ command is registered at runtime as
 * one of these. Handles the permission gate, exception-to-message mapping
 * and usage help, then dispatches into the command framework.
 */
final class EssentialsPluginCommand extends Command implements PluginOwned{

	/**
	 * @param list<string> $aliases
	 */
	public function __construct(
		private EssentialsZ $plugin,
		private IEssentialsCommand $command,
		Translatable|string $description = "",
		Translatable|string|null $usageMessage = null,
		array $aliases = []
	){
		parent::__construct($command->getName(), $description, $usageMessage, $aliases);
		// Permissions must already exist in the PermissionManager
		// (registered by PermissionsHandler / the server before commands
		// are built). Any node in the list unlocks the command.
		$this->setPermissions(["essentialsz." . $command->getName(), ...$command->getAlternatePermissions()]);
	}

	public function getOwningPlugin() : Plugin{
		return $this->plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->plugin->isEnabled()){
			return false;
		}

		$ess = $this->plugin;
		$server = $ess->getServer();
		$source = new CommandSource($sender, $ess);
		$user = $sender instanceof Player ? $ess->getUser($sender) : null;

		// Permission gate with the plugin's own message instead of the
		// vanilla one (console always passes).
		if($sender instanceof Player && !$this->testPermissionSilent($sender)){
			$source->sendTl("noAccessCommand");
			return true;
		}

		try{
			$this->command->execute($server, $source, $user, $commandLabel, $args);
		}catch(NotEnoughArgumentsException $e){
			$this->sendUsage($source, $commandLabel);
			if($e->getMessage() !== ""){
				$source->sendMessage($e->getMessage());
			}
		}catch(TranslatableException $e){
			$this->showError($source, $e->getMessage(), $commandLabel);
		}catch(\Throwable $t){
			$this->showError($source, $t->getMessage(), $commandLabel);
			$ess->getLogger()->error(I18n::tlLiteral("commandFailed", $commandLabel));
			$ess->getLogger()->logException($t);
		}
		return true;
	}

	/**
	 * Multi-line command help shown when a command gets wrong arguments.
	 */
	private function sendUsage(CommandSource $source, string $commandLabel) : void{
		$usageStrings = $this->command->getUsageStrings();
		if($this->plugin->getSettings()->isVerboseCommandUsages() && $usageStrings !== []){
			$source->sendTl("commandHelpLine1", $commandLabel);
			$source->sendTl("commandHelpLine2", self::stringify($this->getDescription()));
			$source->sendTl("commandHelpLine3");
			foreach($usageStrings as $usage => $descriptionKey){
				$source->sendTl("commandHelpLineUsage", str_replace("<command>", $commandLabel, $usage), I18n::tlLiteral($descriptionKey));
			}
		}else{
			$source->sendMessage(str_replace("<command>", $commandLabel, self::stringify($this->getUsage())));
		}
	}

	private static function stringify(Translatable|string $text) : string{
		return $text instanceof Translatable ? $text->getText() : $text;
	}

	private function showError(CommandSource $source, string $message, string $commandLabel) : void{
		$source->sendTl("errorWithMessage", $message);
		if($this->plugin->getSettings()->isDebug()){
			$this->plugin->getLogger()->info(I18n::tlLiteral("errorWithMessage", $message) . " (/" . $commandLabel . ")");
		}
	}
}
