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

import axios from '@nextcloud/axios'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'

const clientSessionId = Math.random().toString(36).substring(2)

const axiosConfig = {
	baseURL: generateUrl('apps/polls/'),
	headers: {
		Accept: 'application/json',
		'Nc-Polls-Client-Id': clientSessionId,
	},
}

const axiosOcsConfig = {
	baseURL: generateOcsUrl('apps/'),
	headers: {
		Accept: 'application/json',
	},
}

const CancelToken = axios.CancelToken
const httpInstance = axios.create(axiosConfig)
const ocsInstance = axios.create(axiosOcsConfig)

/**
 * Description
 *
 * @param {any} apiObject apiObject
 * @return {any}
 */
const createCancelTokenHandler = (apiObject) => {
	const cancelTokenHandler = {}

	Object.getOwnPropertyNames(apiObject)
		.forEach((propertyName) => {
			const cancelTokenRequestHandler = {
				cancelToken: undefined,
			}

			cancelTokenHandler[propertyName] = {
				handleRequestCancellation: () => {
					cancelTokenRequestHandler.cancelToken && cancelTokenRequestHandler.cancelToken.cancel(`${propertyName} canceled`)
					cancelTokenRequestHandler.cancelToken = CancelToken.source()
					return cancelTokenRequestHandler.cancelToken
				},
			}
		})

	return cancelTokenHandler
}

export { ocsInstance, httpInstance, createCancelTokenHandler }
