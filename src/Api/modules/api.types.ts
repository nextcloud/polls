/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Comment, Option, Poll, Share, Vote } from '../../Types'

export type FullPollResponse = {
	poll: Poll
	options: Option[]
	votes: Vote[]
	orphaned: number
	comments: Comment[]
	shares: Share[]
	subscribed: boolean
}

export type AddOptionResponse = {
	option: Option
	repetitions: Option[]
	options: Option[]
	votes: Vote[]
}

export type setVoteResponse = {
	vote: Vote
	poll: Poll
	options: Option[]
	votes: Vote[]
}

export type RemoveVotesResponse = {
	poll: Poll
	options: Option[]
	votes: Vote[]
}
