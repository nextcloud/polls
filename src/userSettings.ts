/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLanguage } from '@nextcloud/l10n'
import { Settings } from 'luxon'
import { createApp } from 'vue'
import UserSettingsView from './views/UserSettingsView.vue'
import { pinia } from './stores/index.ts'

Settings.defaultLocale = getLanguage()

const Polls = createApp(UserSettingsView).use(pinia)
Polls.mount('#content_polls')
