/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { Option, SimpleOption } from '../../stores/options.js'
import { DateUnitKeys } from '../../constants/dateUnits.js'
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'
import { AxiosResponse } from '@nextcloud/axios'

const options = {
	getOptions(pollId: number): Promise<AxiosResponse<{ options: Option[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/options`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getOptions.name
				].handleRequestCancellation().token,
		})
	},

	addOption(
		pollId: number,
		option: SimpleOption,
	): Promise<AxiosResponse<{ option: Option }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/option`,
			data: { ...option },
			cancelToken:
				cancelTokenHandlerObject[
					this.addOption.name
				].handleRequestCancellation().token,
		})
	},

	updateOption(option: Option): Promise<AxiosResponse<{ option: Option }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `option/${option.id}`,
			// TODO: replace text with timestamp
			data: { ...option },
			cancelToken:
				cancelTokenHandlerObject[
					this.updateOption.name
				].handleRequestCancellation().token,
		})
	},

	deleteOption(optionId: number): Promise<AxiosResponse<{ option: Option }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `option/${optionId}`,
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.deleteOption.name
				].handleRequestCancellation().token,
		})
	},

	restoreOption(optionId: number): Promise<AxiosResponse<{ option: Option }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `option/${optionId}/restore`,
			params: { time: +new Date() },

			cancelToken:
				cancelTokenHandlerObject[
					this.restoreOption.name
				].handleRequestCancellation().token,
		})
	},

	addOptions(
		pollId: number,
		optionsBatch: string,
	): Promise<AxiosResponse<{ options: Option[] }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'option/bulk',
			data: {
				pollId,
				text: optionsBatch,
			},
			cancelToken:
				cancelTokenHandlerObject[
					this.addOptions.name
				].handleRequestCancellation().token,
		})
	},

	confirmOption(optionId: number): Promise<AxiosResponse<{ option: Option }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `option/${optionId}/confirm`,
			cancelToken:
				cancelTokenHandlerObject[
					this.confirmOption.name
				].handleRequestCancellation().token,
		})
	},

	reorderOptions(
		pollId: number,
		options: {
			id: number
			text: string
		}[],
	): Promise<AxiosResponse<{ options: Option[] }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/options/reorder`,
			data: { options },
			cancelToken:
				cancelTokenHandlerObject[
					this.reorderOptions.name
				].handleRequestCancellation().token,
		})
	},

	addOptionsSequence(
		optionId: number,
		step: number,
		unit: DateUnitKeys,
		amount: number,
	): Promise<AxiosResponse<{ options: Option[] }>> {
		return httpInstance.request({
			method: 'POST',
			url: `option/${optionId}/sequence`,
			data: {
				step,
				unit,
				amount,
			},
			cancelToken:
				cancelTokenHandlerObject[
					this.addOptionsSequence.name
				].handleRequestCancellation().token,
		})
	},

	shiftOptions(
		pollId: number,
		step: number,
		unit: DateUnitKeys,
	): Promise<AxiosResponse<{ options: Option[] }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/shift`,
			data: {
				step,
				unit,
			},
			cancelToken:
				cancelTokenHandlerObject[
					this.shiftOptions.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(options)

export default options
