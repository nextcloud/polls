/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// fallow-ignore-file circular-dependency
import axios from '@nextcloud/axios'
import { getLanguage } from '@nextcloud/l10n'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { useSessionStore } from '../../stores/session.ts'

const axiosConfig = {
	baseURL: generateUrl('apps/polls/'),
	headers: {
		Accept: 'application/json',
		'Nc-Polls-Client-Time-Zone':
			Intl.DateTimeFormat().resolvedOptions().timeZone,
		'Nc-Polls-Client-Language': getLanguage(),
	},
}

const axiosOcsConfig = {
	baseURL: generateOcsUrl('apps/'),
	headers: {
		Accept: 'application/json',
	},
}

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
function createCancelTokenHandler (apiObject) {
	const cancelTokenHandler = {}
	Object.getOwnPropertyNames(apiObject).forEach((propertyName) => {
		const handlers = {}

		cancelTokenHandler[propertyName] = {
			handleRequestCancellation: (subKey) => {
				const key = String(subKey ?? '__default__')
				if (!handlers[key]) {
					handlers[key] = { controller: undefined }
				}
				const handler = handlers[key]
				handler.controller?.abort(`${propertyName} canceled`)
				handler.controller = new AbortController()
				return handler.controller
			},
		}
	})

	return cancelTokenHandler
}

export { createCancelTokenHandler, httpInstance, ocsInstance }
