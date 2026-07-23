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

use Doma\EssentialsZ\commands\TranslatableException;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;

final class AdminMenuForm{

	public static function open(IEssentials $ess, User $user) : void{
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			try{
				match($data){
					0 => KitCreateForm::open($ess, $user),
					1 => KitPickForm::open($ess, $user, KitPickForm::MODE_EDIT),
					2 => KitPickForm::open($ess, $user, KitPickForm::MODE_DELETE),
					3 => CategoryForm::open($ess, $user, null),
					4 => CategoryPickForm::open($ess, $user, CategoryPickForm::MODE_EDIT),
					5 => CategoryPickForm::open($ess, $user, CategoryPickForm::MODE_DELETE),
					default => null
				};
			}catch(TranslatableException $e){
				$user->sendMessage($e->getMessage());
			}
		});
		$form->setTitle($user->playerTl("kitUiAdminTitle"));
		$form->addButton($user->playerTl("kitUiCreate"));
		$form->addButton($user->playerTl("kitUiEdit"));
		$form->addButton($user->playerTl("kitUiRemove"));
		$form->addButton($user->playerTl("kitUiCategoryCreate"));
		$form->addButton($user->playerTl("kitUiCategoryEdit"));
		$form->addButton($user->playerTl("kitUiCategoryRemove"));
		$user->getBase()->sendForm($form);
	}
}
