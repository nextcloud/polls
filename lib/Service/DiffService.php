<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCP\AppFramework\Db\Entity;

/**
 * DiffService is responsible for calculating the differences between two objects.
 * It can be used to compare a base object with a comparison object and retrieve
 * the differences in various formats.
 *
 */
class DiffService {
	private Entity $compareObject;
	private array $diff = [];
	private string $baseJson = '';

	/**
	 * Constructor for DiffService
	 *
	 * @param Entity $baseObject The base object to compare against
	 * @throws \JsonException If the base object cannot be serialized to JSON
	 *
	 * @psalm-param Entity $baseObject
	 */
	public function __construct(
		private Entity $baseObject,
	) {
		$this->init();
	}

	private function init(): void {
		$this->baseJson = json_encode($this->baseObject);
	}
	public function setComparisonObject(\OCA\Polls\Db\Poll $compareObject): void {
		$this->compareObject = $compareObject;
		$this->calculateDiff();
	}

	/**
	 * Get the full diff between the base object and the comparison object
	 *
	 * This method returns an associative array where keys are the paths to the changed values
	 * and values are arrays containing 'old' and 'new' values.
	 *
	 * @return array An associative array containing the differences
	 *
	 * @psalm-return array<string, array{old: mixed, new: mixed}>
	 */
	public function getFullDiff(): array {
		return $this->diff;
	}

	/**
	 * Get the new values from the diff, preserving the structure of the original object
	 * This method will return an associative array where keys are the paths to the new values
	 * and values are the new values themselves.
	 *
	 * @return array An associative array containing the new values
	 *
	 * @psalm-return array<string, mixed>
	 */
	public function getNewValuesDiff(): array {
		$newValues = [];

		// Recursively search for "new" values and preserve the structure
		$this->extractNewValues($this->diff, $newValues);

		return $newValues;
	}

	/**
	 * Calculate the difference between the base object and the comparison object.
	 *
	 * This method serializes the objects to JSON, decodes them into associative arrays,
	 * and then compares the arrays recursively to find differences.
	 */
	private function calculateDiff(): void {
		// Serialize the objects to JSON format
		$compareJson = json_encode($this->compareObject);

		// Decode the JSON strings back to associative arrays
		$baseArray = json_decode($this->baseJson, true);
		$compareArray = json_decode($compareJson, true);

		// Compare the arrays recursively
		$this->diff = $this->array_diff_recursive($baseArray, $compareArray);
	}

	/**
	 * Recursively extract new values from the diff array.
	 *
	 * @param array $diff The diff array containing changes.
	 * @param array $newValues The array to populate with new values, preserving the structure.
	 */
	private function extractNewValues($diff, &$newValues): void {
		foreach ($diff as $key => $value) {
			if (is_array($value)) {
				// If 'new' exists, add it to the correct position in the structure
				if (isset($value['new'])) {
					$newValues[$key] = $value['new'];
				}

				// If the element is an array and the key isn't set yet, initialize it
				if (!isset($newValues[$key])) {
					$newValues[$key] = [];
				}

				// Recursively process nested arrays
				$this->extractNewValues($value, $newValues[$key]);
			}
		}
	}

	/**
	 * Recursively compare two arrays and return the differences
	 *
	 * This method compares two arrays recursively and returns an associative array
	 *
	 * @param array $baseArray The base array to compare against
	 * @param array $compareArray The array to compare with the base array
	 * @return array An associative array containing the differences
	 *
	 * @psalm-return array<array-key, non-empty-array<string, array{new: mixed, old: mixed}|mixed|null>>
	 */
	private function array_diff_recursive($baseArray, $compareArray): array {
		$diff = [];

		foreach ($baseArray as $key => $value) {
			if (is_array($value) && isset($compareArray[$key]) && is_array($compareArray[$key])) {
				$recursiveDiff = $this->array_diff_recursive($value, $compareArray[$key]);
				if (!empty($recursiveDiff)) {
					$diff[$key] = $recursiveDiff;
				}
			} elseif (!isset($compareArray[$key]) || $value !== $compareArray[$key]) {
				$diff[$key] = ['old' => $value, 'new' => $compareArray[$key] ?? null];
			}
		}

		// Loop through the second array to find keys that are not in the first array
		foreach ($compareArray as $key => $value) {
			if (!isset($baseArray[$key])) {
				$diff[$key] = ['old' => null, 'new' => $value];
			}
		}

		return $diff;
	}
}
