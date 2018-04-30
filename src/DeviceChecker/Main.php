<?php
/**
 * Created by PhpStorm.
 * User: angel
 * Date: 4/30/18
 * Time: 10:56 AM
 */

namespace DeviceChecker;

use pocketmine\utils\Config;

use pocketmine\event\Listener;

use pocketmine\command\Command;

use pocketmine\utils\TextFormat;

use pocketmine\plugin\PluginBase;

use pocketmine\command\CommandSender;

use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\network\mcpe\protocol\LoginPacket;

use pocketmine\event\server\DataPacketReceiveEvent;

/**
 * Class Main
 * @package DeviceChecker
 */
class Main extends PluginBase implements Listener {

	public const OS_TABLE = [
		'Unknown',
		'Android',
		'iOS',
		'macOS',
		'FireOS',
		'Windows 10',
		'Windows',
		'Dedicated',
	];

	/** @var Config */
	private $storage;

	private $cache = [];

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		if(is_dir(($dir = $this->getDataFolder())) == false){
			mkdir($dir);
		}

		$this->storage = new Config($dir . 'PlayersOS.json');

		$this->getLogger()->notice(TextFormat::GREEN . 'Device Checker has been enabled!');
	}

	public function onDisable() : void{
		$this->storage->save(true);
		$this->getLogger()->notice(TextFormat::RED . 'Device Checker has been dsiabled!');
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onData(DataPacketReceiveEvent $event){

		$pk = $event->getPacket();

		if($pk instanceof LoginPacket) {

			$this->cache[$pk->username] = Main::OS_TABLE[$pk->clientData["DeviceOS"]];
		}
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$loginPacketDeviceOS = $this->cache[$player->getName()];
		unset($this->cache[$player->getName()]);

		if($this->storage->exists($player->getName()) == false){
			$this->storage->set($player->getName(), $loginPacketDeviceOS);
			return;
		}

		if($this->storage->get($player->getName()) !== $loginPacketDeviceOS){
			$this->storage->set($player->getName(), $loginPacketDeviceOS);
			return;
		}

	}

	/**
	 * @param CommandSender $sender
	 * @param Command $command
	 * @param string $label
	 * @param array $args
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {

		if(strtolower($command->getName()) == 'device'){

			if($sender->hasPermission('devicechecker.use') == false or $sender->isOp() == false){
				$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
				return false;
			}

			if(isset($args[0]) == false){
				$sender->sendMessage(TextFormat::YELLOW . '/device <player>');
				return false;
			}

			$target = $sender->getServer()->getOfflinePlayer($args[0]);

			if($target->hasPlayedBefore() == false){
				$sender->sendMessage(TextFormat::RED . TextFormat::ITALIC . $args[0] . TextFormat::RESET . TextFormat::RED .' has never joined the server before!');
				return false;
			}

			$os = $this->storage->get($target->getName());
			$sender->sendMessage(TextFormat::GREEN . TextFormat::ITALIC.'-=+{ '.TextFormat::BLUE.$target->getName()."'s OS".TextFormat::GREEN.'}+=-'.PHP_EOL . TextFormat::GRAY .'  - '.TextFormat::GOLD.'OS: '.TextFormat::BOLD.$os.TextFormat::RESET);
			return true;
		}

		return true;
	}
}
