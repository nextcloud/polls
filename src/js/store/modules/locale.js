/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
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

import moment from 'moment'



const getters = {
	longDateFormat() {
		return moment.localeData().longDateFormat('L')
	},

	dateTimeFormat() {
		return moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT')
	},

	languageCode() {
		return OC.getLanguage()
	},

	languageCodeShort() {
		return OC.getLanguage().split('-')[0]
	},

	localeCode() {
		try {
			return OC.getLocale()
		} catch (e) {
			if (e instanceof TypeError) {
				return OC.getLanguage()
			} else {
				console.error(e)
			}
		}
	},

	localeData(getters) {
		return moment.localeData(moment.locale(getters.localeCode))
	}
}

export default { getters }
