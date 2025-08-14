<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Listener;

use OCA\Polls\Migration\TableSchema;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<AddMissingIndicesEvent>
 */
class AddMissingIndicesListener implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof AddMissingIndicesEvent)) {
			return;
		}

		foreach (TableSchema::OPTIONAL_INDICES as $table => $indices) {
			foreach ($indices as $name => $definition) {
				$event->addMissingIndex(
					$table,
					$name,
					$definition['columns'],
				);
			}
		}
	}
}
