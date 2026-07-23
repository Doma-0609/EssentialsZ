<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\economy;

use Doma\EssentialsZ\commands\Commandpay;
use Doma\EssentialsZ\commands\TranslatableException;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\session\User;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\player\Player;
use function is_numeric;
use function trim;

final class EconomyForms{

	public function __construct(private IEssentials $ess){}

	private function tl(User $user, string $key, string|int|float ...$args) : string{
		return $user->playerTl($key, ...$args);
	}

	public function openPayForm(User $user) : void{
		$names = [];
		foreach($this->ess->getServer()->getOnlinePlayers() as $player){
			if($player !== $user->getBase() && $user->getBase()->canSee($player)){
				$names[] = $player->getName();
			}
		}
		if($names === []){
			$user->sendTl("playerNotFound");
			return;
		}

		$ess = $this->ess;
		$form = new CustomForm(static function(Player $player, ?array $data) use ($ess, $names) : void{
			if($data === null){
				return;
			}
			$user = $ess->getUser($player);

			$targetName = $names[(int) ($data[0] ?? 0)] ?? null;
			$amountRaw = trim((string) ($data[1] ?? ""));
			if($targetName === null){
				return;
			}
			if(!is_numeric($amountRaw) || (float) $amountRaw <= 0.0){
				$user->sendTl("payMustBePositive");
				return;
			}
			$target = $ess->getServer()->getPlayerExact($targetName);
			if($target === null || $target === $player || !$player->canSee($target)){
				$user->sendTl("playerNotFound");
				return;
			}
			try{
				Commandpay::pay($ess, $user, $target->getName(), $target->getDisplayName(), (float) $amountRaw);
			}catch(TranslatableException $e){
				$user->sendMessage($e->getMessage());
			}
		});
		$form->setTitle($this->tl($user, "payUiTitle"));
		$form->addDropdown($this->tl($user, "payUiPlayer"), $names);
		$form->addInput($this->tl($user, "payUiAmount"), "100");
		$user->getBase()->sendForm($form);
	}
}
