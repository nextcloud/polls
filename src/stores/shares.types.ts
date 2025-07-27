/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '../Types'

export type ShareType =
	| 'email'
	| 'external'
	| 'contact'
	| 'user'
	| 'group'
	| 'admin'
	| 'public'
	| 'circle'
	| 'contactGroup'

export type PublicPollEmailConditions = 'mandatory' | 'optional' | 'disabled'

export type SharePurpose = 'poll' | 'pollGroup'

export type Share = {
	displayName: string
	id: string
	invitationSent: boolean
	locked: boolean
	pollId: number | null
	groupId: number | null
	token: string
	type: ShareType
	emailAddress: string
	userId: string
	publicPollEmail: PublicPollEmailConditions
	user: User
	reminderSent: boolean
	label: string
	URL: string
	voted: boolean
	deleted: boolean
}

export type SharesStore = {
	shares: Share[]
}
