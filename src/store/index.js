/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import modules from './modules/index.js'

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

export default new Store({
	modules,
	strict: debug,
})
