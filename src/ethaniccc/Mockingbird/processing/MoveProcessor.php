<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\PacketUtils;
use pocketmine\level\Location;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class MoveProcessor extends Processor{

    private $ticks = 0;

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function process(DataPacket $packet): void{
        if($packet instanceof PlayerAuthInputPacket){
            $user = $this->user;
            if(!$user->loggedIn){
                return;
            }
            $location = Location::fromObject($packet->getPosition()->subtract(0, 1.62, 0), $user->player->getLevel(), $packet->getYaw(), $packet->getPitch());
            $user->locationHistory->addLocation($location);
            $user->lastLocation = $user->location;
            $user->location = $location;
            $user->lastYaw = fmod($user->yaw, 360);
            $user->lastPitch = fmod($user->pitch, 360);
            $user->yaw = $location->yaw;
            $user->pitch = $location->pitch;
            $movePacket = PacketUtils::playerAuthToMovePlayer($packet, $user);
            if($user->timeSinceTeleport > 0){
                $user->lastMoveDelta = $user->moveDelta;
                $user->moveDelta = $user->location->subtract($user->lastLocation);
                $user->lastYawDelta = $user->yawDelta;
                $user->lastPitchDelta = $user->pitchDelta;
                $user->yawDelta = abs($user->lastYaw - $user->yaw);
                $user->pitchDelta = abs($user->lastPitch - $user->pitch);
            }
            ++$user->timeSinceTeleport;
            ++$user->timeSinceDamage;
            ++$user->timeSinceAttack;
            ++$user->timeSinceJoin;
            ++$user->timeSinceMotion;
            $user->onGround = $movePacket->onGround;
            if($user->onGround){
                ++$user->onGroundTicks;
                $user->offGroundTicks = 0;
            } else {
                ++$user->offGroundTicks;
                $user->onGroundTicks = 0;
            }
            $AABB = AABB::from($user);
            $AABB2 = clone $AABB;
            $AABB->maxY = $AABB->minX;
            $AABB->minY -= 0.01;
            $user->blockBelow = $user->player->getLevel()->getCollisionBlocks($AABB, true)[0] ?? null;
            $AABB2->minY = $AABB2->maxY;
            $AABB2->maxY += 0.01;
            $user->blockAbove = $user->player->getLevel()->getCollisionBlocks($AABB2, true)[0] ?? null;
            if(microtime(true) - $user->lastSentNetworkLatencyTime >= 1){
                if(++$this->ticks >= 20){
                    $user->lastSentNetworkLatencyTime = microtime(true);
                    $pk = new NetworkStackLatencyPacket();
                    $pk->timestamp = 1000;
                    $pk->needResponse = true;
                    $user->player->dataPacket($pk);
                }
            } else {
                $this->ticks = 0;
            }
            // now this is important - otherwise everything will break
            $user->player->handleMovePlayer($movePacket);
        }
    }

}