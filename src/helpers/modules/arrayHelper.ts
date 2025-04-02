/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Option, Vote, Participant } from '../../Types'

const uniqueArrayOfObjects = (array: unknown[]) =>
	[...new Set(array.map((obj) => JSON.stringify(obj)))].map((string) =>
		JSON.parse(string),
	)

const uniqueOptions = (options: Option[]) =>
	options.filter(
		(option, index, array) =>
			array.findIndex((compare) => compare.text === option.text) === index,
	)

const uniqueParticipants = (votes: Vote[]): Participant[] => {
	const participants: Participant[] = votes.map((vote) => ({
		user: vote.user,
		pollId: vote.pollId,
	}))

	return uniqueArrayOfObjects(participants)
}

/**
 * Creates a Record object from an array of objects
 *
 * @param arr - An array of objects that should contain the specified key property.
 * @param key - An optional string that specifies which property to use as the key (default is 'id').
 * @return A Record where the keys are the values of the specified property, and the values are the objects themselves.
 */
function createRecordFromArray<T extends object>(
	arr: T[],
	key: keyof T = 'id' as keyof T,
): Record<string | number, T> {
	// Use reduce to iterate over the array and build the Record object
	return arr.reduce(
		(acc, item) => {
			// Ensure the key exists in the current item (type safety)
			const keyValue = item[key]

			// Explicitly cast the key value to 'string | number'
			acc[keyValue as string | number] = item
			return acc
		},
		{} as Record<string | number, T>,
	)
}

// function createRecordFromArray<T extends { id: string | number }>(arr: T[]): Record<string | number, T> {
//   return arr.reduce((acc, item) => {
//     acc[item.id] = item;
//     return acc;
//   }, {} as Record<string | number, T>);
// }

export {
	uniqueArrayOfObjects,
	uniqueOptions,
	uniqueParticipants,
	createRecordFromArray,
}
