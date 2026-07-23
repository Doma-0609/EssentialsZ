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
use function count;
use jojoe77777\FormAPI\SimpleForm;

/**
 * Lists a kit's claim commands. Button 0 adds one, each command button opens
 * it for editing/removal, and the final button returns to the edit menu.
 */
final class KitCommandsForm{

	public static function open(IEssentials $ess, User $user, string $kitName) : void{
		$kit = $ess->getKits()->getKit($kitName);
		if($kit === null){
			$user->sendTl("kitNotFound");
			return;
		}
		$commandCount = count($kit->commands);
		$form = new SimpleForm(static function(Player $player, ?int $data) use ($ess, $kitName, $commandCount) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);
			if($data === 0){
				KitCommandEditForm::open($ess, $user, $kitName, null);
			}elseif($data <= $commandCount){
				KitCommandEditForm::open($ess, $user, $kitName, $data - 1);
			}else{
				KitEditMenuForm::open($ess, $user, $kitName);
			}
		});
		$form->setTitle($user->playerTl("kitUiCommandsTitle", $kit->name));
		$form->setContent($user->playerTl("kitUiCommandsText", $commandCount));
		$form->addButton($user->playerTl("kitUiCommandAdd"));
		foreach($kit->commands as $i => $command){
			$form->addButton($user->playerTl("kitUiCommandEntry", $i + 1, $command));
		}
		$form->addButton($user->playerTl("kitUiBack"));
		$user->getBase()->sendForm($form);
	}
}
