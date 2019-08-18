/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import Vuex from 'vuex'
import polls from './polls'
import poll from './currentPoll'
import comments from './modules/comments'
import event from './modules/event'
import notification from './modules/notification'
import votes from './modules/votes'
import options from './modules/options'
import locale from './locale'

Vue.use(Vuex)

/* eslint-disable-next-line no-unused-vars */
const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store({

	modules: {
		polls,
		poll,
		comments,
		event,
		notification,
		locale,
		votes,
		options
	},
	state: {
		currentUser: ''
	},
	strict: process.env.NODE_ENV !== 'production'
})
