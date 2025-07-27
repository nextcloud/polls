/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Poll } from './poll.types'
import { Chunking, StatusResults } from '../Types'

export type SortType =
	| 'created'
	| 'title'
	| 'access'
	| 'owner'
	| 'expire'
	| 'interaction'

export type SortDirection = 'asc' | 'desc'

export type FilterType =
	| 'relevant'
	| 'my'
	| 'private'
	| 'participated'
	| 'open'
	| 'all'
	| 'closed'
	| 'archived'
	| 'admin'

export type PollCategory = {
	id: FilterType
	title: string
	titleExt: string
	description: string
	pinned: boolean
	showInNavigation(): boolean
	filterCondition(poll: Poll): boolean
}

export type PollCategoryList = Record<FilterType, PollCategory>

export type Meta = {
	chunks: Chunking
	maxPollsInNavigation: number
	status: StatusResults
}

export type PollsStore = {
	polls: Poll[]
	// pollGroups: PollGroup[]
	meta: Meta
	sort: {
		by: SortType
		reverse: boolean
	}
	status: {
		loadingGroups: boolean
	}
	categories: PollCategoryList
}
