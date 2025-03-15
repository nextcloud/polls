/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'

const validators = {
	validateEmailAddress(emailAddress) {
		return httpInstance.request({
			method: 'GET',
			url: `check/emailaddress/${emailAddress}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.validateEmailAddress.name
				].handleRequestCancellation().token,
		})
	},

	validateName(pollToken, name) {
		return httpInstance.request({
			method: 'POST',
			url: 'check/username',
			cancelToken:
				cancelTokenHandlerObject[
					this.validateName.name
				].handleRequestCancellation().token,
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

const cancelTokenHandlerObject = createCancelTokenHandler(validators)

export default validators
