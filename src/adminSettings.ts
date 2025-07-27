/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { pinia } from './stores'

import AdminSettingsPage from './views/AdminSettingsPage.vue'

// Vue.config.devtools = import.meta.env.MODE !== 'production'

const Polls = createApp(AdminSettingsPage).use(pinia)
Polls.mount('#content_polls')
