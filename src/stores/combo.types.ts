/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Participant } from '../Types/index.ts'
import type { Poll } from './poll.types'
import type { Vote } from './votes.types'
import type { Option } from './options.types'

export type ComboStore = {
	id: number
	options: Option[]
	polls: Poll[]
	participants: Participant[]
	votes: Vote[]
}
