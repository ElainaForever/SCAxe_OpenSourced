<?php

namespace pocketmine\item;

use pocketmine\entity\Effect;

class GoldenApple extends Food{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::GOLDEN_APPLE, $meta, $count, "Golden Apple");
	}

	public function getFoodRestore() : int{
		return 4;
	}

	public function getSaturationRestore() : float{
		return 9.6;
	}

	public function getAdditionalEffects() : array{
		return [
            Effect::getEffect(Effect::REGENERATION)->setDuration(100)->setAmplifier(1),
			Effect::getEffect(Effect::ABSORPTION)->setDuration(2400)
		];
	}
}

