/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Option } from '../../stores/options.types'
import type { Vote } from '../../stores/votes.types'
import type { Participant } from '../../Types'

function uniqueArrayOfObjects (array: unknown[]) {
  return [...new Set(array.map((obj) => JSON.stringify(obj)))].map((string) =>
		JSON.parse(string),)
}

function uniqueOptions (options: Option[]) {
  return options.filter(
		(option, index, array) =>
			array.findIndex((compare) => compare.text === option.text) === index,
	)
}

function uniqueParticipants (votes: Vote[]): Participant[] {
	const participants: Participant[] = votes.map((vote) => ({
		user: vote.user,
		pollId: vote.pollId,
	}))

	return uniqueArrayOfObjects(participants)
}

export { uniqueOptions, uniqueParticipants }
