/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export { Activity, Activities } from '../stores/activity.ts'
export { UpdateType, Group, AppSettings } from '../stores/appSettings.ts'
export { Combo } from '../stores/combo.ts'
export { Comment, Comments, CommentsGrouped } from '../stores/comments.ts'
export {
	Poll,
	PollType,
	AccessType,
	ShowResults,
	AllowProposals,
	PollConfiguration,
	PollStatus,
	PollPermissions,
	CurrentUserStatus,
} from '../stores/poll.ts'

export {
	SortType,
	FilterType,
	PollCategory,
	Meta,
	PollList,
} from '../stores/polls.ts'

export {
	Option,
	OptionVotes,
	Sequence,
	SimpleOption,
	Options,
} from '../stores/options.ts'

export { Share, Shares, ShareType } from '../stores/shares.ts'
export { Route, UserStatus, Session } from '../stores/session.ts'

export {
	UserPreferences,
	SessionSettings,
	Calendar,
	Preferences,
	ViewMode,
} from '../stores/preferences.ts'

export { Answer, AnswerSymbol, Vote, Votes } from '../stores/votes.ts'

export {
	DateTimeUnit,
	DateTimeUnitType,
	TimeUnitsType,
	DurationType,
} from '../constants/dateUnits.ts'

export enum Event {
	TransitionsOff = 'polls:transitions:off',
	TransitionsOn = 'polls:transitions:on',
	UpdatePoll = 'polls:poll:update',
	LoadPoll = 'polls:poll:load',
	SidebarChangeTab = 'polls:sidebar:changeTab',
	SidebarToggle = 'polls:sidebar:toggle',
	ChangeShares = 'polls:change:shares',
	UpdateOptions = 'polls:options:update',
	AddDate = 'polls:options:add-date',
	UpdateComments = 'polls:comments:update',
	UpdateActivity = 'polls:activity:update',
	ShowSettings = 'polls:settings:show',
}

export type ButtonMode = 'navigation' | 'actionMenu' | 'native'
export type StatusResults =
	| 'error'
	| 'warning'
	| 'success'
	| 'loading'
	| 'loaded'
	| 'unchanged'
	| ''

export type SignalingType =
	| ''
	| 'empty'
	| 'error'
	| 'valid'
	| 'invalid'
	| 'success'
	| 'checking'

export type UserType =
	| 'email'
	| 'external'
	| 'contact'
	| 'user'
	| 'group'
	| 'admin'
	| 'public'
	| 'circle'
	| 'contactGroup'
	| ''

export type VirtualUserItemType =
	| 'addPublicLink'
	| 'internalAccess'
	| 'deleted'
	| 'anonymous'

/**
 * Type of search that can be used in the search bar.
 * 0 = User, 1 = Group, 2 = UserGroup, 4 = Email, 7 = Circle, 51 = Contact, 99 = All
 */
export type ISearchType = 0 | 1 | 2 | 4 | 7 | 51 | 99

export type Chunking = {
	size: number
	loaded: number
}

export type ApiEmailAdressList = {
	displayName: string
	emailAddress: string
	combined: string
}

export type AppPermissions = {
	addShares: boolean
	addSharesExternal: boolean
	allAccess: boolean
	changeForeignVotes: boolean
	comboView: boolean
	deanonymizePoll: boolean
	pollCreation: boolean
	pollDownload: boolean
	publicShares: boolean
	seeMailAddresses: boolean
	unrestrictedOwner: boolean
}

export type User = {
	id: string
	displayName: string
	emailAddress: string
	isAdmin: boolean
	isNoUser: boolean
	type: UserType
	subName: string | null
	subtitle: string | null
	desc: string | null
	organisation: string | null
	languageCode: string
	languageCodeIntl: string
	localeCode: string | null
	localeCodeIntl: string | null
	timeZone: string | null
	categories: string[] | null
}

export type Participant = {
	pollId: number
	user: User
}

/**
 *
 */
export function createDefault<T>(): T {
	return {} as T
}
