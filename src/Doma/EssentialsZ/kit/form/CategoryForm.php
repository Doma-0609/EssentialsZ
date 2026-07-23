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

use Doma\EssentialsZ\kit\Categories;
use Doma\EssentialsZ\kit\Category;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use function trim;
use jojoe77777\FormAPI\CustomForm;

/**
 * Creates a category (when $existing is null) or edits one. Its member kits
 * are chosen with one toggle per known kit; the category name is fixed once
 * created.
 */
final class CategoryForm{

	public static function open(IEssentials $ess, User $user, ?Category $existing) : void{
		$kitNames = [];
		foreach($ess->getKits()->getAll() as $kit){
			$kitNames[] = $kit->name;
		}
		$creating = $existing === null;

		$form = new CustomForm(static function(Player $player, ?array $data) use ($ess, $kitNames, $creating, $existing) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);

			$offset = $creating ? 1 : 0;
			$name = $creating ? trim((string) ($data[0] ?? "")) : $existing->name;
			$displayName = trim((string) ($data[$offset] ?? ""));
			$iconType = [Category::ICON_NONE, Category::ICON_PATH, Category::ICON_URL][(int) ($data[$offset + 1] ?? 0)] ?? Category::ICON_NONE;
			$icon = trim((string) ($data[$offset + 2] ?? ""));
			$locked = (bool) ($data[$offset + 3] ?? true);

			if($name === "" || !Categories::isValidName($name)){
				$user->sendTl("kitUiInvalidCategoryName");
				return;
			}
			if($creating && $ess->getCategories()->getCategory($name) !== null){
				$user->sendTl("kitUiCategoryExists", $name);
				return;
			}
			if($displayName === ""){
				$displayName = $name;
			}
			if($icon === ""){
				$iconType = Category::ICON_NONE;
			}

			$kits = [];
			$toggleBase = $offset + 4;
			foreach($kitNames as $i => $kitName){
				if((bool) ($data[$toggleBase + $i] ?? false)){
					$kits[] = $kitName;
				}
			}

			$ess->getCategories()->setCategory(new Category($name, $displayName, $iconType, $icon, $locked, $kits));
			$user->sendTl($creating ? "kitUiCategoryCreated" : "kitUiCategoryUpdated", $name);
		});
		$form->setTitle($user->playerTl($creating ? "kitUiCategoryCreateTitle" : "kitUiCategoryEditKitTitle", $existing?->name ?? ""));
		if($creating){
			$form->addInput($user->playerTl("kitUiName"), "pvp");
		}
		$form->addInput($user->playerTl("kitUiDisplayName"), "\u{00A7}bPvP", $existing?->displayName ?? "");
		$form->addDropdown($user->playerTl("kitUiIconType"), [
			$user->playerTl("kitUiIconNone"),
			$user->playerTl("kitUiIconPath"),
			$user->playerTl("kitUiIconUrl")
		], self::iconIndex($existing?->iconType ?? Category::ICON_NONE));
		$form->addInput($user->playerTl("kitUiIcon"), "textures/items/iron_sword", $existing?->icon ?? "");
		$form->addToggle($user->playerTl("kitUiCategoryLocked"), $existing?->locked ?? true);
		foreach($kitNames as $kitName){
			$form->addToggle($user->playerTl("kitUiCategoryMember", $kitName), $existing !== null && $existing->hasKit($kitName));
		}
		$user->getBase()->sendForm($form);
	}

	private static function iconIndex(int $iconType) : int{
		return match($iconType){
			Category::ICON_PATH => 1,
			Category::ICON_URL => 2,
			default => 0
		};
	}
}
