<?php
	
	namespace GambiEl\Doctrine;
	
	use Doctrine\ORM\EntityManager;
	
	class GambiElEntityManager {
		private static EntityManager $entityManager;
		
		public static function getEntityManager($config): EntityManager {
			$conn = new ConnectionConfig();
			if (empty(self::$entityManager)) {
				self::$entityManager = $conn->getEntityManager(
					$config['host'],
					$config['dbName'],
					$config['user'],
					$config['password'],
					$config['driver'],
					$config['port']
				);
			}
			return self::$entityManager;
		}
	}
