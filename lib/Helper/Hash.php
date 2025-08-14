<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Abstract class with functions to ensure for the different use cases
 * generating identical hashes
 */
namespace OCA\Polls\Helper;

/**
 * return a standard hash for votes and options
 * generated via pollId and optionText
 *
 * This is used to create a unique identifier for options and corresponding votes
 *
 * Maybe this may appear overengineered, but it is important to ensure that hashes
 * are generated in a consistent way across different parts of the application.
 *
 * @param int $pollId
 * @param string $optionText
 */
abstract class Hash {
	/**
	 * Generate a hash for an option based on the poll ID and option text.
	 * This is used to create a unique identifier for options.
	 *
	 * @param int $pollId
	 * @param string $optionText
	 * @return string
	 */
	public static function getOptionHash(int $pollId, string $optionText): string {
		return hash('md5', $pollId . $optionText);
	}

	/**
	 * Generate a binary hash for an option based on the poll ID and option text.
	 * This is used to create a unique identifier for options in binary format.
	 *
	 * @param int $pollId
	 * @param string $optionText
	 * @return string binary 16-byte MD5 hash
	 * @psalm-api
	 */
	public static function getOptionHashBin(int $pollId, string $optionText): string {
		return hash('md5', $pollId . $optionText, true);
	}

	/**
	 * Generate a hash for a client ID.
	 * This is used to create a unique identifier for clients.
	 *
	 * @param string $clientId
	 * @return string
	 */
	public static function getClientIdHash(string $clientId): string {
		return hash('md5', $clientId);
	}

	/**
	 * Generate a hash for a user ID.
	 * This is used to create a unique identifier for users.
	 *
	 * @param string $userId
	 * @return string
	 */
	public static function getUserIdHash(string $userId): string {
		// TODO: add a session salt
		return hash('md5', $userId);
	}
}
