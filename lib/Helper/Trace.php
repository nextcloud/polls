<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Model;

class Trace implements \JsonSerializable {

	/** @var Array */
	protected $result = [];

	/** @var string */
	protected $method;

	public function __construct(
		string $method,
		string $payload = ''
	) {
		$this->method = $method;
		$this->log('Initialization', $payload);
	}

	public function log(string $operation, string $payload = ''): void {
		$this->result[] = [
			'method' => $this->method,
			'time' => time(),
			'operation' => $operation,
			'payload' => $payload
		];
	}

	public function jsonSerialize(): array {
		return $this->result;
	}
}
