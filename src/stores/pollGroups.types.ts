/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { User } from '../Types/index.ts'

export type PollGroup = {
	id: number
	created: number
	deleted: number
	description: string
	owner: User
	name: string
	titleExt: string
	pollIds: number[]
	slug: string
	allowEdit: boolean
}
