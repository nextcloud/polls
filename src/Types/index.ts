/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export {
	DateTimeUnits as DateTimeUnit,
	DateTimeUnitType,
	TimeUnitsType,
	DurationType,
} from './dateTime'

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
	| 'missing'

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
	| 'deleted'
	| 'anonymous'
	| ''

export type VirtualUserItemType = 'addPublicLink' | 'internalAccess'

/**
 * Type of search that can be used in the search bar.
 * 0 = User, 1 = Group, 2 = UserGroup, 4 = Email, 7 = Circle, 51 = Contact, 99 = All
 */
export type ISearchType = 0 | 1 | 2 | 4 | 7 | 51 | 99

export type Chunking = {
	size: number
	loaded: number
}

export type User = {
	id: string
	displayName: string
	emailAddress: string
	isAdmin: boolean
	isNoUser: boolean
	isGuest: boolean
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
