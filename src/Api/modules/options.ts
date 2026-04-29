/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { httpInstance, createCancelTokenHandler } from './HttpApi'

import type { DateTimeUnits } from '../../Types/dateTime'
import type { AxiosResponse } from '@nextcloud/axios'
import type { Vote } from '../../stores/votes.types'
import type { Option, OptionDto, Sequence, SimpleOption } from '../../stores/options.types'

const options = {
	getOptions(pollId: number): Promise<AxiosResponse<{ options: OptionDto[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `poll/${pollId}/options`,
			params: { time: +new Date() },
			signal:
				cancelTokenHandlerObject[
					this.getOptions.name
				].handleRequestCancellation().signal,
		})
	},

	addOption(
		pollId: number,
		option: SimpleOption,
		sequence: Sequence | null,
		voteYes: boolean = false,
	): Promise<
		AxiosResponse<{
			added: OptionDto[]
			options: OptionDto[]
			votes: Vote[]
		}>
	> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/option`,
			// data: { ...option },
			data: { option, sequence, voteYes },
			signal:
				cancelTokenHandlerObject[
					this.addOption.name
				].handleRequestCancellation().signal,
		})
	},

	updateOption(option: Option): Promise<AxiosResponse<{ option: OptionDto }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `option/${option.id}`,
			data: { ...option },
			signal:
				cancelTokenHandlerObject[
					this.updateOption.name
				].handleRequestCancellation().signal,
		})
	},

	deleteOption(optionId: number): Promise<AxiosResponse<{ option: OptionDto }>> {
		return httpInstance.request({
			method: 'DELETE',
			url: `option/${optionId}`,
			params: { time: +new Date() },
			signal:
				cancelTokenHandlerObject[
					this.deleteOption.name
				].handleRequestCancellation().signal,
		})
	},

	restoreOption(optionId: number): Promise<AxiosResponse<{ option: OptionDto }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `option/${optionId}/restore`,
			params: { time: +new Date() },

			signal:
				cancelTokenHandlerObject[
					this.restoreOption.name
				].handleRequestCancellation().signal,
		})
	},

	addOptions(
		pollId: number,
		optionsBatch: string,
	): Promise<
		AxiosResponse<{
			option: OptionDto
			repetitions: OptionDto[]
			options: OptionDto[]
			votes: Vote[]
		}>
	> {
		return httpInstance.request({
			method: 'POST',
			url: 'option/bulk',
			data: {
				pollId,
				text: optionsBatch,
			},
			signal:
				cancelTokenHandlerObject[
					this.addOptions.name
				].handleRequestCancellation().signal,
		})
	},

	confirmOption(optionId: number): Promise<AxiosResponse<{ option: OptionDto }>> {
		return httpInstance.request({
			method: 'PUT',
			url: `option/${optionId}/confirm`,
			signal:
				cancelTokenHandlerObject[
					this.confirmOption.name
				].handleRequestCancellation().signal,
		})
	},

	reorderOptions(
		pollId: number,
		options: {
			id: number
			text: string
		}[],
	): Promise<AxiosResponse<{ options: OptionDto[] }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/options/reorder`,
			data: { options },
			signal:
				cancelTokenHandlerObject[
					this.reorderOptions.name
				].handleRequestCancellation().signal,
		})
	},

	addOptionsSequence(
		optionId: number,
		sequence: Sequence,
	): Promise<AxiosResponse<{ options: OptionDto[] }>> {
		return httpInstance.request({
			method: 'POST',
			url: `option/${optionId}/sequence`,
			data: {
				sequence,
			},
			signal:
				cancelTokenHandlerObject[
					this.addOptionsSequence.name
				].handleRequestCancellation().signal,
		})
	},

	shiftOptions(
		pollId: number,
		step: number,
		unit: DateTimeUnits,
	): Promise<AxiosResponse<{ options: OptionDto[] }>> {
		return httpInstance.request({
			method: 'POST',
			url: `poll/${pollId}/shift`,
			data: {
				step,
				unit,
			},
			signal:
				cancelTokenHandlerObject[
					this.shiftOptions.name
				].handleRequestCancellation().signal,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(options)

export default options
