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

use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;

final class CategoryPickForm{

	public const MODE_EDIT = "edit";
	public const MODE_DELETE = "delete";

	public static function open(IEssentials $ess, User $user, string $mode) : void{
		$categories = $ess->getCategories()->getAll();
		if($categories === []){
			$user->sendTl("kitUiNoCategories");
			return;
		}

		$names = [];
		foreach($categories as $category){
			$names[] = $category->name;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $names, $mode) : void{
			if($data === null || !isset($names[$data])){
				return;
			}
			$user = $ess->getUser($player);
			$category = $ess->getCategories()->getCategory($names[$data]);
			if($category === null){
				$user->sendTl("kitUiCategoryGone");
				return;
			}
			if($mode === self::MODE_DELETE){
				$ess->getCategories()->removeCategory($category->name);
				$user->sendTl("kitUiCategoryRemoved", $category->name);
			}else{
				CategoryForm::open($ess, $user, $category);
			}
		});
		$form->setTitle($user->playerTl($mode === self::MODE_DELETE ? "kitUiCategoryRemoveTitle" : "kitUiCategoryEditTitle"));
		foreach($categories as $category){
			$form->addButton($user->playerTl("kitUiEntryButton", $category->displayName));
		}
		$user->getBase()->sendForm($form);
	}
}
