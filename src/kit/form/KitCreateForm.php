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
use Doma\EssentialsZ\kit\Kit;
use Doma\EssentialsZ\kit\Kits;
use Doma\EssentialsZ\session\User;
use pocketmine\player\Player;
use function is_numeric;
use function trim;
use jojoe77777\FormAPI\CustomForm;

final class KitCreateForm{

	public static function open(IEssentials $ess, User $user) : void{
		$form = new CustomForm(static function(Player $player, ?array $data) use ($ess) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);

			$name = trim((string) ($data[0] ?? ""));
			$displayName = trim((string) ($data[1] ?? ""));
			$delayRaw = trim((string) ($data[2] ?? "0"));
			$costRaw = trim((string) ($data[3] ?? "0"));
			$iconType = [Kit::ICON_NONE, Kit::ICON_PATH, Kit::ICON_URL][(int) ($data[4] ?? 0)] ?? Kit::ICON_NONE;
			$icon = trim((string) ($data[5] ?? ""));
			$includeArmor = (bool) ($data[6] ?? true);

			if($name === "" || !Kits::isValidName($name)){
				$user->sendTl("invalidKitName");
				return;
			}
			if($ess->getKits()->getKit($name) !== null){
				$user->sendTl("kitUiExists", $name);
				return;
			}
			if(!KitFormFields::isNumericOrEmpty($delayRaw) || !KitFormFields::isNumericOrEmpty($costRaw)){
				$user->sendTl("kitUiInvalidNumber");
				return;
			}
			if($displayName === ""){
				$displayName = $name;
			}
			if($icon === ""){
				$iconType = Kit::ICON_NONE;
			}

			$items = KitFormFields::snapshotInventory($player);
			$armor = $includeArmor ? KitFormFields::snapshotArmor($player) : [];
			if($items === [] && $armor === []){
				$user->sendTl("kitUiNoItems");
				return;
			}

			$ess->getKits()->setKit(new Kit(
				$name,
				$displayName,
				$delayRaw === "" ? 0.0 : (float) $delayRaw,
				$costRaw === "" ? 0.0 : (float) $costRaw,
				$iconType,
				$icon,
				$items,
				$armor,
				[]
			));
			$user->sendTl("kitUiCreated", $name);
			KitEditMenuForm::open($ess, $user, $name);
		});
		$form->setTitle($user->playerTl("kitUiCreateTitle"));
		$form->addInput($user->playerTl("kitUiName"), "starter");
		$form->addInput($user->playerTl("kitUiDisplayName"), "\u{00A7}bStarter");
		$form->addInput($user->playerTl("kitUiDelay"), "0", "0");
		$form->addInput($user->playerTl("kitUiCost"), "0", "0");
		$form->addDropdown($user->playerTl("kitUiIconType"), [
			$user->playerTl("kitUiIconNone"),
			$user->playerTl("kitUiIconPath"),
			$user->playerTl("kitUiIconUrl")
		]);
		$form->addInput($user->playerTl("kitUiIcon"), "textures/items/diamond_sword");
		$form->addToggle($user->playerTl("kitUiIncludeArmor"), true);
		$user->getBase()->sendForm($form);
	}
}
