/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { AxiosResponse } from '@nextcloud/axios'
import { AppSettings, Group } from '../../stores/appSettings.js'
import { httpInstance, createCancelTokenHandler } from './HttpApi.js'
import { ISearchType, User } from '../../Types/index.js'

const appSettings = {
	getAppSettings(): Promise<AxiosResponse<{ appSettings: AppSettings }>> {
		return httpInstance.request({
			method: 'GET',
			url: 'settings/app',
			params: { time: +new Date() },
			cancelToken:
				cancelTokenHandlerObject[
					this.getAppSettings.name
				].handleRequestCancellation().token,
		})
	},

	writeAppSettings(
		appSettings: AppSettings,
	): Promise<AxiosResponse<{ appSettings: AppSettings }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'settings/app',
			data: { appSettings },
			cancelToken:
				cancelTokenHandlerObject[
					this.writeAppSettings.name
				].handleRequestCancellation().token,
		})
	},

	getGroups(query: string): Promise<AxiosResponse<{ groups: Group[] }>> {
		return httpInstance.request({
			method: 'GET',
			url: `groups${query.trim() ? `/${query.trim()}` : ''}`,
			cancelToken:
				cancelTokenHandlerObject[
					this.getGroups.name
				].handleRequestCancellation().token,
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
			cancelToken:
				cancelTokenHandlerObject[
					this.getUsers.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(appSettings)

export default appSettings
