<?php
	
	namespace GambiEl\Doctrine;
	
	use Doctrine\DBAL\ConnectionException;
	use Doctrine\ORM\EntityManager;
	use Doctrine\ORM\NonUniqueResultException;
	use Doctrine\ORM\NoResultException;
	use Doctrine\ORM\OptimisticLockException;
	use Doctrine\ORM\ORMException;
	use Doctrine\ORM\QueryBuilder;
	use Doctrine\ORM\TransactionRequiredException;
	use GambiEl\Doctrine\Filter\DoctrineFilterBase;
	use GambiEl\Doctrine\Join\JoinConfig;
	use GambiEl\Helpers\GambielArrayHelper;
	use InvalidArgumentException;
	
	abstract class RepositoryBase {
		protected QueryBuilder $qb;
		protected string $class;
		protected string $dbConfigName;
		
		private string $selectAlias = "e";
		
		public function __construct(string $dbConfigName, $class, bool $withDistinct = false) {
			$this->dbConfigName = $dbConfigName;
			$this->class = $class;
			$this->qb = $this->EntityManager()->createQueryBuilder();
			$this->qb->from($this->class, 'e');
			$this->configQueryBuilder();
			if ($withDistinct)
				$this->selectAlias = "DISTINCT " . $this->selectAlias;
		}
		
		protected function EntityManager(): EntityManager {
			$conn = new ConnectionConfig();
			
			$dbConfig = $_ENV['GAMBIEL_DB_CONFIG'];
			if (is_array($dbConfig)) {
				$indexConfig = GambielArrayHelper::multidimensionalSearch($dbConfig, [
					"name" => $this->dbConfigName
				]);
				
				if ($indexConfig !== false) {
					$config = $dbConfig[$indexConfig];
					return $conn->getEntityManager(
						$config['host'],
						$config['dbName'],
						$config['user'],
						$config['password'],
						$config['driver'],
						$config['port']
					);
				} else {
					throw new InvalidArgumentException("Database config is not found!");
				}
			} else {
				throw new InvalidArgumentException("Database config is not found!");
			}
		}
		
		protected function configQueryBuilder() { }
		
		public function beginTransaction() {
			$this->EntityManager()->getConnection()->beginTransaction();
		}
		
		public function delete(int $id) {
			try {
				$object = $this->getById($id);
				$this->EntityManager()->remove($object);
			} catch (ORMException $e) {
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			}
		}
		
		public function getById(int $id): ?object {
			try {
				return $this->EntityManager()->find($this->class, $id);
			} catch (OptimisticLockException $e) {
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			} catch (TransactionRequiredException $e) {
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			} catch (ORMException $e) {
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			}
		}
		
		public function commit() {
			try {
				$this->EntityManager()->getConnection()->commit();
			} catch (ConnectionException $e) {
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			}
		}
		
		public function rollBack() {
			try {
				$this->EntityManager()->getConnection()->rollBack();
			} catch (ConnectionException $e) {
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			}
		}
		
		/**
		 * @param array $filter
		 * @param string|null $orderBy
		 * @param int|null $page
		 * @param int|null $limit
		 * @param string|null $typeReturn 'class | json'
		 * @param string|null $groupBy
		 * @return array
		 */
		public function search(array $filter, ?string $orderBy = null, ?int $page = 1, ?int $limit = 0, ?string $typeReturn = "class", ?string $groupBy = null): array {
			$qb = $this->qb->select($this->selectAlias);
			$qb->setFirstResult(((empty($page) ? 1 : $page) - 1) * $limit);
			if (!empty($groupBy)) {
				$qb->add('groupBy', [$groupBy]);
			}
			if (!empty($orderBy)) {
				$qb->add('orderBy', [$orderBy]);
			}
			if (!empty($limit)) {
				$qb->setMaxResults($limit);
			}
			DoctrineFilterBase::create(
				$qb,
				$filter,
				$this->getJoinsConfig()
			);
			$query = $qb->getQuery();
			if ($typeReturn == 'class'):
				return $query->getResult();
			else:
				return $query->getArrayResult();
			endif;
		}
		
		/**
		 * @return JoinConfig[]
		 */
		protected function getJoinsConfig(): array {
			return [];
		}
		
		public function count(array $filter): int {
			try {
				$qb = DoctrineFilterBase::create(
					$this->qb->select("count({$this->selectAlias})"),
					$filter,
					$this->getJoinsConfig()
				);
				$query = $qb->getQuery();
				return (int)$query->getSingleScalarResult();
			} catch (NoResultException $e) {
				throw new InvalidArgumentException($e->getMessage(), 404);
			} catch (NonUniqueResultException $e) {
				throw new InvalidArgumentException($e->getMessage(), 405);
			}
		}
		
		public function save($object) {
			$this->persist($object);
			$this->flush();
		}
		
		public function persist($object) {
			try {
				$this->EntityManager()->persist($object);
			} catch (ORMException $e) {
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			}
		}
		
		public function flush() {
			$tentativas = 0;
			$pararTentativas = false;
			do {
				$tentativas++;
				try {
					$this->EntityManager()->flush();
					$pararTentativas = true;
				} catch (OptimisticLockException $e) {
					if ($tentativas === 3) {
						throw new InvalidArgumentException($e->getMessage(), $e->getCode());
					} else {
						sleep(3);
					}
				} catch (ORMException $e) {
					throw new InvalidArgumentException($e->getMessage(), $e->getCode());
				}
			} while ($tentativas < 3 && !$pararTentativas);
		}
		
	}
