<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\kit\form;

use Doma\EssentialsZ\kit\Category;
use Doma\EssentialsZ\commands\Commandkit;
use Doma\EssentialsZ\commands\TranslatableException;
use Doma\EssentialsZ\kit\form\KitUI;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\kit\Kit;
use Doma\EssentialsZ\session\User;
use Doma\EssentialsZ\utils\DateUtil;
use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;

final class KitListForm{

	public static function open(IEssentials $ess, User $user, ?Category $category) : void{
		$kits = self::claimableKits($ess, $user, $category);
		if($kits === []){
			$user->sendTl("noKits");
			return;
		}

		$names = [];
		foreach($kits as $kit){
			$names[] = $kit->name;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $names) : void{
			if($data === null || !isset($names[$data])){
				return;
			}
			$user = $ess->getUser($player);
			try{
				Commandkit::claimKit($ess, $user, $user, $names[$data]);
			}catch(TranslatableException $e){
				$user->sendMessage($e->getMessage());
			}
		});
		$form->setTitle($user->playerTl($category !== null ? "kitUiCategoryKitTitle" : "kitUiTitle", $category?->displayName ?? ""));
		$form->setContent($user->playerTl("kitUiKitText"));
		foreach($kits as $kit){
			$form->addButton(self::buttonLabel($ess, $user, $kit), ...self::iconArgs($kit));
		}
		$user->getBase()->sendForm($form);
	}

	/**
	 * @return list<Kit>
	 */
	private static function claimableKits(IEssentials $ess, User $user, ?Category $category) : array{
		$kits = [];
		if($category !== null){
			foreach($category->kits as $kitName){
				$kit = $ess->getKits()->getKit($kitName);
				if($kit !== null && KitUI::canClaim($user, $kit)){
					$kits[] = $kit;
				}
			}
		}else{
			foreach($ess->getKits()->getAll() as $kit){
				if(KitUI::canClaim($user, $kit)){
					$kits[] = $kit;
				}
			}
		}
		return $kits;
	}

	private static function buttonLabel(IEssentials $ess, User $user, Kit $kit) : string{
		$nextUse = Commandkit::getNextUse($user, $kit);
		if($nextUse < 0){
			return $user->playerTl("kitUiButtonOnce", $kit->displayName);
		}
		if($nextUse > 0){
			return $user->playerTl("kitUiButtonCooldown", $kit->displayName, DateUtil::formatDateDiff($nextUse));
		}
		$economy = $ess->getEconomy();
		if($economy !== null && $kit->cost > 0){
			return $user->playerTl("kitUiButtonPriced", $kit->displayName, $economy->formatMoney($kit->cost));
		}
		return $user->playerTl("kitUiButtonFree", $kit->displayName);
	}

	/**
	 * Trailing addButton() icon arguments: empty for no icon, otherwise
	 * [iconType, iconPath].
	 *
	 * @return list<int|string>
	 */
	private static function iconArgs(Kit $kit) : array{
		if($kit->iconType === Kit::ICON_NONE || $kit->icon === ""){
			return [];
		}
		return [$kit->iconType, $kit->icon];
	}
}
