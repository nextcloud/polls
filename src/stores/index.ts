/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { debouncePlugin } from '../plugins/piniaDebounce'

export const pinia = createPinia()
pinia.use(debouncePlugin)
