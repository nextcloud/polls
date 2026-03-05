/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OptionDto } from '../../stores/options.types'
import type { Vote } from '../../stores/votes.types'
import type { Poll } from '../../stores/poll.types'
import type { Comment } from '../../stores/comments.types'
import type { Share } from '../../stores/shares.types'

export type Job = {
	id: string
	className: string
	lastRun: number
	argument: string | null
	nameSpace: string
	name: string
	manuallyRunnable: boolean
}
export type JobsList = Record<string, Job>

export type ApiEmailAdressList = {
	displayName: string
	emailAddress: string
	combined: string
}

export type FullPollResponse = {
	poll: Poll
	options: OptionDto[]
	votes: Vote[]
	comments: Comment[]
	shares: Share[]
	subscribed: boolean
}

export type AddOptionResponse = {
	option: OptionDto
	repetitions: OptionDto[]
	options: OptionDto[]
	votes: Vote[]
}

export type setVoteResponse = {
	vote: Vote
	poll: Poll
	options: OptionDto[]
	votes: Vote[]
}

export type RemoveVotesResponse = {
	poll: Poll
	options: OptionDto[]
	votes: Vote[]
}
