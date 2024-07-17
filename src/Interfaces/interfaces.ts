/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export enum StatusResults {
	Error = 'error',
	Warning = 'warning',
	Success = 'success',
	Loading = 'loading',
	Loaded = 'loaded',
}
export type PollPermissions = {
	addOptions: boolean
	archive: boolean
	comment: boolean
	delete: boolean
	edit: boolean
	seeResults: boolean
	seeUsernames: boolean
	subscribe: boolean
	view: boolean
	vote: boolean
}

export enum UserType {
	Email = 'email',
	External = 'external',
	Contact = 'contact',
	User = 'user',
	Group = 'group',
	Admin = 'admin',
	Public = 'public',
	Circle = 'circle',
	ContactGroup = 'contactGroup'
}

export enum VirtualUserItemType {
	AddPublicLink = 'addPublicLink',
	InternalAccess = 'internalAccess',
	Deleted = 'deleted',
	Anonymous = 'anonymous',
	None = '',
}

export enum UpdateType {
	NoPolling = 'noPolling'
}

export type AppSettings = {
	usePrivacyUrl: string,
	useImprintUrl: string,
	useLogin: boolean
	useActivity: boolean
	navigationPollsInList: boolean
	updateType: UpdateType,
}

export type AppPermissions = {
	allAccess: boolean
	publicShares: boolean
	pollCreation: boolean
	seeMailAddresses: boolean
	pollDownload: boolean
	comboView: boolean
}

export type User = {
	userId: string
	displayName: string
	emailAddress: string
	subName: string
	subtitle: string
	isNoUser: boolean
	desc: string
	type: UserType
	id: string
	user: string
	organisation: string
	languageCode: string
	localeCode: string
	timeZone: string
	categories: string[]
}
