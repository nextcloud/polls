/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { WatcherMode } from '../composables/usePollWatcher.types'

export type Group = {
	id: string
	userId: string
	displayName: string
	emailAddress: string
	isNoUser: boolean
	type: string
}

export type AppSettingsStore = {
	allAccessGroups: string[]
	allowCombo: boolean
	allowPublicShares: boolean
	allowAllAccess: boolean
	allowPollCreation: boolean
	allowPollDownload: boolean
	autoArchive: boolean
	autoArchiveOffset: number
	autoDelete: boolean
	autoDeleteOffset: number
	defaultPrivacyUrl: string
	defaultImprintUrl: string
	disclaimer: string
	imprintUrl: string
	legalTermsInEmail: boolean
	privacyUrl: string
	showMailAddresses: boolean
	showLogin: boolean
	unrestrictedOwner: boolean
	updateType: WatcherMode
	useActivity: boolean
	useCollaboration: boolean
	useSiteLegalTerms: boolean
	navigationPollsInList: boolean
	finalPrivacyUrl: string
	finalImprintUrl: string
	comboGroups: string[]
	publicSharesGroups: string[]
	pollCreationGroups: string[]
	pollDownloadGroups: string[]
	showMailAddressesGroups: string[]
	unrestrictedOwnerGroups: string[]
	groups: Group[]
	status: {
		loadingGroups: boolean
	}
}
