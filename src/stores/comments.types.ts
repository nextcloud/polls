/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '../Types'

export type Comment = {
	comment: string
	deleted: number
	id: number
	parent: number
	pollId: number
	timestamp: number
	user: User
	confidential: number
	recipient: User | null
}

export type ShortComment = {
	comment: string
	deleted: number
	id: number
}

export interface CommentsGrouped extends Comment {
	comments: Comment[]
}

export type CommentsStore = {
	comments: Comment[]
}
