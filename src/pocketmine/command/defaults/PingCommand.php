<?php

/*
 *    ____ _                   _                   
 *  / ___| | _____      _____| |_ ___  _ __   ___ 
 * | |  _| |/ _ \ \ /\ / / __| __/ _ \| '_ \ / _ \
 * | |_| | | (_) \ V  V /\__ \ || (_) | | | |  __/
 *  \____|_|\___/ \_/\_/ |___/\__\___/|_| |_|\___|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Glowstone (Lemdy)
 * @link vk.com/weany
 *
 *此代码来源于vk.com/weany
*/

namespace pocketmine\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PingCommand extends VanillaCommand{

    public function __construct($name){
		parent::__construct(
			$name,
			"get player's ping",
			"/ping (player)"
		);
	}
	
	public function execute(CommandSender $sender, $commandLabel, array $args)
    {

        if (!($sender instanceof Player)) {
            $sender->sendMessage("只能在游戏中运行!");
            return true;
        }

        if (!(isset($args[0]))) {
            $sender->sendMessage("Ping: " . $sender->getPing() . "ms");
            return true;
        } else {
            $target = Server::getInstance()->getPlayer($args[0]);

            if ($target == null) {
                return $sender->sendMessage(TextFormat::RED . "找不到该玩家");
            }

            $sender->sendMessage($target->getName() . "'s ping: " . $target->getPing() . "ms");
        }
        return false;
    }
}
