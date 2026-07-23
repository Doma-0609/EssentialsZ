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

final class KitEditMenuForm{

	public static function open(IEssentials $ess, User $user, string $kitName) : void{
		if($ess->getKits()->getKit($kitName) === null){
			$user->sendTl("kitNotFound");
			return;
		}
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $kitName) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			match($data){
				0 => KitSettingsForm::open($ess, $user, $kitName),
				1 => KitCommandsForm::open($ess, $user, $kitName),
				default => null
			};
		});
		$form->setTitle($user->playerTl("kitUiEditKitTitle", $kitName));
		$form->setContent($user->playerTl("kitUiEditMenuText"));
		$form->addButton($user->playerTl("kitUiEditSettings"));
		$form->addButton($user->playerTl("kitUiEditCommands"));
		$user->getBase()->sendForm($form);
	}
}
