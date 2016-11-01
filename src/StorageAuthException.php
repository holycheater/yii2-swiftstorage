<?php
// vim: sw=4:ts=4:noet:sta:

namespace alexsalt\swiftstorage;

class StorageAuthException extends StorageException {
	public function getName() {
		return 'SwiftStorageAuthException';
	}
}
