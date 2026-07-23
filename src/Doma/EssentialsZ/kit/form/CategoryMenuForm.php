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

use Doma\EssentialsZ\kit\form\KitUI;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;

final class CategoryMenuForm{

	public static function open(IEssentials $ess, User $user) : void{
		$categories = [];
		foreach($ess->getCategories()->getAll() as $category){
			if(KitUI::categoryVisibleTo($user, $category)){
				$categories[] = $category;
			}
		}
		if($categories === []){
			KitListForm::open($ess, $user, null);
			return;
		}

		$names = [];
		foreach($categories as $category){
			$names[] = $category->name;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $names) : void{
			if($data === null || !isset($names[$data])){
				return;
			}
			$category = $ess->getCategories()->getCategory($names[$data]);
			$user = $ess->getUser($player);
			if($category === null || !KitUI::categoryVisibleTo($user, $category)){
				$user->sendTl("kitUiCategoryGone");
				return;
			}
			KitListForm::open($ess, $user, $category);
		});
		$form->setTitle($user->playerTl("kitUiCategoryTitle"));
		$form->setContent($user->playerTl("kitUiCategoryText"));
		foreach($categories as $category){
			$label = $user->playerTl("kitUiCategoryButton", $category->displayName);
			if($category->iconType === \Doma\EssentialsZ\kit\Category::ICON_NONE || $category->icon === ""){
				$form->addButton($label);
			}else{
				$form->addButton($label, $category->iconType, $category->icon);
			}
		}
		$user->getBase()->sendForm($form);
	}
}
