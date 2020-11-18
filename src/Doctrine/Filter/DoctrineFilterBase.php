<?php
	
	namespace GambiEl\Doctrin2\Filter;
	
	use Doctrine\DBAL\Connection;
	use Doctrine\ORM\QueryBuilder;
	use GambiEl\Doctrin2\Join\JoinConfig;
	use GambiEl\Filter\Enums\DoctrineFilterComparatorEnum;
	use GambiEl\Helpers\GambielArrayHelper;
	
	abstract class DoctrineFilterBase {
		private static array $filter = [
			"or" => [],
			"orOnly" => [],
			"and" => []
		];
		
		public static function create(QueryBuilder $qb, array $filter, array $joins): QueryBuilder {
			self::clearFilters();
			$filter = GambielArrayHelper::clear($filter);
			
			if (!empty($filter)) {
				foreach ($filter as $colunaBd => $filter) {
					if (is_array(current($filter))) {
						foreach ($filter as $item) {
							if (is_array($item[1]) && $item[2] != DoctrineFilterComparatorEnum::IN && $item[2] != DoctrineFilterComparatorEnum::NOT_IN) {
								foreach ($item[1] as $value) {
									self::setFilter([
										$item[0],
										$value,
										$item[2],
										$item[3],
										!empty($item[4]) ? $item[4] : null
									], $colunaBd);
								}
							} else {
								self::setFilter($item, $colunaBd);
							}
						}
					} else {
						if (is_array($filter[1]) && $filter[2] != DoctrineFilterComparatorEnum::IN && $filter[2] != DoctrineFilterComparatorEnum::NOT_IN) {
							foreach ($filter[1] as $value) {
								self::setFilter([
									$filter[0],
									$value,
									$filter[2],
									$filter[3],
									!empty($filter[4]) ? $filter[4] : null
								], $colunaBd);
							}
						} else {
							self::setFilter($filter, $colunaBd);
						}
					}
				}
			}
			
			self::setQueryBuild($qb);
			self::addJoins($qb, $joins);
			
			return $qb;
		}
		
		private static function clearFilters() {
			self::$filter = [
				"or" => [],
				"orOnly" => [],
				"and" => []
			];
		}
		
		private static function setFilter(array $filter, string $collumName) {
			$condicaologica = strtolower($filter[3] ?? 'and');
			$filterConfig = new DoctrineFilterConfig();
			$filterConfig->setCollumName($collumName);
			$filterConfig->setComparator(strtolower($filter[2]));
			$filterConfig->setValue($filter[1]);
			$filterConfig->setAlias($filter[0]);
			
			if (!empty($filter[4])) {
				$filterConfig->setGroup($filter[4]);
			} else if (($condicaologica == 'or' || $condicaologica == 'or only') && !is_array($filterConfig->getValue())) {
				$filterConfig->setGroup('default');
			}
			
			if ($condicaologica == 'and') {
				self::and($filterConfig);
			} else if ($condicaologica == 'or only') {
				$numberAlias = 0;
				$newAlias = $filter[0] . "_" . $numberAlias;
				
				/**
				 * @var DoctrineFilterConfig $filterConfigCurrent
				 */
				foreach (self::$filter['orOnly'] as $filterConfigCurrent) {
					if ($filterConfigCurrent->getAlias() == $newAlias) {
						$numberAlias++;
						$newAlias = $filter[0] . "_" . $numberAlias;
					}
				}
				
				$filterConfig->setAlias($newAlias);
				
				self::or($filterConfig, true);
			} else {
				if (!empty(self::$filter['or'])) {
					if (key_exists($filterConfig->getGroup(), self::$filter['or'])) {
						$numberAlias = 0;
						/**
						 * @var $filterConfigCurrent $options
						 */
						foreach (self::$filter['or'][$filterConfig->getGroup()] as $options) {
							$newAlias = $filter[0] . "_" . $numberAlias;
							if ($options->getAlias() == $newAlias) {
								$numberAlias++;
								$newAlias = $filter[0] . "_" . $numberAlias;
							}
							
							$filterConfig->setAlias($newAlias);
						}
						
						self::or($filterConfig, false);
					} else {
						self::or($filterConfig, false);
					}
				} else {
					self::or($filterConfig, false);
				}
			}
		}
		
		private static function and(DoctrineFilterConfig $filterConfig) {
			if ($filterConfig->getValue() !== false &&
				(!empty($filterConfig->getValue()) || $filterConfig->getValue() === "0")
			) {
				array_push(self::$filter['and'], $filterConfig);
			}
		}
		
		private static function or(DoctrineFilterConfig $filterConfig, bool $only) {
			if ($filterConfig->getValue() !== false &&
				(!empty($filterConfig->getValue()) || $filterConfig->getValue() === "0")
			) {
				if ($only) {
					array_push(self::$filter['orOnly'], $filterConfig);
				} else {
					$indexGroup = empty($filterConfig->getGroup()) ? $filterConfig->getCollumName() : $filterConfig->getGroup();
					if (key_exists($indexGroup, self::$filter['or'])) {
						array_push(self::$filter['or'][$indexGroup], $filterConfig);
					} else {
						self::$filter['or'][$indexGroup] = [
							$filterConfig
						];
					}
				}
			}
		}
		
		private static function setQueryBuild(QueryBuilder $qb) {
			/**
			 * @var DoctrineFilterConfig $and
			 */
			foreach (self::$filter['and'] as $and) {
				if ($and->getComparator() == DoctrineFilterComparatorEnum::NOT_IN || $and->getComparator() == DoctrineFilterComparatorEnum::IN) {
					$qb->andWhere("{$and->getCollumName()} {$and->getComparator()} (:{$and->getAlias()})")
						->setParameter($and->getAlias(), is_array($and->getValue()) ? $and->getValue() : explode(",", $and->getValue()), Connection::PARAM_INT_ARRAY);
				} else if ($and->getComparator() == DoctrineFilterComparatorEnum::IS_NULL || $and->getComparator() == DoctrineFilterComparatorEnum::IS_NOT_NULL) {
					$qb->andWhere("{$and->getCollumName()} {$and->getComparator()} null");
				} else {
					$qb->andWhere("{$and->getCollumName()} {$and->getComparator()} :{$and->getAlias()}")
						->setParameter($and->getAlias(), $and->getValue());
				}
			}
			
			/**
			 * @var DoctrineFilterConfig $or
			 */
			foreach (self::$filter['or'] as $orConditions) {
				$contitionOr = '';
				foreach ($orConditions as $index => $or) {
					if (empty($contitionOr)) {
						if ($or->getComparator() == DoctrineFilterComparatorEnum::NOT_IN || $or->getComparator() == DoctrineFilterComparatorEnum::IN) {
							$contitionOr = "{$or->getCollumName()} {$or->getComparator()} (:{$or->getAlias()})";
						} else if ($or->getComparator() == DoctrineFilterComparatorEnum::IS_NULL || $or->getComparator() == DoctrineFilterComparatorEnum::IS_NOT_NULL) {
							$contitionOr = "{$or->getCollumName()} {$or->getComparator()} null";
						} else {
							$contitionOr = "{$or->getCollumName()} {$or->getComparator()} :{$or->getAlias()}";
						}
					} else {
						if ($or->getComparator() == DoctrineFilterComparatorEnum::NOT_IN || $or->getComparator() == DoctrineFilterComparatorEnum::IN) {
							$contitionOr .= " OR {$or->getCollumName()} {$or->getComparator()} (:{$or->getAlias()})";
						} else if ($or->getComparator() == DoctrineFilterComparatorEnum::IS_NULL || $or->getComparator() == DoctrineFilterComparatorEnum::IS_NOT_NULL) {
							$contitionOr .= " OR {$or->getCollumName()} {$or->getComparator()} null";
						} else {
							$contitionOr .= " OR {$or->getCollumName()} {$or->getComparator()} :{$or->getAlias()}";
						}
					}
					
					if ($index == count($orConditions) - 1) {
						$qb->andWhere($contitionOr);
					}
					
					if ($or->getComparator() == DoctrineFilterComparatorEnum::NOT_IN || $or->getComparator() == DoctrineFilterComparatorEnum::IN) {
						$qb->setParameter($or->getAlias(), is_array($or->getValue()) ? $or->getValue() : explode(",", $or->getValue()), Connection::PARAM_INT_ARRAY);
					} else if ($or->getComparator() != DoctrineFilterComparatorEnum::IS_NULL && $or->getComparator() != DoctrineFilterComparatorEnum::IS_NOT_NULL) {
						$qb->setParameter($or->getAlias(), $or->getValue());
					}
				}
			}
			
			$conditionOrOnly = '';
			/**
			 * @var DoctrineFilterConfig $orOnly
			 */
			foreach (self::$filter['orOnly'] as $index => $orOnly) {
				if (empty($conditionOrOnly)) {
					if ($orOnly->getComparator() == DoctrineFilterComparatorEnum::NOT_IN || $orOnly->getComparator() == DoctrineFilterComparatorEnum::IN) {
						$conditionOrOnly = "{$orOnly->getCollumName()} {$orOnly->getComparator()} (:{$orOnly->getAlias()})";
					} else if ($orOnly->getComparator() == DoctrineFilterComparatorEnum::IS_NULL || $orOnly->getComparator() == DoctrineFilterComparatorEnum::IS_NOT_NULL) {
						$conditionOrOnly = "{$orOnly->getCollumName()} {$orOnly->getComparator()} null";
					} else {
						$conditionOrOnly = "{$orOnly->getCollumName()} {$orOnly->getComparator()} :{$orOnly->getAlias()}";
					}
				} else {
					if ($orOnly->getComparator() == DoctrineFilterComparatorEnum::NOT_IN || $orOnly->getComparator() == DoctrineFilterComparatorEnum::IN) {
						$conditionOrOnly .= " OR {$orOnly->getCollumName()} {$orOnly->getComparator()} (:{$orOnly->getAlias()})";
					} else if ($and->getComparator() == DoctrineFilterComparatorEnum::IS_NULL || $and->getComparator() == DoctrineFilterComparatorEnum::IS_NOT_NULL) {
						$conditionOrOnly .= " OR {$orOnly->getCollumName()} {$orOnly->getComparator()} null";
					} else {
						$conditionOrOnly .= " OR {$orOnly->getCollumName()} {$orOnly->getComparator()} :{$orOnly->getAlias()}";
					}
				}
				
				if ($index == count(self::$filter['orOnly']) - 1) {
					$qb->andWhere($conditionOrOnly);
				}
				
				if ($orOnly->getComparator() == DoctrineFilterComparatorEnum::NOT_IN || $orOnly->getComparator() == DoctrineFilterComparatorEnum::IN) {
					$qb->setParameter($orOnly->getAlias(), is_array($orOnly->getValue()) ? $orOnly->getValue() : explode(",", $orOnly->getValue()), Connection::PARAM_INT_ARRAY);
				} else if ($or->getComparator() != DoctrineFilterComparatorEnum::IS_NULL && $or->getComparator() != DoctrineFilterComparatorEnum::IS_NOT_NULL) {
					$qb->setParameter($orOnly->getAlias(), $orOnly->getValue());
				}
			}
		}
		
		/**
		 * @param QueryBuilder $qb
		 * @param JoinConfig[] $joins
		 */
		private static function addJoins(QueryBuilder $qb, array $joins) {
			if (!empty($joins)) {
				foreach ($joins as $join) {
					if (
						str_contains($qb->getQuery()->getDQL(), $join->getAlias() . '.') &&
						array_search($join->getAlias(), $qb->getAllAliases()) === false
					) {
						self::setJoinConfigOnQueryBuilder($qb, $join);
					}
				}
			}
		}
		
		private static function setJoinConfigOnQueryBuilder(QueryBuilder $qb, JoinConfig $join) {
			if (!empty($join->getPreJoins())) {
				foreach ($join->getPreJoins() as $preJoin) {
					self::setJoinConfigOnQueryBuilder($qb, $preJoin);
				}
			}
			
			if (array_search($join->getAlias(), $qb->getAllAliases()) === false) {
				$qb->leftJoin(
					$join->getJoin(),
					$join->getAlias(),
					$join->getConditionType(),
					$join->getCondition()
				);
			}
		}
	}
