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
use Doma\EssentialsZ\session\User;
use pocketmine\Server;
use function count;
use function is_numeric;
use function max;
use function min;
use function round;
use function strlen;
use function strtolower;
use function trim;

class Commandscale extends EssentialsCommand{

	public function __construct(){
		parent::__construct("scale");
	}

	protected function runConsole(Server $server, CommandSource $sender, string $commandLabel, array $args) : void{
		if(count($args) < 2){
			throw new NotEnoughArgumentsException();
		}
		$this->scaleOtherPlayers($server, $sender, $this->parseSize($args[0]), $args[1]);
	}

	protected function run(Server $server, User $user, string $commandLabel, array $args) : void{
		if(count($args) < 1){
			throw new NotEnoughArgumentsException();
		}

		$size = $this->parseSize($args[0]);
		if(count($args) > 1 && $user->isAuthorized("essentialsz.scale.others")){
			if(strlen(trim($args[1])) < 2){
				throw new PlayerNotFoundException();
			}
			$this->scaleOtherPlayers($server, $user->getSource(), $size, $args[1]);
			return;
		}

		$user->getBase()->setScale($size);
		$user->sendTl("scaled", (string) $size);
	}

	private function scaleOtherPlayers(Server $server, CommandSource $sender, float $size, string $name) : void{
		$foundUser = false;
		foreach($this->matchPlayers($server, $name, $sender) as $matchPlayer){
			$foundUser = true;
			$matchPlayer->setScale($size);
			$sender->sendTl("scaledOther", $matchPlayer->getDisplayName(), (string) $size);
		}
		if(!$foundUser){
			throw new PlayerNotFoundException();
		}
	}

	private function parseSize(string $arg) : float{
		$settings = $this->ess->getSettings();
		$min = $settings->getScaleMin();
		$max = $settings->getScaleMax();

		$lower = strtolower(trim($arg));
		if($lower === "reset" || $lower === "default"){
			return (float) min($max, max($min, 1.0));
		}
		if(!is_numeric($arg)){
			throw new TranslatableException("scaleOutOfRange", (string) $min, (string) $max);
		}

		$size = round((float) $arg, 2);
		if($size < $min || $size > $max){
			throw new TranslatableException("scaleOutOfRange", (string) $min, (string) $max);
		}
		return $size;
	}
}
