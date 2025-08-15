<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;
use OCA\Polls\Bootstrap\AliasUtil;
use Psr\Log\LoggerInterface;

final class VerifyClassAlias implements IRepairStep {
	public function __construct(private LoggerInterface $logger) {}

	public function getName(): string {
		return 'Polls: verify class_alias mapping (self-test)';
	}

	public function run(IOutput $output): void {
		$results = AliasUtil::applyAliases($this->logger); // idempotent
		foreach ($results as $old => $r) {
			$symbol = $r['ok'] ? '✅' : '❌';
			$output->info(sprintf(
				'%s %s | loaded:%s | file:%s%s',
				$symbol, $old, $r['loaded'] ? 'yes' : 'no', $r['file'] ?? 'n/a',
				$r['note'] ? ' | '.$r['note'] : ''
			));
		}
	}
}
