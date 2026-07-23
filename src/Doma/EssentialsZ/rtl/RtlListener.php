<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\rtl;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\TextPacket;

final class RtlListener implements Listener{

	public function __construct(private RtlProcessor $processor){}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		foreach($event->getPackets() as $packet){
			if($packet instanceof TextPacket){
				$this->correctPacket($packet);
			}
		}
	}

	/**
	 * Translation packets carry a translation key in $message, so only their
	 * parameters may be rewritten.
	 */
	private function correctPacket(TextPacket $packet) : void{
		if($packet->type === TextPacket::TYPE_TRANSLATION){
			foreach($packet->parameters as $i => $parameter){
				$packet->parameters[$i] = $this->processor->correct($parameter);
			}
		}elseif($packet->message !== ""){
			$packet->message = $this->processor->correct($packet->message);
		}
	}
}
