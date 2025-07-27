/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type ApiEmailAdressList = {
	displayName: string
	emailAddress: string
	combined: string
}

export { default as ActivityAPI } from './modules/activity'
export { default as AdminAPI } from './modules/admin'
export { default as AppSettingsAPI } from './modules/appSettings'
export { default as CalendarAPI } from './modules/calendar'
export { default as CommentsAPI } from './modules/comments'
export { default as OptionsAPI } from './modules/options'
export { default as PollsAPI } from './modules/polls'
export { default as PollGroupsAPI } from './modules/pollGroups'
export { default as PublicAPI } from './modules/public'
export { default as SharesAPI } from './modules/shares'
export { default as UserSettingsAPI } from './modules/userSettings'
export { default as ValidatorAPI } from './modules/validators'
export { default as VotesAPI } from './modules/votes'
export { default as SessionAPI } from './modules/session'
