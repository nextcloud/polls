/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

import type { AxiosResponse } from '@nextcloud/axios'
import type { Session } from '../../stores/session.types'

const session = {
	getSession(): Promise<AxiosResponse<Session>> {
		return httpInstance.request({
			method: 'GET',
			url: '/session',
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getSession.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(session)

export default session
