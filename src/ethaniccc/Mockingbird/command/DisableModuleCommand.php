<?php

namespace ethaniccc\Mockingbird\command;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\command\Command;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class DisableModuleCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setDescription("Disable Mockingbird modules!");
        $this->setUsage(TextFormat::RED . "/mbdisable <module_name>");
        $this->setPermission($this->getPlugin()->getConfig()->get("module_permission"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
        if($this->testPermission($sender)){
            if(!isset($args[0])){
                $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You need to specify a module to disable.");
                return;
            }
            $module = $this->getPlugin()->getModuleByName($args[0]);
            if(!$module instanceof Cheat){
                $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "The specified module was not found.");
                return;
            }
            if(!$module->isEnabled()){
                $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "The specified module is already disabled.");
                return;
            }
            $this->getPlugin()->disableModule($module);
            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::GREEN . "The specified module has been disabled!");
        }
    }

    /**
     * @return Mockingbird
     */
    public function getPlugin(): Plugin{
        return $this->plugin;
    }
}