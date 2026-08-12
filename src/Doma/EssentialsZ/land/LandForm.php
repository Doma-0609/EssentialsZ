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
use Doma\EssentialsZ\session\User;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;
use function array_keys;
use function count;
use function trim;

final class LandForm{

	public static function openMenu(IEssentials $ess, User $user) : void{
		$lands = $ess->getLand()->getLandsOfOwner($user->getName());

		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $lands) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			if($data === 0){
				$position = $player->getPosition();
				$here = $ess->getLand()->getLandAt($player->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorZ());
				$here === null
					? $user->sendTl("landHereNone")
					: $user->sendTl("landHere", $here->id, $here->owner, $here->minX, $here->minZ, $here->maxX, $here->maxZ);
				return;
			}
			$land = $lands[$data - 1] ?? null;
			if($land !== null){
				self::openLand($ess, $user, $land->id);
			}
		});
		$form->setTitle($user->playerTl("landUiTitle"));
		$form->setContent($user->playerTl("landUiText", count($lands)));
		$form->addButton($user->playerTl("landUiHere"));
		foreach($lands as $land){
			$form->addButton($user->playerTl("landUiEntry", $land->id, $land->world));
		}
		$user->getBase()->sendForm($form);
	}

	public static function openLand(IEssentials $ess, User $user, int $id) : void{
		$land = self::ownedLand($ess, $user, $id);
		if($land === null){
			return;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $id) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			$land = self::ownedLand($ess, $user, $id);
			if($land === null){
				return;
			}
			match($data){
				0 => self::openMembers($ess, $user, $id),
				1 => self::sell($ess, $user, $land),
				default => self::openMenu($ess, $user)
			};
		});
		$form->setTitle($user->playerTl("landUiDetailTitle", $land->id));
		$form->setContent($user->playerTl("landUiDetailText", $land->world, $land->minX, $land->minZ, $land->maxX, $land->maxZ, count($land->invitees)));
		$form->addButton($user->playerTl("landUiMembers"));
		$form->addButton($user->playerTl("landUiSell"));
		$form->addButton($user->playerTl("landUiBack"));
		$user->getBase()->sendForm($form);
	}

	public static function openMembers(IEssentials $ess, User $user, int $id) : void{
		$land = self::ownedLand($ess, $user, $id);
		if($land === null){
			return;
		}
		$names = array_keys($land->invitees);
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $id, $names) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			if($data === 0){
				self::openAddMember($ess, $user, $id);
				return;
			}
			$name = $names[$data - 1] ?? null;
			if($name !== null){
				self::openMember($ess, $user, $id, $name);
			}else{
				self::openLand($ess, $user, $id);
			}
		});
		$form->setTitle($user->playerTl("landUiMembersTitle", $land->id));
		$form->setContent($user->playerTl("landUiMembersText"));
		$form->addButton($user->playerTl("landUiAddMember"));
		foreach($land->invitees as $name => $level){
			$form->addButton($user->playerTl("landUiMemberEntry", $name, $user->playerTl("landUiLevel_" . $level)));
		}
		$user->getBase()->sendForm($form);
	}

	private static function openAddMember(IEssentials $ess, User $user, int $id) : void{
		$form = new CustomForm(static function(Player $player, ?array $data) use ($ess, $id) : void{
			$user = $ess->getUser($player);
			if($data === null){
				self::openMembers($ess, $user, $id);
				return;
			}
			$land = self::ownedLand($ess, $user, $id);
			if($land === null){
				return;
			}
			$name = trim((string) ($data[0] ?? ""));
			if($name === ""){
				self::openMembers($ess, $user, $id);
				return;
			}
			$level = (int) ($data[1] ?? 0) === 1 ? Land::LEVEL_CONTAINER : Land::LEVEL_BUILD;
			$ess->getLand()->setInvited($land, $name, $level);
			$user->sendTl("landInvited", $name, $land->id);
			self::openMembers($ess, $user, $id);
		});
		$form->setTitle($user->playerTl("landUiAddMemberTitle"));
		$form->addInput($user->playerTl("landUiMemberName"));
		$form->addDropdown($user->playerTl("landUiMemberLevel"), [
			$user->playerTl("landUiLevel_build"),
			$user->playerTl("landUiLevel_container")
		]);
		$user->getBase()->sendForm($form);
	}

	private static function openMember(IEssentials $ess, User $user, int $id, string $name) : void{
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $id, $name) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			$land = self::ownedLand($ess, $user, $id);
			if($land === null){
				return;
			}
			if($data === 0 || $data === 1){
				$ess->getLand()->setInvited($land, $name, $data === 0 ? Land::LEVEL_BUILD : Land::LEVEL_CONTAINER);
			}elseif($data === 2){
				$ess->getLand()->setInvited($land, $name, null);
				$user->sendTl("landKicked", $name, $land->id);
			}
			self::openMembers($ess, $user, $id);
		});
		$form->setTitle($user->playerTl("landUiMemberTitle", $name));
		$form->addButton($user->playerTl("landUiSetBuild"));
		$form->addButton($user->playerTl("landUiSetContainer"));
		$form->addButton($user->playerTl("landUiRemoveMember"));
		$form->addButton($user->playerTl("landUiBack"));
		$user->getBase()->sendForm($form);
	}

	private static function sell(IEssentials $ess, User $user, Land $land) : void{
		$ess->getLand()->remove($land);
		$user->sendTl("landSold", $land->id);
	}

	private static function ownedLand(IEssentials $ess, User $user, int $id) : ?Land{
		$land = $ess->getLand()->getLandById($id);
		if($land === null || !$land->isOwner($user->getName())){
			$user->sendTl("landNotYours");
			return null;
		}
		return $land;
	}
}
