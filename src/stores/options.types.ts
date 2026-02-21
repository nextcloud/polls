/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '../Types'
import type { DateTimeUnitType } from '../Types/dateTime'
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

export type Option = {
	id: number
	pollId: number
	text: string
	isoTimestamp: string
	deleted: number
	order: number
	confirmed: number
	isoDuration: string
	locked: boolean
	hash: string
	isOwner: boolean
	votes: OptionVotes
	owner: User | undefined
}

export type SimpleOption = Pick<Option, 'text' | 'isoTimestamp' | 'isoDuration'>

export type DateOptionFinder = Pick<SimpleOption, 'isoTimestamp' | 'isoDuration'>

export type OptionsStore = {
	options: Option[]
	ranked: RankedType
}
