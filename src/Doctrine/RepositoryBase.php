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
	use GambiEl\Doctrine\Filter\DoctrinePaginationOrderingSort;
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
			$dbConfig = $_ENV['GAMBIEL_DB_CONFIG'];
			if (is_array($dbConfig)) {
				$indexConfig = GambielArrayHelper::multidimensionalSearch($dbConfig, [
					"name" => $this->dbConfigName
				]);
				
				if ($indexConfig !== false) {
					return GambiElEntityManager::getEntityManager($dbConfig[$indexConfig]);
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
			$this->beginTransaction();
			try {
				$object = $this->getById($id);
				$this->EntityManager()->remove($object);
				$this->flush();
				$this->commit();
			} catch (ORMException $e) {
				$this->rollBack();
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			}
		}
		
		public function deleteAll(array $objects) {
			$this->beginTransaction();
			try {
				foreach ($objects as $object) {
					$this->EntityManager()->remove($object);
				}
				$this->flush();
				$this->commit();
			} catch (ORMException $e) {
				$this->rollBack();
				throw new InvalidArgumentException($e->getMessage(), $e->getCode());
			} catch (InvalidArgumentException $e) {
				$this->rollBack();
				throw $e;
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
		 * @param array $paginationOrderingSort Array with [page => 1, order => 'e.Name', sort => 'ASC', limit => 30]
		 * @param string|null $groupBy e.IdPerson
		 * @return array
		 */
		public function search(array $filter, array $paginationOrderingSort, ?string $groupBy = null): array {
			$PaginationOrderingSort = new DoctrinePaginationOrderingSort($paginationOrderingSort);
			$qb = $this->qb->select($this->selectAlias);
			$qb->setFirstResult(((empty($PaginationOrderingSort->page) ? 1 : $PaginationOrderingSort->page) - 1) * $PaginationOrderingSort->limit);
			if (!empty($groupBy)) {
				$qb->add('groupBy', [$PaginationOrderingSort->orderBy]);
			}
			if (!empty($PaginationOrderingSort->orderBy)) {
				$qb->add('orderBy', [$PaginationOrderingSort->orderBy]);
			}
			if (!empty($PaginationOrderingSort->limit)) {
				$qb->setMaxResults($PaginationOrderingSort->limit);
			}
			DoctrineFilterBase::create(
				$qb,
				$filter,
				$this->getJoinsConfig()
			);
			$query = $qb->getQuery();
			return $query->getResult();
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
			$this->beginTransaction();
			try {
				$this->persist($object);
				$this->flush();
				$this->commit();
			} catch (InvalidArgumentException $e) {
				$this->rollBack();
				throw $e;
			}
		}
		
		public function saveAll(array $objects) {
			$this->beginTransaction();
			try {
				foreach ($objects as $object) {
					$this->persist($object);
				}
				$this->flush();
				$this->commit();
			} catch (InvalidArgumentException $e) {
				$this->rollBack();
				throw $e;
			}
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
