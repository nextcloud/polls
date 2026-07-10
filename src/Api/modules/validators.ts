import type { AxiosResponse } from '@nextcloud/axios'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, httpInstance } from './HttpApi.js'

// eslint-disable-next-line prefer-const -- assigned below, after `validators` is fully defined
let cancelTokenHandlerObject: ReturnType<typeof createCancelTokenHandler>

const validators = {
	validateEmailAddress(
		emailAddress: string,
	): Promise<AxiosResponse<{ result: boolean; emailAddress: string }>> {
		return httpInstance.request({
			method: 'GET',
			url: `check/emailaddress/${emailAddress}`,
			signal: cancelTokenHandlerObject[
				this.validateEmailAddress.name
			].handleRequestCancellation().signal,
		})
	},

	validateName(
		pollToken: string | string[],
		name: string,
	): Promise<AxiosResponse<{ name: string }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'check/username',
			signal: cancelTokenHandlerObject[
				this.validateName.name
			].handleRequestCancellation().signal,
			data: {
				displayName: name,
				token: pollToken,
			},
			headers: {
				'Nc-Polls-Share-Token': pollToken,
			},
		})
	},
}

cancelTokenHandlerObject = createCancelTokenHandler(validators)

export default validators
