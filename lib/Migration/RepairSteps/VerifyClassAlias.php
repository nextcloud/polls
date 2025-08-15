<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration\RepairSteps;

use OCA\Polls\Bootstrap\AliasUtil;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

final class VerifyClassAlias implements IRepairStep {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	public function getName(): string {
		return 'Polls: verify class_alias mapping (self-test)';
	}

	public function run(IOutput $output): void {
		$results = AliasUtil::applyAliases($this->logger); // idempotent

		$allOk = true;
		foreach ($results as $r) {
			if (!$r['ok']) {
				$allOk = false;
				break;
			}
		}

		if ($allOk) {
			$output->info('OK');
			return;
		}

		// Nur Probleme ausgeben (kompakt)
		foreach ($results as $old => $r) {
			if ($r['ok']) {
				continue;
			}
			$note = $r['note'] ? ' | ' . $r['note'] : '';
			$output->info(sprintf('❌ %s | file:%s%s', $old, $r['file'] ?? 'n/a', $note));
		}
	}
}
