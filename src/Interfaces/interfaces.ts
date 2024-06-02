export interface PollPermissions {
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

export type UserType = 'email' | 'external' | 'contact' | 'user' | 'group' | 'admin' | 'public' | 'circle' | 'contactGroup'
export type UpdateType = 'noPolling'

export interface AppSettings {
	usePrivacyUrl: string,
	useImprintUrl: string,
	useLogin: boolean
	useActivity: boolean
	navigationPollsInList: boolean
	updateType: UpdateType,
}

export interface AppPermissions {
	allAccess: boolean
	publicShares: boolean
	pollCreation: boolean
	seeMailAddresses: boolean
	pollDownload: boolean
}

export interface User {
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
	icon: string
	categories: string[]
}
