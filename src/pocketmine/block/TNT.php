<?php
namespace pocketmine\block;
use pocketmine\item\Item;
class TNT extends Solid implements ElectricalAppliance{
	protected $id = self::TNT;
	public function __construct(){
	}
	public function getName() : string{
		return "TNT";
	}
	public function getHardness(){
		return 0;
	}
}
