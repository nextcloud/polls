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
import { CancelledRequest } from '../Exceptions/Exceptions.js'

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
const axiosInstance = axios.create(axiosConfig)
const axiosOcsInstance = axios.create(axiosOcsConfig)

const axiosRequest = (payload) => {
	try {
		const response = axiosInstance.request(payload)
		return response
	} catch (e) {
		if (axios.canceled()) {
			throw new CancelledRequest('Request cancelled')
		}
		throw e
	}
}

const axiosOcsRequest = (payload) => {
	try {
		const response = axiosOcsInstance.request(payload)
		return response
	} catch (e) {
		if (axios.canceled()) {
			throw new CancelledRequest('Request cancelled')
		}
		throw e
	}
}

/**
 * Description
 *
 * @param {any} apiObject
 * @return {any}
 */
const createCancelTokenHandler = (apiObject) => {
	// initializing the cancel token handler object
	const cancelTokenHandler = {}

	// for each property in apiObject, i.e. for each request
	Object
		.getOwnPropertyNames(apiObject)
		.forEach((propertyName) => {
			// initializing the cancel token of the request
			const cancelTokenRequestHandler = {
				cancelToken: undefined,
			}

			// associating the cancel token handler to the request name
			cancelTokenHandler[propertyName] = {
				handleRequestCancellation: () => {
					// if a previous cancel token exists,
					// cancel the request
					cancelTokenRequestHandler.cancelToken && cancelTokenRequestHandler.cancelToken.cancel(`${propertyName} canceled`)

					// creating a new cancel token
					cancelTokenRequestHandler.cancelToken = CancelToken.source()

					// returning the new cancel token
					return cancelTokenRequestHandler.cancelToken
				},
			}
		})

	return cancelTokenHandler
}

export { axiosConfig, axiosOcsInstance, axiosOcsRequest, axiosRequest, axiosInstance, createCancelTokenHandler }
