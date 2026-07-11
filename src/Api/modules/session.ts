import type { AxiosResponse } from '@nextcloud/axios'
import type { Session } from '../../stores/session.types'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, httpInstance } from './HttpApi.js'

// eslint-disable-next-line prefer-const -- assigned below, after `session` is fully defined
let cancelTokenHandlerObject: ReturnType<typeof createCancelTokenHandler>

const session = {
	getSession(): Promise<AxiosResponse<Session>> {
		return httpInstance.request({
			method: 'GET',
			url: '/session',
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getSession.name
			].handleRequestCancellation().signal,
		})
	},
}

cancelTokenHandlerObject = createCancelTokenHandler(session)

export default session
