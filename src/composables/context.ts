/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { RouteLocationNormalized } from 'vue-router'
import { useSessionStore } from '../stores/session'
import { usePreferencesStore } from '../stores/preferences'
import { Logger } from '../helpers/modules/logger'
import { Settings } from 'luxon'

/**
 * Load the application context based on the current route.
 *
 * This function initializes the session and user preferences,
 * setting the default locale for date and time formatting.
 *
 * @param to - The current route being navigated to.
 * @param cheapLoading - A boolean indicating whether to load context lightweightly
 */
async function loadContext(
	to: RouteLocationNormalized,
	cheapLoading: boolean = false,
) {
	const sessionStore = useSessionStore()
	const preferencesStore = usePreferencesStore()

	await sessionStore.load({ to, cheapLoading })

	if (!cheapLoading) {
		Settings.defaultLocale =
			sessionStore.currentUser.localeCodeIntl
			|| sessionStore.currentUser.languageCodeIntl

		if (sessionStore.userStatus.isLoggedin) {
			await preferencesStore.load()
		}
	}
	Logger.info('Context loaded')
}

export { loadContext }
