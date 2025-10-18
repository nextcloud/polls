/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { pinia } from './stores'
import { Settings } from 'luxon'
import { getLanguage } from '@nextcloud/l10n'

import AdminSettingsPage from './views/AdminSettingsPage.vue'

Settings.defaultLocale = getLanguage()

const Polls = createApp(AdminSettingsPage).use(pinia)
Polls.mount('#content_polls')
