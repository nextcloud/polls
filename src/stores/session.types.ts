/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { RouteRecordNameGeneric } from 'vue-router'

import { AppSettingsStore } from './appSettings.types'
import { ViewMode } from './preferences.types'

import type { Share } from './shares.types'
import type { FilterType } from './polls.types'
import type { User } from '../Types'
import { WatcherMode, WatcherStatus } from '../composables/usePollWatcher.types'
import { TimeZoneTypes } from '@/Types/dateTime'

interface RouteParams {
	id: number
	token: string
	type: FilterType
	slug: string
}

export type Route = {
	currentRoute: string
	name: RouteRecordNameGeneric
	path: string
	params: RouteParams
}

export type UserStatus = {
	isLoggedin: boolean
	isAdmin: boolean
}

export type SessionSettings = {
	viewModeDatePoll: '' | ViewMode
	viewModeTextPoll: '' | ViewMode
	viewModeForced: null | ViewMode
	timezoneName: TimeZoneTypes | string
}

export type Watcher = {
	id: string
	mode: WatcherMode
	status: WatcherStatus
	interval?: number
	lastUpdate: number
	lastMessage?: string
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

export type Session = {
	appPermissions: AppPermissions
	appSettings: AppSettingsStore
	currentUser: User
	route: Route
	sessionSettings: SessionSettings
	share: Share
	token: string | null
	userStatus: UserStatus
	watcher: Watcher
}

export type SessionStore = Session
