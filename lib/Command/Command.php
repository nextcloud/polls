<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @psalm-api
 */
class Command extends \Symfony\Component\Console\Command\Command {
	protected const NAME_PREFIX = 'polls:';

	protected string $name = '';
	protected string $description = '';
	protected array $operationHints = [];
	protected bool $defaultContinueAnswer = false;
	protected mixed $helper;
	protected InputInterface $input;
	protected OutputInterface $output;
	protected ConfirmationQuestion $question;

	public function __construct(
	) {
		parent::__construct();
		$this->question = new ConfirmationQuestion('Continue (y/n)? [' . ($this->defaultContinueAnswer ? 'y' : 'n') . '] ', $this->defaultContinueAnswer);
	}

	protected function configure(): void {
		$this
			->setName($this->name)
			->setDescription($this->description);
	}

	/**
	 * @psalm-suppress PossiblyUnusedProperty
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->setOutput($output);
		$this->setInput($input);

		if ($this->requestConfirmation($input, $output)) {
			return 1;
		}

		return $this->runCommands();
	}

	protected function requestConfirmation(InputInterface $input, OutputInterface $output): int {
		if ($input->isInteractive()) {
			/** @var QuestionHelper */
			$this->helper = $this->getHelper('question');
			foreach ($this->operationHints as $hint) {
				$this->printComment($hint);
			}
			$this->printNewLine();

			if (!$this->helper->ask($input, $output, $this->question)) {
				return 1;
			}
		}
		return 0;
	}

	protected function runCommands(): int {
		throw new LogicException('You must override the runCommands() method in the concrete command class.');
	}

	protected function setOutput(OutputInterface $output): void {
		$this->output = $output;
	}

	protected function setInput(InputInterface $input): void {
		$this->input = $input;
	}

	protected function printNewLine(): void {
		$this->output->writeln('');
	}

	protected function printInfo(string|array $messages, string $prefix = ''): void {
		if (is_array($messages)) {
			foreach ($messages as $message) {
				$this->output->writeln('<info>' . $prefix . $message . '</info>');
			}
			return;
		}
		$this->output->writeln('<info>' . $prefix . $messages . '</info>');
	}

	protected function printComment(string|array $messages, string $prefix = ''): void {
		if (is_array($messages)) {
			foreach ($messages as $message) {
				$this->output->writeln('<comment>' . $prefix . $message . '</comment>');
			}
			return;
		}
		$this->output->writeln('<comment>' . $prefix . $messages . '</comment>');
	}
}
