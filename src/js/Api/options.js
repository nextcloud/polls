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

import { axiosInstance, createCancelTokenHandler } from './AxiosHelper.js'

const options = {
	getOptions(pollId) {
		return axiosInstance.request({
			method: 'GET',
			url: `poll/${pollId}/options`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.getOptions.name].handleRequestCancellation().token,
		})
	},

	addOption(option) {
		return axiosInstance.request({
			method: 'POST',
			url: 'option',
			data: { ...option },
			cancelToken: cancelTokenHandlerObject[this.addOption.name].handleRequestCancellation().token,
		})
	},

	updateOption(option) {
		return axiosInstance.request({
			method: 'PUT',
			url: `option/${option.id}`,
			// TODO: replace text with timestamp
			data: { ...option },
			cancelToken: cancelTokenHandlerObject[this.updateOption.name].handleRequestCancellation().token,
		})
	},

	deleteOption(optionId) {
		return axiosInstance.request({
			method: 'DELETE',
			url: `option/${optionId}`,
			params: { time: +new Date() },
			cancelToken: cancelTokenHandlerObject[this.deleteOption.name].handleRequestCancellation().token,
		})
	},

	addOptions(pollId, optionsBatch) {
		return axiosInstance.request({
			method: 'POST',
			url: 'option/bulk',
			data: {
				pollId,
				text: optionsBatch,
			},
			cancelToken: cancelTokenHandlerObject[this.takeOver.name].handleRequestCancellation().token,
		})
	},

	confirmOption(optionId) {
		return axiosInstance.request({
			method: 'PUT',
			url: `option/${optionId}/confirm`,
			cancelToken: cancelTokenHandlerObject[this.confirmOption.name].handleRequestCancellation().token,
		})
	},

	reorderOptions(pollId, options) {
		return axiosInstance.request({
			method: 'POST',
			url: `poll/${pollId}/options/reorder`,
			data: { options },
			cancelToken: cancelTokenHandlerObject[this.reorderOptions.name].handleRequestCancellation().token,
		})
	},

	addOptionsSequence(optionId, step, unit, amount) {
		return axiosInstance.request({
			method: 'POST',
			url: `option/${optionId}/sequence`,
			data: {
				step,
				unit,
				amount,
			},
			cancelToken: cancelTokenHandlerObject[this.addOptionsSequence.name].handleRequestCancellation().token,
		})
	},

	shiftOptions(pollId, step, unit) {
		return axiosInstance.request({
			method: 'POST',
			url: `poll/${pollId}/shift`,
			data: {
				step,
				unit,
			},
			cancelToken: cancelTokenHandlerObject[this.shiftOptions.name].handleRequestCancellation().token,
		})
	},

}

const cancelTokenHandlerObject = createCancelTokenHandler(options)

export { options as OptionsAPI }
