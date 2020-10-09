<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class KillAuraA extends Detection{

    private $hitInfo = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            if(empty($this->hitInfo)){
                $this->hitInfo = ["entity" => $packet->trData->entityRuntimeId, "tick" => $user->player->getServer()->getTick()];
            } else {
                $lastEntity = $this->hitInfo["entity"];
                $currentEntity = $packet->trData->entityRuntimeId;
                $lastTick = $this->hitInfo["tick"];
                $currentTick = $user->player->getServer()->getTick();
                if($lastEntity !== $currentEntity
                    && $lastTick === $currentTick){
                    $this->fail($user, "{$user->player->getName()}: cE: $currentEntity, lE: $lastEntity");
                } else {
                    $this->reward($user, 0.95);
                }
                $this->hitInfo["entity"] = $currentEntity;
                $this->hitInfo["tick"] = $currentTick;
            }
        }
    }

}