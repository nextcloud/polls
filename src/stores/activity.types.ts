/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type Activity = {
	activity_id: number
	app: string
	type: string
	user: string
	subject: string
	subject_rich: []
	message: string
	message_rich: []
	object_type: string
	object_id: number
	link: string
	icon: string
	datetime: string
}

export type ActivitiyStore = {
	activities: Activity[]
}
