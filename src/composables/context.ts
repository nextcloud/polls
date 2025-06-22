/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { RouteLocationNormalized } from 'vue-router'
import { useSessionStore } from '../stores/session.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Logger } from '../helpers/index.ts'
import { Settings } from 'luxon'

async function loadContext(to: RouteLocationNormalized) {
	const sessionStore = useSessionStore()
	const preferencesStore = usePreferencesStore()

	await sessionStore.load(to)

	Settings.defaultLocale = sessionStore.currentUser.localeCodeIntl || sessionStore.currentUser.languageCodeIntl

	if (sessionStore.userStatus.isLoggedin) {
		await preferencesStore.load()
	}
	Logger.debug('Context loaded')
}

export { loadContext }
