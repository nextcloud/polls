/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Chunking, StatusResults, User, UserType } from '../Types'
import { ViewMode } from './preferences.types'

export type PollType = 'textPoll' | 'datePoll'

export type PollTypesType = {
	name: string
}

export type VoteVariant = 'simple'
export type AccessType = 'private' | 'open'
export type ShowResults = 'always' | 'closed' | 'never'
export type AllowProposals = 'allow' | 'disallow' | 'review'
export type SortParticipants = 'alphabetical' | 'voteCount' | 'unordered'

type Meta = {
	chunking: Chunking
	status: StatusResults
}

export type PollConfiguration = {
	access: AccessType
	allowComment: boolean
	allowMaybe: boolean
	allowProposals: AllowProposals
	anonymous: boolean
	autoReminder: boolean
	collapseDescription: boolean
	description: string
	expire: number
	forceConfidentialComments: boolean
	forcedDisplayMode: ViewMode | 'user-pref'
	hideBookedUp: boolean
	maxVotesPerOption: number
	maxVotesPerUser: number
	proposalsExpire: number
	showResults: ShowResults
	title: string
	useNo: boolean
}

export type PollStatus = {
	anonymizeLevel: string
	lastInteraction: number
	created: number
	isAnonymous: boolean
	isArchived: boolean
	isExpired: boolean
	isRealAnonymous: boolean
	relevantThreshold: number
	deletionDate: number
	archivedDate: number
	countParticipants: number
	maxVotes: number
	maxOptionVotes: number
	forcedViewMode: ViewMode | null
}

export type PollPermissions = {
	addOptions: boolean
	addShares: boolean
	addSharesExternal: boolean
	archive: boolean
	changeForeignVotes: boolean
	changeOwner: boolean
	clone: boolean
	comment: boolean
	confirmOptions: boolean
	deanonymize: boolean
	delete: boolean
	edit: boolean
	reorderOptions: boolean
	seeResults: boolean
	seeUsernames: boolean
	subscribe: boolean
	takeOver: boolean
	view: boolean
	vote: boolean
}

export type CurrentUserStatus = {
	groupInvitations: string[]
	isInvolved: boolean
	isLocked: boolean
	isLoggedIn: boolean
	isNoUser: boolean
	isOwner: boolean
	orphanedVotes: number
	shareToken: string
	userId: string
	userRole: UserType
	countVotes: number
	yesVotes: number
	noVotes: number
	maybeVotes: number
}

export type Poll = {
	id: number
	type: PollType
	votingVariant: VoteVariant
	configuration: PollConfiguration
	descriptionSafe: string
	owner: User
	status: PollStatus
	currentUserStatus: CurrentUserStatus
	permissions: PollPermissions
	pollGroups: number[]
	revealParticipants: boolean
	sortParticipants: SortParticipants
	meta: Meta
}

export type PollStore = Poll
