/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLanguage } from '@nextcloud/l10n'
import { Settings } from 'luxon'
import { createApp } from 'vue'
import UserSettingsPage from './views/UserSettingsPage.vue'
import { pinia } from './stores'

Settings.defaultLocale = getLanguage()

const Polls = createApp(UserSettingsPage).use(pinia)
Polls.mount('#content_polls')
