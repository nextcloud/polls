/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import polls from './modules/polls.js'
import settings from './modules/settings.js'

Vue.use(Vuex)

export default new Store({
	modules: { polls, settings },
	strict: process.env.NODE_ENV !== 'production',
})
