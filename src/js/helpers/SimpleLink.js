/**
 * @copyright Copyright (c) 2022 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
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

import { h } from 'vue'

const SimpleLink = {
	name: 'SimpleLink',
	functional: true,

	props: {
		href: {
			type: String,
			default: '',
		},
		name: {
			type: String,
			default: '',
		},
		target: {
			type: String,
			default: null,
		},
	},

	render(context) {
		return h('a', {
			attrs: {
				href: context.props.href,
				target: context.props.target,
			},
		}, context.props.name)
	},
}

export default SimpleLink
