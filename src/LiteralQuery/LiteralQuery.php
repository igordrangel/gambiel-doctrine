<?php
	declare(strict_types=1);
	
	namespace GambiEl\LiteralQuery;
	
	use GambiEl\Helpers\GambielArrayHelper;
	use GambiEl\LiteralQuery\Filter\LiteralQueryFilter;
	use InvalidArgumentException;
	use PDO;
	
	class LiteralQuery {
		private PDO $connection;
		private string $host;
		private string $dbname;
		private string $user;
		private string $password;
		private string $drive;
		private string $port;
		
		public function __construct(string $dbConfigName) {
			$dbConfig = $_ENV['GAMBIEL_DB_CONFIG'];
			if (is_array($dbConfig)) {
				$indexConfig = GambielArrayHelper::multidimensionalSearch($dbConfig, [
					"name" => $dbConfigName
				]);
				
				if ($indexConfig !== false) {
					$config = $dbConfig[$indexConfig];
					$this->host = $config['host'];
					$this->dbname = $config['dbName'];
					$this->user = $config['user'];
					$this->password = $config['password'];
					$this->drive = $config['driver'];
					$this->port = $config['port'];
				} else {
					throw new InvalidArgumentException("Database config is not found!");
				}
			} else {
				throw new InvalidArgumentException("Database config is not found!");
			}
			
			try {
				$this->connection = new PDO("$this->drive:host=$this->host;dbname=$this->dbname;port=$this->port;charset=utf8", $this->user, $this->password);
			} catch (InvalidArgumentException $e) {
				throw $e;
			}
		}
		
		public function getLastInsertId(): string {
			return $this->getConnection()->lastInsertId();
		}
		
		private function getConnection(): PDO {
			return $this->connection;
		}
		
		public function beginTransaction() {
			$this->getConnection()->beginTransaction();
		}
		
		public function rollback() {
			$this->getConnection()->rollBack();
		}
		
		public function commit() {
			$this->getConnection()->commit();
		}
		
		public function callProcedure(string $procedureName, array $arrParams): array {
			$params = implode(",", $arrParams);
			
			return $this->select('CALL ' . $procedureName . ' (' . $params . ')');
		}
		
		public function select(string $query): array {
			try {
				$data = [];
				$select = $this->getConnection()->query($query, PDO::FETCH_ASSOC);
				
				if (!empty($select)):
					foreach ($select as $row):
						$data[] = $row;
					endforeach;
				endif;
				
				return $data;
			} catch (InvalidArgumentException $e) {
				throw $e;
			}
		}
		
		public function getView(string $viewName, array $filters, string $orderBy = null): array {
			$where = null;
			if (!empty($filters))
				$where = LiteralQueryFilter::CriarFiltro($filters);
			
			if (!empty($where))
				$where = "WHERE " . $where;
			
			return $this->select("SELECT * FROM $viewName $where $orderBy");
		}
		
		public function insert(string $tableName, string $names, string $values) {
			try {
				$this->getConnection()->query("INSERT INTO $tableName ($names) VALUES($values)", PDO::FETCH_ASSOC);
			} catch (InvalidArgumentException $e) {
				throw $e;
			}
		}
		
		public function update(string $tableName, string $values, string $condition) {
			try {
				if (!empty($condition))
					$condition = "WHERE " . $condition;
				
				$this->getConnection()->query("UPDATE $tableName SET $values $condition", PDO::FETCH_ASSOC);
			} catch (InvalidArgumentException $e) {
				throw $e;
			}
		}
		
		public function truncate(string $tableName) {
			try {
				$this->getConnection()->query("TRUNCATE $tableName", PDO::FETCH_ASSOC);
			} catch (InvalidArgumentException $e) {
				throw $e;
			}
		}
		
		public function delete(string $tableName, string $condition) {
			try {
				$this->getConnection()->query("DELETE FROM $tableName WHERE $condition", PDO::FETCH_ASSOC);
			} catch (InvalidArgumentException $e) {
				throw $e;
			}
		}
		
	}
