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
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_map;
use function array_rand;
use function count;
use function implode;
use function is_numeric;
use function preg_replace_callback;
use function str_replace;
use function str_starts_with;
use function stripos;
use function strtolower;
use function substr;

abstract class EssentialsCommand implements IEssentialsCommand{

	/** Matches <required> / [optional] argument groups inside usage strings */
	private const ARGUMENT_PATTERN = "/([ :>])(([\\[<])[A-Za-z |]+[>\\]])/";

	protected IEssentials $ess;

	private string $name;
	/** @var array<string, string> usage => description translation key */
	private array $usageStrings = [];

	protected function __construct(string $name){
		$this->name = $name;

		// Discover usage strings from <name>CommandUsageN message keys,
		// stopping at the first missing index.
		for($i = 1; ; $i++){
			$baseKey = $name . "CommandUsage" . $i;
			if(!I18n::keyExistsLiteral($baseKey)){
				break;
			}
			$this->addUsageString(I18n::tlLiteral($baseKey), $baseKey . "Description");
		}
	}

	/**
	 * Colors <required> and [optional] argument groups of a usage string.
	 */
	private function addUsageString(string $usage, string $descriptionKey) : void{
		$colored = preg_replace_callback(self::ARGUMENT_PATTERN, static function(array $matches) : string{
			$color = $matches[3] === "<"
				? I18n::tlLiteral("commandArgumentRequired")
				: I18n::tlLiteral("commandArgumentOptional");
			return $matches[1]
				. $color
				. str_replace("|", I18n::tlLiteral("commandArgumentOr") . "|" . $color, $matches[2])
				. TextFormat::RESET;
		}, $usage);
		$this->usageStrings[$colored ?? $usage] = $descriptionKey;
	}

	public function getUsageStrings() : array{
		return $this->usageStrings;
	}

	public function getName() : string{
		return $this->name;
	}

	public function setEssentials(IEssentials $ess) : void{
		$this->ess = $ess;
	}

	public function getAlternatePermissions() : array{
		return [];
	}

	final public function execute(Server $server, CommandSource $source, ?User $user, string $commandLabel, array $args) : void{
		if($user !== null){
			$this->run($server, $user, $commandLabel, $args);
		}else{
			$this->runConsole($server, $source, $commandLabel, $args);
		}
	}

	/**
	 * Player execution path. Default: delegate to the generic path.
	 *
	 * @param list<string> $args
	 */
	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		$this->runConsole($server, $user->getSource(), $commandLabel, $args);
	}

	/**
	 * Console/generic execution path. Default: command is player-only.
	 *
	 * @param list<string> $args
	 */
	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		throw new TranslatableException("onlyPlayers", $commandLabel);
	}

	/**
	 * Joins args from $start onwards into a single string.
	 *
	 * @param list<string> $args
	 */
	public static function getFinalArg(array $args, int $start) : string{
		return implode(" ", \array_slice($args, $start));
	}

	/**
	 * Matches a single online player (exact, then prefix), throwing when
	 * nobody matches.
	 */
	protected function getPlayer(Server $server, CommandSource $sender, string $searchTerm) : User{
		$selected = $this->resolveSelector($sender, $searchTerm);
		if($selected !== null){
			if($selected === []){
				throw new PlayerNotFoundException();
			}
			return $selected[0];
		}
		$exact = $server->getPlayerExact($searchTerm);
		$player = $exact ?? $server->getPlayerByPrefix($searchTerm);
		if($player === null || !$this->canInteract($sender, $player)){
			throw new PlayerNotFoundException();
		}
		return $this->ess->getUser($player);
	}

	/**
	 * Every user a target selector or name matches (a plain name matches one).
	 *
	 * @return list<User>
	 * @throws PlayerNotFoundException when nothing matches
	 */
	protected function matchTargets(Server $server, CommandSource $sender, string $searchTerm) : array{
		$selected = $this->resolveSelector($sender, $searchTerm);
		if($selected !== null){
			if($selected === []){
				throw new PlayerNotFoundException();
			}
			return $selected;
		}
		return [$this->getPlayer($server, $sender, $searchTerm)];
	}

	/**
	 * Expands a Bedrock target selector into the users it matches: @s the
	 * sender, @a everyone, @p the nearest player, @r a random player. Returns
	 * null when $term is not a selector, or an empty list when it matched
	 * nobody. Vanished players the sender cannot see are excluded.
	 *
	 * @return list<User>|null
	 */
	protected function resolveSelector(CommandSource $sender, string $term) : ?array{
		switch(strtolower($term)){
			case "@s":
				$self = $sender->getUser();
				return $self !== null ? [$self] : [];
			case "@a":
				return $this->visibleUsers($sender);
			case "@r":
				$users = $this->visibleUsers($sender);
				return $users === [] ? [] : [$users[array_rand($users)]];
			case "@p":
				return $this->nearestUser($sender);
			default:
				return null;
		}
	}

	/**
	 * @return list<User>
	 */
	private function visibleUsers(CommandSource $sender) : array{
		$users = [];
		foreach($this->ess->getOnlineUsers() as $user){
			if($this->canInteract($sender, $user->getBase())){
				$users[] = $user;
			}
		}
		return $users;
	}

	/**
	 * @return list<User> the nearest player to the sender (0 or 1 entries)
	 */
	private function nearestUser(CommandSource $sender) : array{
		$users = $this->visibleUsers($sender);
		$from = $sender->getPlayer();
		if($from === null){
			return $users === [] ? [] : [$users[0]];
		}
		$best = null;
		$bestDistance = null;
		foreach($users as $user){
			$player = $user->getBase();
			if($player->getWorld() !== $from->getWorld()){
				continue;
			}
			$distance = $player->getPosition()->distanceSquared($from->getPosition());
			if($bestDistance === null || $distance < $bestDistance){
				$bestDistance = $distance;
				$best = $user;
			}
		}
		if($best !== null){
			return [$best];
		}
		return $users === [] ? [] : [$users[0]];
	}

	/**
	 * Vanished players are unreachable through commands for senders that
	 * cannot see them.
	 */
	protected function canInteract(?CommandSource $sender, \pocketmine\player\Player $target) : bool{
		$senderPlayer = $sender?->getPlayer();
		return $senderPlayer === null || $senderPlayer->canSee($target);
	}

	/**
	 * Player arg at position $pos; NotEnoughArguments when absent.
	 *
	 * @param list<string> $args
	 */
	protected function getPlayerAt(Server $server, CommandSource $sender, array $args, int $pos) : User{
		if(count($args) <= $pos){
			throw new NotEnoughArgumentsException();
		}
		if($args[$pos] === ""){
			throw new PlayerNotFoundException();
		}
		return $this->getPlayer($server, $sender, $args[$pos]);
	}

	/**
	 * Parses one coordinate argument; "~" and "~<offset>" are relative to
	 * $base.
	 */
	protected static function parseCoordinate(string $arg, float $base) : float{
		if(str_starts_with($arg, "~")){
			$offset = substr($arg, 1);
			if($offset === ""){
				return $base;
			}
			if(!is_numeric($offset)){
				throw new NotEnoughArgumentsException();
			}
			return $base + (float) $offset;
		}
		if(!is_numeric($arg)){
			throw new NotEnoughArgumentsException();
		}
		return (float) $arg;
	}

	protected static function checkCoordinateRange(CommandSource $sender, float ...$coordinates) : void{
		foreach($coordinates as $coordinate){
			if($coordinate > 30000000 || $coordinate < -30000000){
				throw new NotEnoughArgumentsException($sender->tl("teleportInvalidLocation"));
			}
		}
	}

	/**
	 * An exact name wins alone; otherwise every online player whose name
	 * contains the term (case-insensitive) is returned.
	 *
	 * @return list<\pocketmine\player\Player>
	 */
	protected function matchPlayers(Server $server, string $searchTerm, ?CommandSource $sender = null) : array{
		if($sender !== null){
			$selected = $this->resolveSelector($sender, $searchTerm);
			if($selected !== null){
				return array_map(static fn(User $user) => $user->getBase(), $selected);
			}
		}
		$exact = $server->getPlayerExact($searchTerm);
		if($exact !== null){
			return $this->canInteract($sender, $exact) ? [$exact] : [];
		}
		$matches = [];
		foreach($server->getOnlinePlayers() as $player){
			if(stripos($player->getName(), $searchTerm) !== false && $this->canInteract($sender, $player)){
				$matches[] = $player;
			}
		}
		return $matches;
	}
}
