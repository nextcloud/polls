/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { RouteLocationNormalized } from 'vue-router'
import { useSessionStore } from '../stores/session.ts'
import { usePreferencesStore } from '../stores/preferences.ts'

const loadContext = (to: RouteLocationNormalized) => {
	const preferencesStore = usePreferencesStore()
	const sessionStore = useSessionStore()
	sessionStore.setRouter(to)
	sessionStore.load().then(() => {
		if (sessionStore.userStatus.isLoggedin) {
			preferencesStore.load()
		}
	})
}

export { loadContext }