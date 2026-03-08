/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'
import { useSessionStore } from '../../stores/session.ts'

const axiosConfig = {
	baseURL: generateUrl('apps/polls/'),
	headers: {
		Accept: 'application/json',
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

httpInstance.interceptors.request.use((config) => {
	try {
		const sessionStore = useSessionStore()
		config.headers = {
			...config.headers,
			'Nc-Polls-Client-Id': sessionStore.watcher.id,
		}
	} catch {
		// ignore
	}
	return config
})
/**
 * Description
 *
 * @param {any} apiObject apiObject
 * @return {any}
 */
const createCancelTokenHandler = (apiObject) => {
	const cancelTokenHandler = {}
	Object.getOwnPropertyNames(apiObject).forEach((propertyName) => {
		const handlers = {}

		cancelTokenHandler[propertyName] = {
			handleRequestCancellation: (subKey) => {
				const key = String(subKey ?? '__default__')
				if (!handlers[key]) {
					handlers[key] = { cancelToken: undefined }
				}
				const handler = handlers[key]
				handler.cancelToken?.cancel(`${propertyName} canceled`)
				handler.cancelToken = CancelToken.source()
				return handler.cancelToken
			},
		}
	})

	return cancelTokenHandler
}

export { ocsInstance, httpInstance, createCancelTokenHandler }
