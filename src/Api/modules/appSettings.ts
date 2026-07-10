import type { AxiosResponse } from '@nextcloud/axios'
import type { AppSettingsStore, Group } from '../../stores/appSettings.types'
import type { ISearchType, User } from '../../Types'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createCancelTokenHandler, httpInstance } from './HttpApi'

const appSettings = {
	getAppSettings(): Promise<AxiosResponse<{ appSettings: AppSettingsStore }>> {
		return httpInstance.request({
			method: 'GET',
			url: 'settings/app',
			params: { time: +new Date() },
			signal: cancelTokenHandlerObject[
				this.getAppSettings.name
			].handleRequestCancellation().signal,
		})
	},

	writeAppSettings(
		appSettings: AppSettingsStore,
	): Promise<AxiosResponse<{ appSettings: AppSettingsStore }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'settings/app',
			data: { appSettings },
			signal: cancelTokenHandlerObject[
				this.writeAppSettings.name
			].handleRequestCancellation().signal,
		})
	},

	getGroups(query: string): Promise<AxiosResponse<{ groups: Group[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `groups${query.trim() ? `/${query.trim()}` : ''}`,
			signal: cancelTokenHandlerObject[
				this.getGroups.name
			].handleRequestCancellation().signal,
		})
	},

	getUsers(
		query: string,
		types: ISearchType[],
	): Promise<AxiosResponse<{ siteusers: User[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `search/users${query.trim() ? `/${query.trim()}` : ''}`,
			params: { types: types.toString() },
			signal: cancelTokenHandlerObject[
				this.getUsers.name
			].handleRequestCancellation().signal,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(appSettings)

export default appSettings
