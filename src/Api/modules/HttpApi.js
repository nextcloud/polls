/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'

const clientSessionId = Math.random().toString(36).substring(2)

const axiosConfig = {
	baseURL: generateUrl('apps/polls/'),
	headers: {
		Accept: 'application/json',
		'Nc-Polls-Client-Id': clientSessionId,
		'Nc-Polls-Client-Time-Zone':
			Intl.DateTimeFormat().resolvedOptions().timeZone,
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
	Object.getOwnPropertyNames(apiObject).forEach((propertyName) => {
		const cancelTokenRequestHandler = {
			cancelToken: undefined,
		}

		cancelTokenHandler[propertyName] = {
			handleRequestCancellation: () => {
				cancelTokenRequestHandler.cancelToken
					&& cancelTokenRequestHandler.cancelToken.cancel(
						`${propertyName} canceled`,
					)
				cancelTokenRequestHandler.cancelToken = CancelToken.source()
				return cancelTokenRequestHandler.cancelToken
			},
		}
	})

	return cancelTokenHandler
}

export { ocsInstance, httpInstance, createCancelTokenHandler }
