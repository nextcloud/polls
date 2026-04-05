/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ViewMode } from './preferences.types'

import type { Share } from './shares.types'
import type { User } from '../Types'
import { WatcherMode, WatcherStatus } from '../composables/usePollWatcher.types'
import { TimeZoneTypes } from '@/Types/dateTime'

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

type AppSettings = {
	finalImprintUrl: string
	finalPrivacyUrl: string
	navigationPollsInList: boolean
	useLogin: boolean
	useActivity: boolean
	updateType: string
	currentVersion: string
}

export type Session = {
	appPermissions: AppPermissions
	appSettings: AppSettings
	currentUser: User
	sessionSettings: SessionSettings
	share: Share
	token: string | null
	userStatus: UserStatus
	watcher: Watcher
}

export type SessionStore = Session & {
	navigationStatus: 'idle' | 'loading'
}
