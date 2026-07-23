<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\commands;

use Doma\EssentialsZ\session\CommandSource;
use Doma\EssentialsZ\kit\form\KitUI;
use Doma\EssentialsZ\IEssentials;
use Doma\EssentialsZ\kit\Kit;
use Doma\EssentialsZ\session\User;
use Doma\EssentialsZ\utils\DateUtil;
use pocketmine\Server;
use function count;
use function explode;
use function floor;
use function implode;
use function mb_strtolower;
use function microtime;

class Commandkit extends EssentialsCommand{

	public function __construct(){
		parent::__construct("kit");
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			KitUI::openMenu($this->ess, $user);
			return;
		}

		if(mb_strtolower($args[0]) === "admin"){
			if(!$user->isAuthorized("essentialsz.kit.admin")){
				throw new TranslatableException("noAccessCommand");
			}
			KitUI::openAdmin($this->ess, $user);
			return;
		}

		if(count($args) > 1 && $user->isAuthorized("essentialsz.kit.others")){
			$target = $this->getPlayerAt($server, $user->getSource(), $args, 1);
			$this->giveKits($target, $user, mb_strtolower($args[0]));
		}else{
			$this->giveKits($user, $user, mb_strtolower($args[0]));
		}
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 2){
			$this->sendKitList($sender);
			return;
		}

		$target = $this->getPlayer($server, $sender, $args[1]);
		foreach(explode(",", mb_strtolower($args[0])) as $kitName){
			$kit = $this->ess->getKits()->getKit($kitName);
			if($kit === null){
				throw new TranslatableException("kitNotFound");
			}
			$this->ess->getKits()->giveKit($target, $kit);
			$sender->sendTl("kitGiveTo", $kit->name, $target->getDisplayName());
			$target->sendTl("kitReceive", $kit->name);
		}
	}

	private function sendKitList(CommandSource $sender) : void{
		$names = [];
		foreach($this->ess->getKits()->getAll() as $kit){
			if(!$sender->isPlayer() || $sender->getUser()->isAuthorized("essentialsz.kits." . mb_strtolower($kit->name))){
				$names[] = $kit->name;
			}
		}
		if($names === []){
			$sender->sendTl("noKits");
		}else{
			$sender->sendTl("kits", implode(", ", $names));
		}
	}

	private function giveKits(User $userTo, User $userFrom, string $kitNames) : void{
		if($kitNames === ""){
			throw new TranslatableException("kitNotFound");
		}
		foreach(explode(",", $kitNames) as $kitName){
			if($kitName === ""){
				throw new TranslatableException("kitNotFound");
			}
			self::claimKit($this->ess, $userTo, $userFrom, $kitName);
		}
	}

	public static function claimKit(IEssentials $ess, User $userTo, User $userFrom, string $kitName) : void{
		$kit = $ess->getKits()->getKit($kitName);
		if($kit === null){
			throw new TranslatableException("kitNotFound");
		}

		$node = "essentialsz.kits." . mb_strtolower($kit->name);
		if(!$userFrom->isAuthorized($node)){
			throw new TranslatableException("noKitPermission", $node);
		}

		$nextUse = self::getNextUse($userFrom, $kit);
		if($nextUse < 0){
			$userFrom->sendTl("kitOnce");
			return;
		}
		if($nextUse > 0){
			$userFrom->sendTl("kitTimed", DateUtil::formatDateDiff($nextUse));
			return;
		}

		$economy = $ess->getEconomy();
		$charge = $economy !== null && $kit->cost > 0;
		if($charge && !$economy->hasBalance($userTo->getName(), $kit->cost)){
			throw new TranslatableException("notEnoughMoney");
		}

		$ess->getKits()->giveKit($userTo, $kit);
		if($kit->delay !== 0.0){
			$userFrom->setKitTimestamp($kit->name, (int) floor(microtime(true) * 1000));
		}
		if($charge){
			$economy->subtractBalance($userTo->getName(), $kit->cost);
		}

		if($userFrom !== $userTo){
			$userFrom->sendTl("kitGiveTo", $kit->name, $userTo->getDisplayName());
		}
		$userTo->sendTl("kitReceive", $kit->name);
	}

	/** @return int 0 usable now, <0 single-use already claimed, >0 next-use timestamp (ms) */
	public static function getNextUse(User $user, Kit $kit) : int{
		if($user->isAuthorized("essentialsz.kit.exemptdelay")){
			return 0;
		}
		$lastTime = $user->getKitTimestamp($kit->name);
		$now = (int) floor(microtime(true) * 1000);
		if($lastTime === 0 || $lastTime > $now){
			return 0;
		}
		if($kit->delay < 0){
			return -1;
		}
		$nextUse = $lastTime + (int) ($kit->delay * 1000);
		return $nextUse <= $now ? 0 : $nextUse;
	}
}
