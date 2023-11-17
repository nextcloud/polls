<?php

declare(strict_types=1);
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

namespace OCA\Polls\Command;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
		$this->question = new ConfirmationQuestion('Continue (y/n)? ['. ($this->defaultContinueAnswer ? 'y' : 'n') . '] ', $this->defaultContinueAnswer);
	}

	protected function configure(): void {
		$this
			->setName($this->name)
			->setDescription($this->description);
	}

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
