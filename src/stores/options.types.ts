/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { DateTime, Duration } from 'luxon'
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

export type HasIsoFields = {
	isoTimestamp: string | null | undefined
	isoDuration: string | null | undefined
}
export type OptionDto = {
	id: number
	pollId: number
	text: string
	isoTimestamp: string | null | undefined
	deleted: number
	order: number
	confirmed: number
	isoDuration: string | null | undefined
	locked: boolean
	hash: string
	isOwner: boolean
	votes: OptionVotes
	owner: User | undefined
}

export type OptionDurationMethod = {
	getDuration: () => Duration
}

export type OptionTimestampMethod = {
	getDateTime: () => DateTime
}

export type Option = OptionDto & OptionDurationMethod & OptionTimestampMethod

export type SimpleOptionDto = Pick<
	OptionDto,
	'text' | 'isoTimestamp' | 'isoDuration'
>

export type SimpleOption = SimpleOptionDto
	& OptionDurationMethod
	& OptionTimestampMethod

export type DateOptionFinder = Pick<SimpleOption, 'isoTimestamp' | 'isoDuration'>

export type OptionsStore = {
	options: Option[]
	ranked: RankedType
}
