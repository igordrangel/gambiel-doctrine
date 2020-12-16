<?php
	
	namespace GambiEl\Doctrine\Filter;
	
	class DoctrinePaginationOrderingSort {
		public int $page;
		public int $limit;
		public string $orderBy;
		public int $trash;
		
		public function __construct(array $params, string $sort = null, string $order = 'ASC', int $limit = 30) {
			$columnOrder = $params['sort'] ?? '';
			$orderType = $params['order'] ?? '';
			
			$this->page = (int)$params['page'] ?? 1;
			$this->limit = (int)$params['limit'] ?? $limit;
			
			if ($sort) $this->orderBy = "{$sort} {$order}";
			if (!empty($columnOrder)) {
				$this->orderBy = "$columnOrder $orderType";
			}
			
			$this->trash = (int)$params['trash'] ?? 0;
		}
	}
