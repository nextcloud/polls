/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { RouteLocationNormalized } from 'vue-router'
import { useSessionStore } from '../stores/session.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Logger } from '../helpers/index.ts'

async function loadContext(to: RouteLocationNormalized) {
	const sessionStore = useSessionStore()
	const preferencesStore = usePreferencesStore()

	await sessionStore.load(to)

	if (sessionStore.userStatus.isLoggedin) {
		await preferencesStore.load()
	}
	sessionStore.generateWatcherId()
	Logger.debug('Context loaded')
}

export { loadContext }
