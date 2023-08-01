<?php

namespace Will;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use onebone\economyapi\EconomyAPI;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

class XpShop extends PluginBase implements Listener {

    private Config $config;

    public function onEnable(): void {
        $this->getLogger()->info("XpShop enabled!");

        // Load the configuration file
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
    }

    public function onDisable(): void {
        $this->getLogger()->info("XpShop disabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "xpshop") {
            if ($sender instanceof Player) {
                $this->openSellBuyForm($sender);
            } else {
                $sender->sendMessage("This command can only be executed in-game.");
            }
            return true;
        }

        return false;
    }

    private function openSellBuyForm(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data !== null) {
                if ($data === 0) {
                    $this->sellXp($player);
                } elseif ($data === 1) {
                    $this->openBuyForm($player);
                }
            }
        });

        $form->setTitle(">> §2XP Shop§r <<");
        $form->setContent("§eWelcome To XpShop \n§aHere You Can Sell Or Buy Xp With Different Price \n§bSelect an option:");
        $form->addButton("Sell XP");
        $form->addButton("Buy XP");

        $player->sendForm($form);
    }

    private function sellXp(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data !== null) {
                $xpAmount = (int) $data[0];
                $this->processSellXp($player, $xpAmount);
            }
        });

        $form->setTitle("Sell XP");
        $form->addSlider("Amount of XP to sell", 0, $player->getXpManager()->getXpLevel(), 1, 0);
        $form->addLabel("Current XP Level: " . $player->getXpManager()->getXpLevel());
        $form->addLabel("Sell Price per XP: " . $this->config->get("sell-xp-price", 500));
        $player->sendForm($form);
    }

    private function processSellXp(Player $player, int $xpAmount): void {
        $playerXp = $player->getXpManager()->getXpLevel();
        if ($playerXp < $xpAmount) {
            $player->sendMessage("§l§c[ERROR] §r§cYou don't have enough XP to sell.");
            return;
        }

        $xpPrice = $xpAmount * $this->config->get("sell-xp-price", 500);
        $player->getXpManager()->subtractXpLevels($xpAmount);
        EconomyAPI::getInstance()->addMoney($player, $xpPrice);
        $player->sendMessage("§l§7[§aSUCCESS§7] §r§eYou sold §a" . $xpAmount . " §eXP for §a" . $xpPrice . "Money");
    }

    private function openBuyForm(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data !== null) {
                $xpAmount = (int) $data[0];
                $this->processBuyXp($player, $xpAmount);
            }
        });

        $form->setTitle("Buy XP");
        $form->addSlider("Amount of XP to buy", 0, 150, 1, 0);
        $form->addLabel("Current Money: " . EconomyAPI::getInstance()->myMoney($player));
        $form->addLabel("Buy Price per XP: " . $this->config->get("buy-xp-price", 500));
        $player->sendForm($form);
    }

    private function processBuyXp(Player $player, int $xpAmount): void {
        $xpPrice = $xpAmount * $this->config->get("buy-xp-price", 500);
        $playerMoney = EconomyAPI::getInstance()->myMoney($player);

        if ($playerMoney >= $xpPrice) {
            $player->getXpManager()->addXpLevels($xpAmount);
            EconomyAPI::getInstance()->reduceMoney($player, $xpPrice);
            $player->sendMessage("§l§7[§aSUCCESS§7] §r§eYou bought §a" . $xpAmount . " §eXP for §a" . $xpPrice . "Money");
        } else {
            $player->sendMessage("§l§c[ERROR] §r§cYou don't have enough money to buy XP.");
        }
    }
}
