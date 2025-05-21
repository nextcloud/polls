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

export enum ButtonMode {
	Navigation = 'navigation',
	ActionMenu = 'actionMenu',
	Native = 'native',
}

export enum StatusResults {
	Error = 'error',
	Warning = 'warning',
	Success = 'success',
	Loading = 'loading',
	Loaded = 'loaded',
	Unchanged = 'unchanged',
	None = '',
}

export enum SignalingType {
	None = '',
	Empty = 'empty',
	Error = 'error',
	Valid = 'valid',
	InValid = 'invalid',
	Success = 'success',
	Checking = 'checking',
}

export enum UserType {
	Email = 'email',
	External = 'external',
	Contact = 'contact',
	User = 'user',
	Group = 'group',
	Admin = 'admin',
	Public = 'public',
	Circle = 'circle',
	ContactGroup = 'contactGroup',
	None = '',
}

export enum VirtualUserItemType {
	AddPublicLink = 'addPublicLink',
	InternalAccess = 'internalAccess',
	Deleted = 'deleted',
	Anonymous = 'anonymous',
}

export enum BoxType {
	Text = 'textBox',
	Date = 'dateBox',
	AlignedText = 'alignedTextBox',
}

export enum ISearchType {
	User = 0,
	Group = 1,
	UserGroup = 2,
	Email = 4,
	Circle = 7,
	Contact = 51,
	All = 99,
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
	languageCodeFixed: string
	localeCode: string | null
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
