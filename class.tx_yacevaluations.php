<?php

class tx_yacevaluations {

	function returnFieldJS() {
		return 'return value;';
	}

	function evaluateFieldValue($value, $is_in, &$set) {
		if ($value % 2 != 0 && $value != 0) {
			$value += 1;
		}
		return $value;
	}

}

?>