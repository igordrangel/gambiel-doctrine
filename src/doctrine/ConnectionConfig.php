<?php
	
	namespace GambiEl\Doctrine\Repository;
	
	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\ORMException;
	use Doctrine\ORM\Tools\Setup;
	use InvalidArgumentException;
	
	class ConnectionConfig {
		private static ?ConnectionConfig $instance = null;
		private EntityManager $entityManager;
		
		/**
		 * @staticvar Singleton $instance
		 * @return static
		 */
		public static function getInstance(): ConnectionConfig {
			if (static::$instance == null) {
				static::$instance = new static();
			}
			return static::$instance;
		}
		
		public function getEntityManager(
			string $host,
			string $dbname,
			string $user,
			string $password,
			string $driver
		): EntityManager {
			try {
				if (empty($this->entityManager)) {
					$isDevMode = true;
					$paths = [__DIR__ . "/src/Entity"];
					
					$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, null, null, false);
					$connection = EntityManager::create([
						'dbname' => $dbname,
						'user' => $user,
						'password' => $password,
						'host' => $host,
						'driver' => $driver,
						'charset' => 'utf8'
					], $config);
					
					$this->entityManager = $connection;
				}
				return $this->entityManager;
			} catch (ORMException $e) {
				throw new InvalidArgumentException($e->getMessage(), 500);
			}
		}
		
	}
