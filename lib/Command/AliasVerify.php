<?php

declare(strict_types=1);

namespace OCA\Polls\Command;

use OCA\Polls\AppConstants;
use OCA\Polls\Bootstrap\AliasUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputOption;

/** @psalm-suppress UnusedClass */
class AliasVerify extends Command {
	public function __construct(
		private ?LoggerInterface $logger = null,
	) {
		parent::__construct();
		$this->skipQuestion = true; // No confirmation needed
	}

	protected function configure(): void {
		$this
			->setName(AppConstants::APP_ID . ':alias:verify')
			->setDescription('Verify class_alias mapping for Polls DB/Schema classes')
			->addOption('json', null, InputOption::VALUE_NONE, 'Output result as JSON');
	}

	protected function runCommands(): int {
		$results = AliasUtil::applyAliases($this->logger);

		foreach ($results as $old => $r) {
			$symbol = $r['ok'] ? '✅' : '❌';
			$note = $r['note'] ? ' | ' . $r['note'] : '';
			$this->printInfo(sprintf(
				'%s | loaded: %s | file: %s%s| %s',
				$old,
				$r['loaded'] ? 'yes' : 'no',
				$r['file'] ?? 'n/a',
				$note,
				$symbol,
			), ' - ');
		}
		return 0;
	}
}
