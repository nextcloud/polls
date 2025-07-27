/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Chunking, User } from '../Types'

export type Answer = 'yes' | 'no' | 'maybe' | ''
export type AnswerSymbol = '✔' | '❌' | '❔' | ''

export type Vote = {
	id: number
	pollId: number
	optionText: string
	answer: Answer
	answerSymbol: AnswerSymbol
	deleted: number
	optionId: number
	user: User
}

export type VotesStore = {
	votes: Vote[]
	sortByOption: number
	meta: {
		chunks: Chunking
	}
}
