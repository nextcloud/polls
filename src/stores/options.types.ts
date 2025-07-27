/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '../Types/index.ts'
import type { DateTimeUnitType } from '../constants/dateUnits.ts'
import type { Answer } from './votes.types'

export type RankedType = 'yes' | 'no'

export type Sequence = {
	unit: DateTimeUnitType
	stepWidth: number
	repetitions: number
}

export type OptionVotes = {
	yes: number
	maybe: number
	no: number
	count: number
	currentUser?: Answer
}

export type SimpleOption = {
	text?: string
	timestamp?: number
	duration?: number
}

export type Option = {
	id: number
	pollId: number
	text: string
	timestamp: number
	deleted: number
	order: number
	confirmed: number
	duration: number
	locked: boolean
	hash: string
	isOwner: boolean
	votes: OptionVotes
	owner: User | undefined
}

export type OptionsStore = {
	options: Option[]
	ranked: RankedType
}
