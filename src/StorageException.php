<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\swiftstorage;

use yii\base\Exception;

class StorageException extends Exception {
	public function getName() {
		return 'SwiftStorageException';
	}
}
