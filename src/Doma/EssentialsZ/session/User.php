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

use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_keys;
use function count;
use function floor;
use function mb_strtolower;
use function microtime;
use function str_replace;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\teleport\TpaRequest;

class User extends UserData implements IUser{

	private ?CommandSource $source = null;

	private bool $afk = false;
	private bool $godModeEnabled = false;
	private bool $vanished = false;
	private bool $flyModeEnabled = false;

	/** @var array<string, TpaRequest> */
	private array $tpaRequests = [];

	public function __construct(Player $base, IEssentials $ess){
		parent::__construct($base, $ess);
	}

	public function getName() : string{
		return $this->base->getName();
	}

	public function getDisplayName() : string{
		return $this->base->getDisplayName();
	}

	public function isAuthorized(string $node) : bool{
		return $this->base->hasPermission($node);
	}

	public function sendMessage(string $message) : void{
		if($message !== ""){
			$this->base->sendMessage($message);
		}
	}

	public function sendTl(string $tlKey, string|int|float ...$args) : void{
		$this->sendMessage($this->playerTl($tlKey, ...$args));
	}

	public function playerTl(string $tlKey, string|int|float ...$args) : string{
		return $this->ess->getI18n()->tlPlayer($this->base, $tlKey, ...$args);
	}

	public function getSource() : CommandSource{
		if($this->source === null || $this->source->getSender() !== $this->base){
			$this->source = new CommandSource($this->base, $this->ess);
		}
		return $this->source;
	}

	public function isAfk() : bool{
		return $this->afk;
	}

	public function setAfk(bool $afk) : void{
		$this->afk = $afk;
	}

	public function updateAfkStatus(bool $afk, ?string $message = null) : void{
		if($this->afk === $afk){
			return;
		}
		$this->afk = $afk;

		if($this->vanished){
			$this->sendTl($afk ? "userIsAwaySelf" : "userIsNotAwaySelf");
			return;
		}
		$i18n = $this->ess->getI18n();
		if($afk){
			$broadcast = $message !== null
				? $i18n->tl("userIsAwayWithMessage", $this->getDisplayName(), $message)
				: $i18n->tl("userIsAway", $this->getDisplayName());
		}else{
			$broadcast = $i18n->tl("userIsNotAway", $this->getDisplayName());
		}
		$this->getServer()->broadcastMessage($broadcast);
	}

	public function isGodModeEnabled() : bool{
		return $this->godModeEnabled;
	}

	public function setGodModeEnabled(bool $enabled) : void{
		$this->godModeEnabled = $enabled;
	}

	public function isFlyModeEnabled() : bool{
		return $this->flyModeEnabled;
	}

	public function setFlyModeEnabled(bool $enabled) : void{
		$this->flyModeEnabled = $enabled;
	}

	public function isVanished() : bool{
		return $this->vanished;
	}

	public function setVanished(bool $vanished, bool $announce = true) : void{
		if($this->vanished === $vanished){
			return;
		}
		$this->vanished = $vanished;
		$server = $this->getServer();
		$others = [];

		if($vanished){
			foreach($server->getOnlinePlayers() as $viewer){
				if($viewer === $this->base){
					continue;
				}
				$others[] = $viewer;
				if(!$viewer->hasPermission("essentialsz.vanish.see")){
					$this->hideFrom($viewer);
				}
			}
		}else{
			foreach($server->getOnlinePlayers() as $viewer){
				if($viewer === $this->base){
					continue;
				}
				$others[] = $viewer;
				if(!$viewer->canSee($this->base)){
					$this->showTo($viewer);
				}
			}
		}
		if($announce){
			$this->broadcastFakeQuitJoin($vanished, $others);
		}
	}

	/** @param list<Player> $recipients */
	private function broadcastFakeQuitJoin(bool $quit, array $recipients) : void{
		$settings = $this->ess->getSettings();
		$format = $quit ? $settings->getVanishFakeQuitMessage() : $settings->getVanishFakeJoinMessage();
		if($format !== null){
			$message = str_replace(
				["{PLAYER}", "{USERNAME}"],
				[$this->base->getDisplayName(), $this->base->getName()],
				$format
			);
		}else{
			$message = $quit
				? KnownTranslationFactory::multiplayer_player_left($this->base->getDisplayName())->prefix(TextFormat::YELLOW)
				: KnownTranslationFactory::multiplayer_player_joined($this->base->getDisplayName())->prefix(TextFormat::YELLOW);
		}
		$this->getServer()->broadcastMessage($message, $recipients);
	}

	public function requestTeleport(User $requester, bool $here) : void{
		$key = mb_strtolower($requester->getName());
		unset($this->tpaRequests[$key]);
		$this->tpaRequests[$key] = new TpaRequest(
			$key,
			$requester->getDisplayName(),
			$here,
			(int) floor(microtime(true) * 1000),
			$here ? $requester->getBase()->getLocation() : null
		);
	}

	private function purgeExpiredTpaRequests() : void{
		$timeout = $this->ess->getSettings()->getTpaAcceptCancellation();
		if($timeout === 0){
			return;
		}
		$now = (int) floor(microtime(true) * 1000);
		foreach($this->tpaRequests as $key => $request){
			if($now - $request->time > $timeout * 1000){
				unset($this->tpaRequests[$key]);
			}
		}
	}

	public function hasOutstandingTpaRequest(string $name, bool $here) : bool{
		$this->purgeExpiredTpaRequests();
		$request = $this->tpaRequests[mb_strtolower($name)] ?? null;
		return $request !== null && $request->here === $here;
	}

	public function getOutstandingTpaRequest(string $name) : ?TpaRequest{
		$this->purgeExpiredTpaRequests();
		return $this->tpaRequests[mb_strtolower($name)] ?? null;
	}

	public function getNextTpaRequest() : ?TpaRequest{
		$this->purgeExpiredTpaRequests();
		$next = null;
		foreach($this->tpaRequests as $request){
			if($next === null || $request->time < $next->time){
				$next = $request;
			}
		}
		return $next;
	}

	public function hasPendingTpaRequests() : bool{
		$this->purgeExpiredTpaRequests();
		return count($this->tpaRequests) > 0;
	}

	public function removeTpaRequest(string $name) : void{
		unset($this->tpaRequests[mb_strtolower($name)]);
	}

	/**
	 * @return list<string>
	 */
	public function getPendingTpaKeys() : array{
		$this->purgeExpiredTpaRequests();
		return array_keys($this->tpaRequests);
	}

	public function hideFrom(Player $viewer) : void{
		if($viewer === $this->base){
			return;
		}
		$viewer->hidePlayer($this->base);
		// hidePlayer() despawns the entity but leaves the tab-list entry
		$viewer->getNetworkSession()->sendDataPacket(PlayerListPacket::remove([
			PlayerListEntry::createRemovalEntry($this->base->getUniqueId())
		]));
	}

	public function showTo(Player $viewer) : void{
		if($viewer === $this->base){
			return;
		}
		$viewer->showPlayer($this->base);
		$session = $viewer->getNetworkSession();
		$session->sendDataPacket(PlayerListPacket::add([
			PlayerListEntry::createAdditionEntry(
				$this->base->getUniqueId(),
				$this->base->getId(),
				$this->base->getDisplayName(),
				$session->getTypeConverter()->getSkinAdapter()->toSkinData($this->base->getSkin()),
				$this->base->getXuid()
			)
		]));
	}
}
