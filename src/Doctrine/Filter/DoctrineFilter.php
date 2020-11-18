<?php
	
	namespace GambiEl\Doctrin2\Filter;
	
	class DoctrineFilter extends DoctrineFilterBase {
		
		/**
		 * @param string $attributeClassName
		 * @param string|array|int $filter
		 * @param string $comparator
		 * @param string|null $groupName
		 * @return array
		 * @uses SELECT e FROM Exemplo e WHERE e.User = :User OR e.Library = :Library
		 *
		 */
		public static function or(string $attributeClassName, $filter, string $comparator, string $groupName = null): array {
			$arrFiltro = [$attributeClassName => self::getParamFilter($filter)];
			return self::new($arrFiltro, $comparator, 'or', $groupName);
		}
		
		private static function getParamFilter($filter) {
			if (gettype($filter) === 'array') {
				$param = $filter;
			} else {
				$param = $filter;
			}
			
			return $param;
		}
		
		private static function new(array $arrFilter, string $comparator, string $condition, string $groupName = null): array {
			$arrResult = [];
			foreach ($arrFilter as $atributo => $filtro) {
				$name = str_replace(".", "_", $atributo);
				$arrResult[$atributo] = [$name, $filtro, $comparator, $condition, $groupName];
			}
			
			return $arrResult;
		}
		
		/**
		 * @param string $atributeClassName
		 * @param string|array|int $filter
		 * @param string $comparator
		 * @return array
		 * @uses SELECT e FROM Exemplo e WHERE e.Filial = :Filial AND e.Banco = :Banco
		 *
		 */
		public static function and(string $atributeClassName, $filter, string $comparator): array {
			$arrFilter = [$atributeClassName => self::getParamFilter($filter)];
			return self::new($arrFilter, $comparator, 'and');
		}
		
		public static function add(array $arrFilter): array {
			$arr = [];
			foreach ($arrFilter as $keyIndex => $arrValue) {
				foreach ($arrValue as $keyValue => $value) {
					if (array_key_exists($keyValue, $arr)) {
						$dadosIndex = $arr[$keyValue];
						if (!is_array($arr[$keyValue][0])) {
							$arr[$keyValue] = [];
							array_push($arr[$keyValue], $dadosIndex, $value);
						} else {
							array_push($arr[$keyValue], $value);
						}
					} else {
						$arr = array_merge($arr, $arrValue);
					}
				}
			}
			foreach ($arr as $keyIndex => $arrValue) {
				if (is_array($arrValue[0])) {
					foreach ($arrValue as $keyValue => $value) {
						$arr[$keyIndex][$keyValue][0] = $arr[$keyIndex][$keyValue][0] . "_" . $keyValue;
					}
				}
			}
			
			return $arr;
		}
		
	}
