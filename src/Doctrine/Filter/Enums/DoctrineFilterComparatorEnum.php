<?php
	
	namespace GambiEl\Doctrine\Filter\Enums;
	
	class DoctrineFilterComparatorEnum {
		const EQUAL = "=";
		const DIFFERENT = "!=";
		const BIGGER = ">";
		const SMALLER = "<";
		const GREATER_EQUAL = ">=";
		const SMALLER_EQUAL = "<=";
		const LIKE = "like";
		const IN = "in";
		const NOT_IN = "not in";
		const IS_NULL = "is";
		const IS_NOT_NULL = "is not";
	}
