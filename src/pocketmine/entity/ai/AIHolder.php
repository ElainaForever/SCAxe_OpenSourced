<?php
/*
 * Based on the amazing MyOwnWorld written by Zzm !!!
*/

namespace pocketmine\entity\ai;

use pocketmine\entity\IronGolem;
use pocketmine\entity\Mooshroom;
use pocketmine\entity\Ocelot;
use pocketmine\entity\PigZombie;
use pocketmine\entity\SnowGolem;
use pocketmine\entity\Wolf;
use pocketmine\event\entity\EntityGenerateEvent;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\entity\Zombie;
use pocketmine\level\format\FullChunk;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

use pocketmine\entity\Creeper;
use pocketmine\entity\Skeleton;
use pocketmine\entity\Cow;
use pocketmine\entity\Pig;
use pocketmine\entity\Sheep;
use pocketmine\entity\Chicken;

class AIHolder{
	public $ZombieAI;
	public $CreeperAI;
	public $SkeletonAI;
	public $CowAI;
	public $PigAI;
	public $SheepAI;
	public $ChickenAI;
	public $IronGolemAI;
	public $SnowGolemAI;
	public $PigZombieAI;

	public $zombie = [];
	public $Creeper = [];
	public $Skeleton = [];
	public $Cow = [];
	public $Pig = [];
	public $Sheep = [];
	public $Chicken = [];
	public $irongolem = [];
	public $snowgolem = [];
	public $pigzombie = [];


	public $birth_r = 30;

	public $tasks = [];

	public $server;

	public function getServer(){
		return $this->server;
	}

	public function __construct(Server $server){
		$this->server = $server;

		if($this->server->aiConfig["mobgenerate"]){
			$this->tasks['ZombieGenerate'] = $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([
				$this,
				"MobGenerate"
			]), 20 * 45);
		}


		/*$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([
			$this,
			"TimeFix"
		]), 20);*/

		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask ([$this, "RotationTimer"]), 2);

		$this->ZombieAI = new ZombieAI($this);
		$this->CowAI = new CowAI($this);
		//$this->PigAI = new PigAI($this);
		//$this->SheepAI = new SheepAI($this);
		//TODO: improve AIs below
		$this->ChickenAI = new ChickenAI($this);
		$this->CreeperAI = new CreeperAI($this);
		$this->SkeletonAI = new SkeletonAI($this);
		$this->IronGolemAI = new IronGolemAI($this);
		//$this->PigZombieAI = new PigZombieAI($this);
	}

	/*
	 ************ API ���� ************************************
	 */

	/**
	 * @param $r
	 * ���ý�ʬ��ް뾶
	 */
	public function setZombieHatred_r($r){
		$this->ZombieAI->hatred_r = $r;
	}

	/**
	 * @param $r
	 * ����ҹ��ˢ��ʬ��Χ����ÿ�����Ϊ���ģ�
	 */
	public function setZombieBirth_r($r){
		$this->birth_r = $r;
	}

	/**
	 * @param $v
	 * ���ý�ʬ���ģʽ�µ���·�ٶ�
	 */
	public function setZombieHate_v($v){
		$this->ZombieAI->zo_hate_v = $v;
	}

	/**
	 * @param $tick
	 * @return bool
	 * ��������ˢ�ּ�ʱ��
	 * �������ڸ���ˢ��ʱ������
	 */
	public function RestartSpawnTimer($tick = 1200){
		$task = $this->tasks['ZombieGenerate'];
		if($task instanceof TaskHandler){
			//TODO û������Ƿ���Ч������
			$task->cancel();
			$task->run($tick);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @return bool
	 * ֹͣˢ�ּ�ʱ��
	 */
	public function CancelSpawnTimer(){
		$task = $this->tasks['ZombieGenerate'];
		if($task instanceof TaskHandler){
			$task->cancel();
			return true;
		}else{
			return false;
		}
	}

	public function TimeFix(){
		foreach($this->getServer()->getLevels() as $level){
			if($level->getTime() > 24000){
				$level->setTime(0);
			}
		}
	}

	/**
	 * @param Position $pos ����λ������(����)
	 * @param int      $maxHealth ���Ѫ��
	 * @param int      $health Ѫ��
	 *                            ����һֻ��ʬ��ĳ����
	 */
	public function spawnZombie(Position $pos, $maxHealth = 20, $health = 20){
		$chunk = $pos->level->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$nbt = $this->getNBT();
		$zo = new Zombie($chunk, $nbt);
		$zo->setPosition($pos);
		$zo->setMaxHealth($maxHealth);
		$zo->setHealth($health);
		$zo->spawnToAll();
		//$this->getLogger()->info("������һֻ��ʬ");
	}

	/**
	 * @param Position $pos ����λ������(����)
	 * @param int      $maxHealth ���Ѫ��
	 * @param int      $health Ѫ��
	 *                            ����һֻ��������ĳ����
	 */
	public function spawnCreeper(Position $pos, $maxHealth = 20, $health = 20){
		$chunk = $pos->level->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$nbt = $this->getNBT();
		$co = new Creeper($chunk, $nbt);
		$co->setPosition($pos);
		$co->setMaxHealth($maxHealth);
		$co->setHealth($health);
		$co->spawnToAll();
		//$this->getLogger()->info("������һֻ������");
	}

	/**
	 * @param Position $pos ����λ������(����)
	 * @param int      $maxHealth ���Ѫ��
	 * @param int      $health Ѫ��
	 *                            ����һֻ���ù�������ĳ����
	 */
	public function spawnSkeleton(Position $pos, $maxHealth = 20, $health = 20){
		$chunk = $pos->level->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$nbt = $this->getNBT();
		$so = new Skeleton($chunk, $nbt);
		$so->setPosition($pos);
		$so->setMaxHealth($maxHealth);
		$so->setHealth($health);
		$so->spawnToAll();
		//$this->getLogger()->info("������һֻ���ù�����");
	}

    /**
	 * @param Position $pos ����λ������(����)
	 * @param int      $maxHealth ���Ѫ��
	 * @param int      $health Ѫ��
	 *                            ����һֻ�i��ĳ����
	 */
	public function spawnCow(Position $pos, $maxHealth = 10, $health = 10){
		$chunk = $pos->level->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$nbt = $this->getNBT();
		$coo = new Cow($chunk, $nbt);
		$coo->setPosition($pos);
		$coo->setMaxHealth($maxHealth);
		$coo->setHealth($health);
		$coo->spawnToAll();
		//$this->getLogger()->info("������һֻţ");
	}
	/**
	 * @param Position $pos ����λ������(����)
	 * @param int      $maxHealth ���Ѫ��
	 * @param int      $health Ѫ��
	 *                            ����һֻ�i��ĳ����
	 */
	public function spawnPig(Position $pos, $maxHealth = 10, $health = 10){
		$chunk = $pos->level->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$nbt = $this->getNBT();
		$po = new Pig($chunk, $nbt);
		$po->setPosition($pos);
		$po->setMaxHealth($maxHealth);
		$po->setHealth($health);
		$po->spawnToAll();
		//$this->getLogger()->info("������һֻ�i");
	}

	/**
	 * @param Position $pos ����λ������(����)
	 * @param int      $maxHealth ���Ѫ��
	 * @param int      $health Ѫ��
	 *                            ����һֻ����ĳ����
	 */
	public function spawnSheep(Position $pos, $maxHealth = 10, $health = 10){
		$chunk = $pos->level->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$nbt = $this->getNBT();
		$sho = new Sheep($chunk, $nbt);
		$sho->setPosition($pos);
		$sho->setMaxHealth($maxHealth);
		$sho->setHealth($health);
		$sho->spawnToAll();
		//$this->getLogger()->info("������һֻ��");
	}

	/**
	 * @param Position $pos ����λ������(����)
	 * @param int      $maxHealth ���Ѫ��
	 * @param int      $health Ѫ��
	 *                            ����һֻ�u��ĳ����
	 */
	public function spawnChicken(Position $pos, $maxHealth = 5, $health = 5){
		$chunk = $pos->level->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$nbt = $this->getNBT();
		$cho = new Chicken($chunk, $nbt);
		$cho->setPosition($pos);
		$cho->setMaxHealth($maxHealth);
		$cho->setHealth($health);
		$cho->spawnToAll();
		//$this->getLogger()->info("������һֻ�u");
	}

	/**
	 * @param $zoHealth
	 * @return int
	 * ���ݽ�ʬѪ����ȡ��Ӧ����ֵ
	 */
	public function getZombieDamage($zoHealth){
		$dif = $this->getServer()->getDifficulty();
		switch($dif){
			case 0:
				return 0;
				break;
			case 1:
				if($zoHealth <= 20 and $zoHealth >= 16){
					return 2;
				}elseif($zoHealth <= 11 and $zoHealth >= 15){
					return 3;
				}elseif($zoHealth <= 6 and $zoHealth >= 10){
					return 3;
				}elseif($zoHealth <= 1 and $zoHealth >= 5){
					return 4;
				}else return 5;
				break;
			case 2:
				if($zoHealth <= 20 and $zoHealth >= 16){
					return 3;
				}elseif($zoHealth <= 11 and $zoHealth >= 15){
					return 4;
				}elseif($zoHealth <= 6 and $zoHealth >= 10){
					return 5;
				}elseif($zoHealth <= 1 and $zoHealth >= 5){
					return 6;
				}else return 7;
				break;
			case 3:
				if($zoHealth <= 20 and $zoHealth >= 16){
					return 4;
				}elseif($zoHealth <= 11 and $zoHealth >= 15){
					return 6;
				}elseif($zoHealth <= 6 and $zoHealth >= 10){
					return 7;
				}elseif($zoHealth <= 1 and $zoHealth >= 5){
					return 9;
				}else return 10;
				break;
		}
		return 0;
	}

	public function getSkeletonDamage($zoHealth){
		$dif = $this->getServer()->getDifficulty();
		switch($dif){
			case 0:
				return 0;
				break;
			case 1:
				if($zoHealth <= 20 and $zoHealth >= 16){
					return 2;
				}elseif($zoHealth <= 11 and $zoHealth >= 15){
					return 3;
				}elseif($zoHealth <= 6 and $zoHealth >= 10){
					return 3;
				}elseif($zoHealth <= 1 and $zoHealth >= 5){
					return 4;
				}else return 5;
				break;
			case 2:
				if($zoHealth <= 20 and $zoHealth >= 16){
					return 3;
				}elseif($zoHealth <= 11 and $zoHealth >= 15){
					return 4;
				}elseif($zoHealth <= 6 and $zoHealth >= 10){
					return 5;
				}elseif($zoHealth <= 1 and $zoHealth >= 5){
					return 6;
				}else return 7;
				break;
			case 3:
				if($zoHealth <= 20 and $zoHealth >= 16){
					return 4;
				}elseif($zoHealth <= 11 and $zoHealth >= 15){
					return 6;
				}elseif($zoHealth <= 6 and $zoHealth >= 10){
					return 7;
				}elseif($zoHealth <= 1 and $zoHealth >= 5){
					return 9;
				}else return 10;
				break;
		}
		return 0;
	}

	/**
	 * @param Player $player
	 * @param        $damage
	 * @return float
	 * ������ҵ�װ����ȡ���Ӧ�ܵ����˺�ֵ
	 */
	public function getPlayerDamage(Player $player, $damage){
		$armorValues = [
			Item::LEATHER_CAP => 1,
			Item::LEATHER_TUNIC => 3,
			Item::LEATHER_PANTS => 2,
			Item::LEATHER_BOOTS => 1,
			Item::CHAIN_HELMET => 1,
			Item::CHAIN_CHESTPLATE => 5,
			Item::CHAIN_LEGGINGS => 4,
			Item::CHAIN_BOOTS => 1,
			Item::GOLD_HELMET => 1,
			Item::GOLD_CHESTPLATE => 5,
			Item::GOLD_LEGGINGS => 3,
			Item::GOLD_BOOTS => 1,
			Item::IRON_HELMET => 2,
			Item::IRON_CHESTPLATE => 6,
			Item::IRON_LEGGINGS => 5,
			Item::IRON_BOOTS => 2,
			Item::DIAMOND_HELMET => 3,
			Item::DIAMOND_CHESTPLATE => 8,
			Item::DIAMOND_LEGGINGS => 6,
			Item::DIAMOND_BOOTS => 3,
		];
		$points = 0;
		foreach($player->getInventory()->getArmorContents() as $index => $i){
			if(isset($armorValues[$i->getId()])){
				$points += $armorValues[$i->getId()];
			}
		}
		$damage = floor($damage - $points * 0.04);
		if($damage < 0){
			$damage = 0;
		}
		return $damage;
	}

	/**
	 * @return CompoundTag
	 * ����һ���յ�ʵ��ͨ��NBT
	 */
	public function getNBT() : CompoundTag{
		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", 0),
				new FloatTag("", 0)
			]),
		]);
		return $nbt;
	}

	/**
	 * @param Position $pos
	 * @return int
	 * ��ȡĳ����(λ��)������
	 */
	public function getLight(Position $pos){
		$chunk = $pos->getLevel()->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$l = 0;
		if($chunk instanceof FullChunk){
			$l = $chunk->getBlockSkyLight($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f);
			if($l < 15){
				//$l = \max($chunk->getBlockLight($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f));
				$l = $chunk->getBlockLight($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f);
			}
		}
		return $l;
	}

	/******** API���� ����Ϊ��ʱ�� *****************************/

	/**
	 * @param Entity $entity
	 * @return bool
	 * �ж�ĳ�����ܱ�32�����Ƿ�����Ҵ���
	 * ���ƽ�ʬ�Ƿ��ƶ�����������ģʽ��
	 */
	public function willMove(Entity $entity){
		foreach($entity->getViewers() as $viewer){
			if($entity->distance($viewer->getLocation()) <= 32) return true;
		}
		return false;
	}

	public function RotationTimer(){
		foreach($this->getServer()->getLevels() as $level){
			foreach($level->getEntities() as $entity){
				if($entity instanceof Zombie or $entity instanceof Creeper or $entity instanceof Skeleton or $entity instanceof Cow or $entity instanceof Pig or $entity instanceof Sheep or $entity
					instanceof Chicken or $entity instanceof Mooshroom or $entity instanceof Ocelot or $entity instanceof Wolf or $entity instanceof PigZombie
				){
					if(count($entity->getViewers()) != 0){
						if($entity instanceof Zombie or $entity instanceof PigZombie){
							$array = &$this->zombie;
						}elseif($entity instanceof Creeper){
							$array = &$this->Creeper;
						}elseif($entity instanceof Skeleton){
							$array = &$this->Skeleton;
						}elseif($entity instanceof Cow or $entity instanceof Mooshroom or $entity instanceof Pig or $entity instanceof Sheep or $entity instanceof Ocelot or $entity instanceof Wolf){
							$array = &$this->Cow;
						}elseif($entity instanceof Pig){
							$array = &$this->Pig;
						}elseif($entity instanceof Sheep){
							$array = &$this->Sheep;
						}elseif($entity instanceof Chicken){
							$array = &$this->Chicken;
						}elseif($entity instanceof IronGolem){
							$array = &$this->irongolem;
						}elseif($entity instanceof SnowGolem){
							$array = &$this->snowgolem;
						}
						if(isset($array[$entity->getId()])){
							$yaw0 = $entity->yaw;  //ʵ��yaw
							$yaw = $array[$entity->getId()]['yaw']; //Ŀ��yaw
							//$this->getLogger()->info($yaw0.' '.$yaw);
							if(abs($yaw0 - $yaw) <= 180){  //-180��+180������
								if($yaw0 <= $yaw){  //ʵ����Ŀ�����
									if($yaw - $yaw0 <= 15){
										$yaw0 = $yaw;
									}else{
										$yaw0 += 15;
									}
								}else{  ////ʵ����Ŀ���ұ�
									if($yaw0 - $yaw <= 15){
										$yaw0 = $yaw;
									}else{
										$yaw0 -= 15;
									}
								}
							}else{  ////+180��-180����
								if($yaw0 >= $yaw){  //ʵ����Ŀ�����
									if((180 - $yaw0) + ($yaw + 180) <= 15){
										$yaw0 = $yaw;
									}else{
										$yaw0 += 15;
										if($yaw0 >= 180) $yaw0 = $yaw0 - 360;
									}
								}else{  ////ʵ����Ŀ���ұ�
									if((180 - $yaw) - ($yaw0 + 180) <= 15){
										$yaw0 = $yaw;
									}else{
										$yaw0 -= 15;
										if($yaw0 <= 180) $yaw0 = $yaw0 + 360;
									}
								}
							}
							$pitch0 = $entity->pitch;  //ʵ��pitch
							$pitch = $array[$entity->getId()]['pitch']; //Ŀ��pitch

							if(abs($pitch0 - $pitch) <= 15){
								$pitch0 = $pitch;
							}elseif($pitch > $pitch0){
								$pitch0 += 10;
							}elseif($pitch < $pitch0){
								$pitch0 -= 10;
							}

							$entity->setRotation($yaw0, $pitch0);
							//$this->RotateHead($entity,$yaw);
						}
					}
				}
			}
		}
	}

	/**
	 * @param $mx
	 * @param $mz
	 * @return float|int
	 * ��ȡyaw�Ƕ�
	 */
	public function getyaw($mx, $mz){  //����motion����ת��Ƕ�
		//ת�����
		if($mz == 0){  //б�ʲ�����
			if($mx < 0){
				$yaw = -90;
			}else{
				$yaw = 90;
			}
		}else{  //����б��
			if($mx >= 0 and $mz > 0){  //��һ����
				$atan = atan($mx / $mz);
				$yaw = rad2deg($atan);
			}elseif($mx >= 0 and $mz < 0){  //�ڶ�����
				$atan = atan($mx / abs($mz));
				$yaw = 180 - rad2deg($atan);
			}elseif($mx < 0 and $mz < 0){  //��������
				$atan = atan($mx / $mz);
				$yaw = -(180 - rad2deg($atan));
			}elseif($mx < 0 and $mz > 0){  //��������
				$atan = atan(abs($mx) / $mz);
				$yaw = -(rad2deg($atan));
			}else{
				$yaw = 0;
			}
		}

		$yaw = -$yaw;
		return $yaw;
	}

	/**
	 * @param Vector3 $from
	 * @param Vector3 $to
	 * @return float|int
	 * ��ȡpitch�Ƕ�
	 */
	public function getpitch(Vector3 $from, Vector3 $to){
		$distance = $from->distance($to);
		$height = $to->y - $from->y;
		if($height > 0){
			return -rad2deg(asin($height / $distance));
		}elseif($height < 0){
			return rad2deg(asin(-$height / $distance));
		}else{
			return 0;
		}
	}

	/**
	 * @param Level   $level
	 * @param Vector3 $v3
	 * @param bool    $hate
	 * @param bool    $reason
	 * @return bool|float|string
	 * �ж�ĳ�����Ƿ��������
	 * ������ԭ��
	 */
	public function ifjump(Level $level, Vector3 $v3, $hate = false, $reason = false){  //boybook Y���㷨���ĺ���
		$x = floor($v3->getX());
		$y = floor($v3->getY());
		$z = floor($v3->getZ());

		//echo ($y." ");
		if($this->whatBlock($level, new Vector3($x, $y, $z)) == "air"){
			//echo "ǰ������ ";
			if($this->whatBlock($level, new Vector3($x, $y - 1, $z)) == "block" or new Vector3($x, $y - 1, $z) == "climb"){  //����
				//echo "������ǰ ";
				if($this->whatBlock($level, new Vector3($x, $y + 1, $z)) == "block" or $this->whatBlock($level, new Vector3($x, $y + 1, $z)) == "half" or $this->whatBlock($level, new Vector3($x, $y + 1, $z)) == "high"){  //�Ϸ�һ�񱻶�ס��
					//echo "�Ϸ���ס \n";
					if($reason) return 'up!';
					return false;  //�Ϸ���ס
				}else{
					//echo "GO��ǰ�� \n";
					if($reason) return 'GO';
					return $y;  //��ǰ��
				}
			}elseif ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "water") {  //ˮ
			//echo "��ˮ��Ӿ \n";
				if ($reason) return 'swim';
				return $y-1;  //����һ����ǰ�ߣ���ˮ��Ӿ��
			}
			elseif($this->whatBlock($level, new Vector3($x, $y - 1, $z)) == "half"){  //��ש
				//echo "�µ���ש \n";
				if($reason) return 'half';
				return $y - 0.5;  //������0.5��
			}elseif($this->whatBlock($level, new Vector3($x, $y - 1, $z)) == "lava"){  //�ҽ�
				//echo "ǰ���ҽ� \n";
				if($reason) return 'lava';
				return false;  //ǰ���ҽ�
			}elseif($this->whatBlock($level, new Vector3($x, $y - 1, $z)) == "air"){  //����
				//echo "���������� ";
				if($this->whatBlock($level, new Vector3($x, $y - 2, $z)) == "block"){
					//echo "GO������ \n";
					if($reason) return 'down';
					return $y - 1;  //������
				}else{ //ǰ������
					//echo "ǰ������ \n";
					if($reason) return 'fall';
					/*	if ($hate === false) {
							return false;
						}
						else {
							return $y-1;  //������
						}*/
				}
			}
		}
		elseif ($this->whatBlock($level,new Vector3($x,$y,$z)) == "water") {  //ˮ
		//echo "����ˮ��";
			if ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "water") {  //���滹��ˮ
			//echo "������ \n";
				if ($reason) return 'inwater';
				return $y+1;  //�����Σ�����ˮ
			}
			elseif ($this->whatBlock($level,new Vector3($x,$y+1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y+1,$z)) == "half") {  //�Ϸ�һ�񱻶�ס��
				if ($this->whatBlock($level,new Vector3($x,$y-1,$z)) == "block" or $this->whatBlock($level,new Vector3($x,$y-1,$z)) == "half") {  //�·�һ��Ҳ��ס��
				//echo "���¶�����ס \n";
					if ($reason) return 'up!_down!';
					return false;  //���¶�����ס
				}
				else {
				//echo "������ \n";
					if ($reason) return 'up!';
					return $y-1;  //�����Σ�����ס
				}
			}
			else {
			//echo "��Ӿing... \n";
				return $y;  //��ǰ��
			}
		}
		elseif($this->whatBlock($level, new Vector3($x, $y, $z)) == "half"){  //��ש
			//echo "ǰ����ש \n";
			if($this->whatBlock($level, new Vector3($x, $y + 1, $z)) == "block" or $this->whatBlock($level, new Vector3($x, $y + 1, $z)) == "half" or $this->whatBlock($level, new Vector3($x, $y + 1, $z)) == "high"){  //�Ϸ�һ�񱻶�ס��
				//return false;  //�Ϸ���ס
			}else{
				if($reason) return 'halfGO';
				return $y + 0.5;
			}

		}elseif($this->whatBlock($level, new Vector3($x, $y, $z)) == "lava"){  //�ҽ�
			//echo "ǰ���ҽ� \n";
			if($reason) return 'lava';
			return false;
		}elseif($this->whatBlock($level, new Vector3($x, $y, $z)) == "high"){  //1.5��߷���
			//echo "ǰ��դ�� \n";
			if($reason) return 'high';
			return false;
		}elseif($this->whatBlock($level, new Vector3($x, $y, $z)) == "climb"){  //����
			//echo "ǰ������ \n";
			//return $y;
			if($reason) return 'climb';
			if($hate){
				return $y + 0.7;
			}else{
				return $y + 0.5;
			}
		}else{  //��������
			//echo "�������� ";
			if($this->whatBlock($level, new Vector3($x, $y + 1, $z)) != "air"){  //ǰ������ǽ
				//echo "ǰ����ǽ \n";
				if($reason) return 'wall';
				return false;
			}else{
				if($this->whatBlock($level, new Vector3($x, $y + 2, $z)) == "block" or $this->whatBlock($level, new Vector3($x, $y + 2, $z)) == "half" or $this->whatBlock($level, new Vector3($x, $y + 2, $z)) == "high"){  //�Ϸ����񱻶�ס��
					//echo "2�񴦱��� \n";
					if($reason) return 'up2!';
					return false;
				}else{
					//echo "GO������ \n";
					if($reason) return 'upGO';
					return $y + 1;  //������
				}
			}
		}
		return false;
	}

	public function whatBlock(Level $level, $v3){  //boybook��y���жϷ� ���� ʲô���飿
		$block = $level->getBlock($v3);
		$id = $block->getID();
		$damage = $block->getDamage();
		switch($id){
			case 0:
			case 6:
			case 27:
			case 30:
			case 31:
			case 37:
			case 38:
			case 39:
			case 40:
			case 50:
			case 51:
			case 63:
			case 66:
			case 68:
			case 78:
			case 111:
			case 141:
			case 142:
			case 171:
			case 175:
			case 244:
			case 323:
				//����ײ����İ�
			case 78:
			case 70:
			case 72:
			case 147:
			case 148:

				//͸������
				return "air";
				break;
			case 8:
			case 9:
				//ˮ
				return "water";
				break;
			case 10:
			case 11:
				//�ҽ�
				return "lava";
				break;
			case 44:
			case 158:
				//��ש
				if($damage >= 8){
					return "block";
				}else{
					return "half";
				}
				break;
			case 64:
				//��
				//var_dump($damage." ");
				//TODO ��֪����ж����Ƿ�������Ϊ����������Զ����
				if($block->isOpened()){
					return "air";
				}else{
					return "block";
				}
				break;
			case 85:
			case 107:
			case 139:
				//1.5��ߵ��޷���Ծ��
				return "high";
				break;
			case 65:
			case 106:
				//��������
				return "climb";
				break;
			default:
				//��ͨ����
				return "block";
				break;
		}
	}

	public function MobDeath(EntityDeathEvent $event){
		//var_dump("death");
		$entity = $event->getEntity();
		if($entity instanceof Zombie){
			$eid = $entity->getID();
			if(isset($this->zombie[$eid])){
				unset($this->zombie[$eid]);
			}
		}
		if($entity instanceof Creeper){
			$eid = $entity->getID();
			if(isset($this->Creeper[$eid])){
				unset($this->Creeper[$eid]);
			}
		}
	}

	/**
	 * ˢ��ʬ��ʱ��
	 */
	public function MobGenerate(){
		foreach($this->getServer()->getOnlinePlayers() as $p){
			//$this->server->getLogger()->info("׼�����ɽ�ʬ");
			$level = $p->getLevel();
			$max = 15;
			//if ($level->getTime() >= 13500) {  //��ҹ��
			//$this->server->getLogger()->info("ʱ��OK");
			$v3 = new Vector3($p->getX() + mt_rand(-$this->birth_r, $this->birth_r), $p->getY(), $p->getZ() + mt_rand(-$this->birth_r, $this->birth_r));
			for($y0 = $p->getY() - 10; $y0 <= $p->getY() + 10; $y0++){
				$v3->y = $y0;
				if($this->whatBlock($level, $v3) == "block"){
					//$this->server->getLogger()->info("����OK");
					$v3_1 = $v3;
					$v3_1->y = $y0 + 1;
					$v3_2 = $v3;
					$v3_2->y = $y0 + 2;
					$random = mt_rand(0, 5);


					if($level->getBlock($v3_1)->getID() == 0 and $level->getBlock($v3_2)->getID() == 0){  //�ҵ�����
						/** @var Entity[] $zoC */
						$zoC = [];
						/** @var Entity[] $cowc */
						$cowc = [];
						$sheepc = [];
						$pigc = [];
						$chickenc = [];
						$skeletonc = [];
						foreach($level->getEntities() as $zo){
							if($zo instanceof Zombie) $zoC[] = $zo;
							if($zo instanceof Cow) $cowc[] = $zo;
							if($zo instanceof Sheep) $sheepc[] = $zo;
							if($zo instanceof Pig) $pigc[] = $zo;
							if($zo instanceof Chicken) $chickenc[] = $zo;
							if($zo instanceof Skeleton) $skeletonc[] = $zo;
						}
						
						if(count($skeletonc) > $max){
							for($i = 0; $i < (count($skeletonc) - $max); $i++) $skeletonc[$i]->kill();
						}elseif($random == 0 && $level->getTime() >= 13500){
							$pos = new Position($v3->x, $v3->y, $v3->z, $level);

							$this->server->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, Skeleton::NETWORK_ID, EntityGenerateEvent::CAUSE_AI_HOLDER));
							if(!$ev->isCancelled()){
								$this->spawnSkeleton($ev->getPosition());
							}
							//$this->server->getLogger()->info("����1����");
						}
						
						if(count($chickenc) > $max){
							for($i = 0; $i < (count($chickenc) - $max); $i++) $chickenc[$i]->kill();
						}elseif($random == 1){
							$pos = new Position($v3->x, $v3->y, $v3->z, $level);

							$this->server->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, Chicken::NETWORK_ID, EntityGenerateEvent::CAUSE_AI_HOLDER));
							if(!$ev->isCancelled()){
								$this->spawnChicken($ev->getPosition());
							}
							//$this->server->getLogger()->info("����1С��");
						}
					
                        if(count($sheepc) > $max){
							for($i = 0; $i < (count($sheepc) - $max); $i++) $sheepc[$i]->kill();
						}elseif($random == 2){
							$pos = new Position($v3->x, $v3->y, $v3->z, $level);

							$this->server->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, Sheep::NETWORK_ID, EntityGenerateEvent::CAUSE_AI_HOLDER));
							if(!$ev->isCancelled()){
								$this->spawnSheep($ev->getPosition());
							}
							//$this->server->getLogger()->info("����1����");
						}
						
						if(count($pigc) > $max){
							for($i = 0; $i < (count($pigc) - $max); $i++) $pigc[$i]->kill();
						}elseif($random == 3){
							$pos = new Position($v3->x, $v3->y, $v3->z, $level);

							$this->server->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, Pig::NETWORK_ID, EntityGenerateEvent::CAUSE_AI_HOLDER));
							if(!$ev->isCancelled()){
								$this->spawnPig($ev->getPosition());
							}
							//$this->server->getLogger()->info("����1С��");
						}

						if(count($zoC) > $max){
							for($i = 0; $i < (count($zoC) - $max); $i++) $zoC[$i]->kill();
						}elseif($random == 4 && $level->getTime() >= 13500){
							$pos = new Position($v3->x, $v3->y, $v3->z, $level);

							$this->server->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, Zombie::NETWORK_ID, EntityGenerateEvent::CAUSE_AI_HOLDER));
							if(!$ev->isCancelled()){
								$this->spawnZombie($ev->getPosition());
							}
							//$this->server->getLogger()->info("����1��ʬ");
						}

						if(count($cowc) > $max){
							for($i = 0; $i < (count($cowc) - $max); $i++) $cowc[$i]->kill();
						}elseif($random == 5){
							$pos = new Position($v3->x, $v3->y, $v3->z, $level);

							$this->server->getPluginManager()->callEvent($ev = new EntityGenerateEvent($pos, Cow::NETWORK_ID, EntityGenerateEvent::CAUSE_AI_HOLDER));
							if(!$ev->isCancelled()){
								$this->spawnCow($ev->getPosition());
							}
							//$this->server->getLogger()->info("����1ţ");
						}
						break;
					}
				}
			}
		}
	}

	public function EntityDamage(EntityDamageEvent $event){  //�����޸�
		if($event instanceof EntityDamageByEntityEvent){
			$p = $event->getDamager();
			$entity = $event->getEntity();
			if($entity instanceof Zombie){
				$array = &$this->zombie;
			}elseif($entity instanceof Creeper){
				$array = &$this->Creeper;
			}elseif($entity instanceof Cow){
				$array = &$this->Cow;
			}elseif($entity instanceof Pig){
				$array = &$this->Pig;
			}elseif($entity instanceof Sheep){
				$array = &$this->Sheep;
			}elseif($entity instanceof Chicken){
				$array = &$this->Chicken;
			}elseif($entity instanceof Skeleton){
				$array = &$this->Skeleton;
			}else{
				$array = [];
			}
			if(isset($array[$entity->getId()])){
				if($p instanceof Player and ($array[$entity->getId()]['canAttack'] == 0)){
					$weapon = $p->getInventory()->getItemInHand()->getID();  //�õ�������е�����
					$high = 0;
					if($weapon == 258 or $weapon == 271 or $weapon == 275){  //����x5
						$back = 1.5;
					}elseif($weapon == 267 or $weapon == 272 or $weapon == 279 or $weapon == 283 or $weapon == 286){  //����x1
						$back = 3;
					}elseif($weapon == 276){  //����x2
						$back = 4;
					}elseif($weapon == 292){  //����x10
						$back = 8;
						$high = 3;
					}else{
						$back = 1;
					}
					//var_dump("���".$p->getName()."������IDΪ".$zo->getId()."��ʵ��");
					$array[$entity->getId()]['x'] = $array[$entity->getId()]['x'] - $array[$entity->getId()]['xxx'] * $back;
					$array[$entity->getId()]['y'] = $entity->getY() + $high;
					$array[$entity->getId()]['z'] = $array[$entity->getId()]['z'] - $array[$entity->getId()]['zzz'] * $back;
					$pos = new Vector3 ($array[$entity->getId()]['x'], $array[$entity->getId()]['y'], $array[$entity->getId()]['z']);  //Ŀ������
					//$entity->setPosition($pos);
					$entity->knockBack($entity, 0, $array[$entity->getId()]['xxx'] * $back, $array[$entity->getId()]['zzz'] * $back);
					if(isset($array[$entity->getId()])){
						$zom = &$array[$entity->getId()];
						$zom['IsChasing'] = $p->getName();
						//var_dump( $zom['IsChasing']);
					}
				}
			}
		}
	}

	public function knockBackover(Entity $entity, Vector3 $v3){
		if($entity instanceof Entity){
			if(isset($this->zombie[$entity->getId()])){
				$entity->setPosition($v3);
				$this->zombie[$entity->getId()]['knockBack'] = false;
			}
			if(isset($this->Cow[$entity->getId()])){
				$entity->setPosition($v3);
				$this->Cow[$entity->getId()]['knockBack'] = false;
			}
			if(isset($this->Pig[$entity->getId()])){
				$entity->setPosition($v3);
				$this->Pig[$entity->getId()]['knockBack'] = false;
			}
			if(isset($this->Sheep[$entity->getId()])){
				$entity->setPosition($v3);
				$this->Sheep[$entity->getId()]['knockBack'] = false;
			}
			if(isset($this->Chicken[$entity->getId()])){
				$entity->setPosition($v3);
				$this->Chicken[$entity->getId()]['knockBack'] = false;
			}
			if(isset($this->Skeleton[$entity->getId()])){
				$entity->setPosition($v3);
				$this->Skeleton[$entity->getId()]['knockBack'] = false;
			}
			if(isset($this->Creeper[$entity->getId()])){
				$entity->setPosition($v3);
				$this->Creeper[$entity->getId()]['knockBack'] = false;
			}
		}
	}

}

