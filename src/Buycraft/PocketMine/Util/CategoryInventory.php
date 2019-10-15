<?php
/**
 * Created by PhpStorm.
 * User: owatt
 * Date: 16/08/18
 * Time: 13:37
 */

namespace Buycraft\PocketMine\Util;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\inventory\ContainerInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket as BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\tile\Tile;

class CategoryInventory extends ContainerInventory
{

    const FAKE_BLOCK_ID = BlockIds::CHEST;
    const FAKE_TILE_ID = Tile::CHEST;

    const SEND_BLOCKS_FAKE = 0;
    const SEND_BLOCKS_REAL = 1;
    const FAKE_BLOCK_DATA = 0;
    const INVENTORY_HEIGHT = 3;

    protected $holders = [];

    protected static $nbtWriter;

    public function __construct()
    {
        parent::__construct(new Vector3());
    }

    public function getDefaultSize(): int
    {
        return 27;
    }

    public function getNetworkType(): int
    {
        return WindowTypes::CONTAINER;
    }

    public function getName(): string
    {
        return "ChestInventory";
    }

    public function onOpen(Player $player): void
    {
        if (!isset($this->holders[$id = $player->getId()])) {
            $this->holders[$id] = $this->holder = $player->floor()->add(0, static::INVENTORY_HEIGHT, 0);
            $this->sendBlocks($player, self::SEND_BLOCKS_FAKE);
            $this->sendFakeTile($player);
            parent::onOpen($player);
        }
    }

    public function onClose(Player $player): void
    {
        if (isset($this->holders[$id = $player->getId()])) {
            parent::onClose($player);
            $this->sendBlocks($player, self::SEND_BLOCKS_REAL);
            unset($this->holders[$id]);
        }
    }

    protected function sendFakeTile(Player $player): void
    {
        $holder = $this->holders[$player->getId()];
        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;
        $tag = new CompoundTag();
        $tag->setString("id", static::FAKE_TILE_ID);
        $customName = "Select a category";
        if ($customName !== null) {
            $tag->setString("CustomName", $customName);
        }
        $pk->namedtag = (self::$nbtWriter ?? (self::$nbtWriter = new NetworkLittleEndianNBTStream()))->write($tag);
        $player->dataPacket($pk);
    }

    protected function sendBlocks(Player $player, int $type): void
    {
        switch ($type) {
            case self::SEND_BLOCKS_FAKE:
                $player->getLevel()->sendBlocks([$player], $this->getFakeBlocks($this->holders[$player->getId()]));
                return;
            case self::SEND_BLOCKS_REAL:
                $player->getLevel()->sendBlocks([$player], $this->getRealBlocks($player, $this->holders[$player->getId()]));
                return;
        }
        throw new \Error("Unhandled type $type provided.");
    }

    protected function getFakeBlocks(Vector3 $holder): array
    {
        return [
            Block::get(static::FAKE_BLOCK_ID, static::FAKE_BLOCK_DATA)->setComponents($holder->x, $holder->y, $holder->z)
        ];
    }

    protected function getRealBlocks(Player $player, Vector3 $holder): array
    {
        return [
            $player->getLevel()->getBlockAt($holder->x, $holder->y, $holder->z)
        ];
    }
}
