/**
 * @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import debounce from 'lodash/debounce'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { mapState } from 'vuex'

export const writePoll = {
	computed: {
		...mapState({
			pollTitle: (state) => state.poll.title,
		}),

	},

	methods: {
		successDebounced: debounce(function() {
			showSuccess(t('polls', '"{pollTitle}" successfully saved', { pollTitle: this.pollTitle }), { timeout: 1000 })
		}, 1500),

		async writePoll() {
			if (this.pollTitle === '') {
				showError(t('polls', 'Title must not be empty!'))
			} else {
				try {
					await this.$store.dispatch('poll/update')
					this.successDebounced()
				} catch {
					showError(t('polls', 'Error writing poll'))
				}
			}
		},

	},
}
