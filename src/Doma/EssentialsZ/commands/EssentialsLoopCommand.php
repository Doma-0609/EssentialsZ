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
use pocketmine\utils\TextFormat;
use function mb_strtolower;
use function str_contains;
use function strlen;
use function trim;

/**
 * Base for commands that can target one player, a partial match, or the
 * "*" / "**" wildcards.
 */
abstract class EssentialsLoopCommand extends EssentialsCommand{

	/**
	 * Runs $userConsumer for every online player matched by $searchTerm.
	 * Matching rules:
	 *  - "@s"/"@p" from a player targets themselves
	 *  - "*" / "**" target everyone online (when $matchWildcards)
	 *  - partial matching against names, then display names
	 *    (when $multipleStringMatches)
	 *
	 * @param \Closure(User) : void $userConsumer
	 *
	 * @throws NotEnoughArgumentsException
	 * @throws TranslatableException
	 */
	protected function loopOnlinePlayersConsumer(Server $server, CommandSource $sender, bool $multipleStringMatches, bool $matchWildcards, string $searchTerm, \Closure $userConsumer) : void{
		if($searchTerm === ""){
			throw new PlayerNotFoundException();
		}

		$selected = $this->resolveSelector($sender, $searchTerm);
		if($selected !== null){
			if($selected === []){
				throw new PlayerNotFoundException();
			}
			foreach($selected as $user){
				$userConsumer($user);
			}
			return;
		}

		if($matchWildcards && ($searchTerm === "**" || $searchTerm === "*")){
			foreach($this->ess->getOnlineUsers() as $onlineUser){
				$userConsumer($onlineUser);
			}
			return;
		}

		if($multipleStringMatches){
			if(strlen(trim($searchTerm)) < 2){
				throw new PlayerNotFoundException();
			}
			$foundUser = false;
			$matchedPlayers = $this->matchPlayers($server, $searchTerm, $sender);

			if($matchedPlayers === []){
				$matchText = mb_strtolower($searchTerm);
				foreach($this->ess->getOnlineUsers() as $player){
					if(!$this->canInteract($sender, $player->getBase())){
						continue;
					}
					$displayName = mb_strtolower(TextFormat::clean($player->getDisplayName()));
					if(str_contains($displayName, $matchText)){
						$foundUser = true;
						$userConsumer($player);
					}
				}
			}else{
				foreach($matchedPlayers as $matchPlayer){
					$foundUser = true;
					$userConsumer($this->ess->getUser($matchPlayer));
				}
			}
			if(!$foundUser){
				throw new PlayerNotFoundException();
			}
		}else{
			$userConsumer($this->getPlayer($server, $sender, $searchTerm));
		}
	}

	/**
	 * Implemented by loop commands that forward their raw arguments to each
	 * matched player.
	 *
	 * @param list<string> $args
	 */
	abstract protected function updatePlayer(Server $server, CommandSource $sender, User $user, array $args) : void;
}
