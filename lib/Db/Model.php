<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @psalm-suppress UnusedClass
 */
abstract class Model extends Entity {
	/**
	 * FactoryMuffin checks for the existence of setters with method_exists($obj, $attr) but that returns false.
	 * By overwriting the __set() magic method we can trigger the changed flag on $obj->attr assignment.
	 */
	public function __set(string $name, mixed $value) {
		$this->setter($name, [$value]);
	}
}
