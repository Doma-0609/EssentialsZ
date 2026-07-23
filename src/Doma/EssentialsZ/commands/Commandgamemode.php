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
use Doma\EssentialsZ\session\IUser;
use Doma\EssentialsZ\session\User;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\GameMode;
use pocketmine\Server;
use function count;
use function str_contains;
use function strlen;
use function strtolower;
use function trim;

class Commandgamemode extends EssentialsLoopCommand{

	public function __construct(){
		parent::__construct("gamemode");
	}

	public function getAlternatePermissions() : array{
		return [DefaultPermissionNames::COMMAND_GAMEMODE_SELF, DefaultPermissionNames::COMMAND_GAMEMODE_OTHER];
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			throw new NotEnoughArgumentsException();
		}elseif(count($args) === 1){
			$this->loopOnlinePlayersConsumer($server, $sender, false, true, $args[0],
				fn(User $user) => $this->setUserGamemode($sender, $this->matchGameMode($commandLabel), $user));
		}elseif(count($args) === 2){
			$this->loopOnlinePlayersConsumer($server, $sender, false, true, $args[1],
				fn(User $user) => $this->setUserGamemode($sender, $this->matchGameMode($args[0]), $user));
		}
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) === 0){
			$gameMode = $this->matchGameMode($commandLabel);
		}elseif(count($args) > 1 && strlen(trim($args[1])) > 2 && $this->canTargetOthers($user)){
			$this->loopOnlinePlayersConsumer($server, $user->getSource(), false, true, $args[1],
				fn(User $player) => $this->setUserGamemode($user->getSource(), $this->matchGameMode(strtolower($args[0])), $player));
			return;
		}else{
			try{
				$gameMode = $this->matchGameMode(strtolower($args[0]));
			}catch(NotEnoughArgumentsException $e){
				if($this->canTargetOthers($user)){
					$this->loopOnlinePlayersConsumer($server, $user->getSource(), false, true, $args[0],
						fn(User $player) => $this->setUserGamemode($user->getSource(), $this->matchGameMode($commandLabel), $player));
					return;
				}
				throw new NotEnoughArgumentsException();
			}
		}

		if($gameMode === null){
			// Toggle cycle: survival -> creative -> adventure -> survival
			$current = $user->getBase()->getGamemode();
			$gameMode = $current === GameMode::SURVIVAL
				? GameMode::CREATIVE
				: ($current === GameMode::CREATIVE ? GameMode::ADVENTURE : GameMode::SURVIVAL);
		}

		if($this->isProhibitedChange($user, $gameMode)){
			$user->sendTl("cantGamemode", $user->playerTl(strtolower($gameMode->name)));
			return;
		}

		$user->getBase()->setGamemode($gameMode);
		$user->sendTl("gameMode", $user->playerTl(strtolower($user->getBase()->getGamemode()->name)), $user->getDisplayName());
	}

	private function setUserGamemode(CommandSource $sender, ?GameMode $gameMode, User $user) : void{
		if($gameMode === null){
			throw new NotEnoughArgumentsException($sender->tl("gameModeInvalid"));
		}

		if($sender->isPlayer() && $this->isProhibitedChange($sender->getUser(), $gameMode)){
			$sender->sendTl("cantGamemode", $sender->tl(strtolower($gameMode->name)));
			return;
		}

		$user->getBase()->setGamemode($gameMode);
		$sender->sendTl("gameMode", $sender->tl(strtolower($gameMode->name)), $user->getDisplayName());
	}

	private function canTargetOthers(User $user) : bool{
		return $user->isAuthorized("essentialsz.gamemode.others")
			|| $user->isAuthorized(DefaultPermissionNames::COMMAND_GAMEMODE_OTHER);
	}

	/**
	 * essentialsz.gamemode grants command access; changing INTO a mode also
	 * requires essentialsz.gamemode.<mode> or essentialsz.gamemode.all.
	 * The vanilla gamemode permission bypasses the per-mode gate.
	 */
	private function isProhibitedChange(?IUser $user, GameMode $to) : bool{
		return $user !== null
			&& !$user->isAuthorized("essentialsz.gamemode.all")
			&& !$user->isAuthorized("essentialsz.gamemode." . strtolower($to->name))
			&& !$user->isAuthorized(DefaultPermissionNames::COMMAND_GAMEMODE_SELF);
	}

	/**
	 * Resolves a gamemode from a command label or argument.
	 * Returns null for the toggle forms (gmt/toggle/cycle/t).
	 *
	 * @throws NotEnoughArgumentsException when nothing matches
	 */
	private function matchGameMode(string $modeString) : ?GameMode{
		$modeString = strtolower($modeString);
		if($modeString === "gmc" || $modeString === "egmc" || str_contains($modeString, "creat") || $modeString === "1" || $modeString === "c"){
			return GameMode::CREATIVE;
		}elseif($modeString === "gms" || $modeString === "egms" || str_contains($modeString, "survi") || $modeString === "0" || $modeString === "s"){
			return GameMode::SURVIVAL;
		}elseif($modeString === "gma" || $modeString === "egma" || str_contains($modeString, "advent") || $modeString === "2" || $modeString === "a"){
			return GameMode::ADVENTURE;
		}elseif($modeString === "gmsp" || $modeString === "egmsp" || str_contains($modeString, "spec") || $modeString === "3" || $modeString === "sp"){
			return GameMode::SPECTATOR;
		}elseif($modeString === "gmt" || $modeString === "egmt" || str_contains($modeString, "toggle") || str_contains($modeString, "cycle") || $modeString === "t"){
			return null; // toggle: resolved against the player's current mode
		}
		throw new NotEnoughArgumentsException();
	}

	protected function updatePlayer(Server $server, CommandSource $sender, User $user, array $args) : void{
		// /gamemode does its own targeting; nothing to forward here.
	}
}
