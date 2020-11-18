<?php
	declare(strict_types=1);
	
	namespace GambiEl\Doctrin2\Join;
	
	class JoinConfig {
		private string $join;
		private string $alias;
		private ?string $conditionType;
		private ?string $condition;
		/**
		 * @var JoinConfig[]
		 */
		private array $preJoins;
		
		/**
		 * JoinConfig constructor.
		 * @param string $join
		 * @param string $alias
		 * @param string|null $conditionType
		 * @param string|null $condition
		 * @param JoinConfig[]|array $preJoins
		 */
		public function __construct(
			string $join,
			string $alias,
			string $conditionType = null,
			string $condition = null,
			array $preJoins = []
		) {
			$this->join = $join;
			$this->alias = $alias;
			$this->conditionType = $conditionType;
			$this->condition = $condition;
			$this->preJoins = $preJoins;
		}
		
		public function getJoin(): string {
			return $this->join;
		}
		
		public function getAlias(): string {
			return $this->alias;
		}
		
		public function getConditionType(): ?string {
			return $this->conditionType;
		}
		
		public function getCondition(): ?string {
			return $this->condition;
		}
		
		/**
		 * @return JoinConfig[]|array
		 */
		public function getPreJoins(): array {
			return $this->preJoins;
		}
	}
