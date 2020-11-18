<?php
	
	namespace GambiEl\Doctrin2\Filter;
	
	use GambiEl\Filter\Enums\DoctrineFilterComparatorEnum;
	
	class DoctrineFilterConfig {
		private ?string $group;
		private string $collumName;
		private string $comparator;
		/**
		 * @var string|array
		 */
		private $value;
		private string $alias;
		
		/**
		 * @return string Retorna o nome do grupo de valores a ser comparada pela busca do tipo OR.
		 */
		public function getGroup(): string {
			return $this->group;
		}
		
		public function setGroup(?string $group) {
			$this->group = $group;
		}
		
		/**
		 * @return string Retorna o nome da coluna a ser comparada pela busca.
		 */
		public function getCollumName(): string {
			return $this->collumName;
		}
		
		/**
		 * @param string $collumName
		 */
		public function setCollumName(string $collumName) {
			$this->collumName = $collumName;
		}
		
		/**
		 * @return string Retorna um comparador de busca: = | != | like | in | not in
		 */
		public function getComparator(): string {
			return $this->comparator;
		}
		
		/**
		 * @param string $comparator
		 */
		public function setComparator(string $comparator) {
			$this->comparator = $comparator;
		}
		
		/**
		 * @return string Retorna o valor a ser filtrado.
		 */
		public function getValue() {
			return $this->value;
		}
		
		/**
		 * @param string|array $value
		 */
		public function setValue($value) {
			if ($value !== false &&
				(!empty($value) || $value === "0") &&
				$this->comparator == DoctrineFilterComparatorEnum::LIKE
			) {
				$value = '%' . $value . '%';
			}
			
			$this->value = $value;
		}
		
		/**
		 * @return string Retorna o alias do valor a ser comparada pela busca.
		 */
		public function getAlias(): string {
			return $this->alias;
		}
		
		/**
		 * @param string $alias
		 */
		public function setAlias(string $alias) {
			$this->alias = $alias;
		}
	}
