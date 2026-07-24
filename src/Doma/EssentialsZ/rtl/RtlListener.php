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
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\TextPacket;

final class RtlListener implements Listener{

    public function __construct(private RtlProcessor $processor){}

    public function onDataPacketSend(DataPacketSendEvent $event) : void{
        foreach($event->getPackets() as $packet){
            if($packet instanceof TextPacket){
                $this->correctPacket($packet);
            }
            if($packet instanceof ModalFormRequestPacket){
                $this->correctFormPacket($packet);
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

    private function correctFormPacket(ModalFormRequestPacket $packet) : void{
        $raw = $packet->formData;
        $data = json_decode($raw, true);
        if(!is_array($data)){
            return;
        }

        $this->correctFormArray($data);

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if($encoded !== false){
            $packet->formData = $encoded;
        }
    }

    private function correctFormArray(array &$data) : void{
        foreach($data as $key => &$value){
            if(is_string($value) && $value !== ""){
                $value = $this->processor->correct($value);
            }elseif(is_array($value)){
                $this->correctFormArray($value);
            }
        }
    }
}