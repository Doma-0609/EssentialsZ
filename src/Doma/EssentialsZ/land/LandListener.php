<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\land;

use Doma\EssentialsZ\IEssentials;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;

final class LandListener implements Listener{

	public function __construct(
		private IEssentials $ess,
		private LandManager $lands
	){}

	public function onBreak(BlockBreakEvent $event) : void{
		$position = $event->getBlock()->getPosition();
		if(!$this->lands->canBuild($event->getPlayer(), $position->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorZ())){
			$this->deny($event, $event->getPlayer(), $position->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorZ());
		}
	}

	/**
	 * A placement can span several blocks; any one inside a protected claim
	 * cancels the whole placement.
	 */
	public function onPlace(BlockPlaceEvent $event) : void{
		$folderName = $event->getBlockAgainst()->getPosition()->getWorld()->getFolderName();
		$player = $event->getPlayer();
		foreach($event->getTransaction()->getBlocks() as [$x, , $z]){
			if(!$this->lands->canBuild($player, $folderName, $x, $z)){
				$this->deny($event, $player, $folderName, $x, $z);
				return;
			}
		}
	}

	/**
	 * Interaction is checked against the block's OWN position, so a container at
	 * the edge of a claim cannot be reached from an adjacent unclaimed block.
	 * Container-level members are allowed here even though they cannot build.
	 */
	public function onInteract(PlayerInteractEvent $event) : void{
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}
		$position = $event->getBlock()->getPosition();
		if(!$this->lands->canInteract($event->getPlayer(), $position->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorZ())){
			$this->deny($event, $event->getPlayer(), $position->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorZ());
		}
	}

	public function onTrample(EntityTrampleFarmlandEvent $event) : void{
		$entity = $event->getEntity();
		$position = $event->getBlock()->getPosition();
		$world = $position->getWorld()->getFolderName();
		if($this->lands->getLandAt($world, $position->getFloorX(), $position->getFloorZ()) === null){
			return;
		}
		// Claimed farmland never decays from trampling by anyone but the trusted.
		if(!($entity instanceof Player) || !$this->lands->canBuild($entity, $world, $position->getFloorX(), $position->getFloorZ())){
			$event->cancel();
		}
	}

	private function deny(BlockBreakEvent|BlockPlaceEvent|PlayerInteractEvent $event, Player $player, string $world, int $x, int $z) : void{
		$event->cancel();
		$land = $this->lands->getLandAt($world, $x, $z);
		$owner = $land !== null ? $land->owner : "";
		$this->ess->getUser($player)->sendTl($owner === "" ? "landProtectedWorld" : "landNoAccess", $owner);
	}
}
