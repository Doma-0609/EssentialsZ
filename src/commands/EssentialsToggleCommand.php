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
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function count;
use function str_starts_with;
use function strlen;
use function strtolower;
use function trim;

abstract class EssentialsToggleCommand extends EssentialsCommand{

	protected string $othersPermission;

	protected function __construct(string $name, string $othersPermission){
		parent::__construct($name);
		$this->othersPermission = $othersPermission;
	}

	protected function handleToggleWithArgs(Server $server, User $user, array $args) : void{
		if(count($args) === 1){
			$toggle = $this->matchToggleArgument($args[0]);
			if($toggle === null && $user->isAuthorized($this->othersPermission)){
				$this->toggleOtherPlayers($server, $user->getSource(), $args);
			}else{
				$this->togglePlayer($user->getSource(), $user, $toggle);
			}
		}elseif(count($args) === 2 && $user->isAuthorized($this->othersPermission)){
			$this->toggleOtherPlayers($server, $user->getSource(), $args);
		}else{
			$this->togglePlayer($user->getSource(), $user, null);
		}
	}

	protected function matchToggleArgument(string $arg) : ?bool{
		$arg = strtolower($arg);
		if($arg === "on" || str_starts_with($arg, "ena") || $arg === "1"){
			return true;
		}elseif($arg === "off" || str_starts_with($arg, "dis") || $arg === "0"){
			return false;
		}
		return null;
	}

	protected function toggleOtherPlayers(Server $server, CommandSource $sender, array $args) : void{
		if(count($args) < 1 || strlen(trim($args[0])) < 2){
			throw new PlayerNotFoundException();
		}

		$foundUser = false;
		foreach($this->matchPlayers($server, $args[0], $sender) as $matchPlayer){
			$foundUser = true;
			$player = $this->ess->getUser($matchPlayer);
			if(count($args) > 1){
				$this->togglePlayer($sender, $player, $this->matchToggleArgument($args[1]));
			}else{
				$this->togglePlayer($sender, $player, null);
			}
		}
		if(!$foundUser){
			throw new PlayerNotFoundException();
		}
	}

	// null must toggle the current state
	abstract protected function togglePlayer(CommandSource $sender, User $user, ?bool $enabled) : void;
}
