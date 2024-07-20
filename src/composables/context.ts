/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { RouteLocationNormalized } from 'vue-router'
import { useSessionStore } from '../stores/session.ts'
import { usePreferencesStore } from '../stores/preferences.ts'
import { Logger } from '../helpers/index.ts'


const loadContext = (to: RouteLocationNormalized) => {
	const preferencesStore = usePreferencesStore()
	const sessionStore = useSessionStore()
	sessionStore.setRouter(to)
	sessionStore.load()
	console.log('loaded session')
	if (sessionStore.userStatus.isLoggedin) {
		preferencesStore.load()
		console.log('loaded preferences for logged in user')
	}
	Logger.debug('Context loaded', {
		'route': to,
		'session': sessionStore.$state,
		'preferences': preferencesStore.$state,
	})
}

export { loadContext }