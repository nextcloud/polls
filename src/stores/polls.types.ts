/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Poll, PollType } from './poll.types'
import { StatusResults } from '../Types'

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
}

export type PollCategoryList = Record<FilterType, PollCategory>

/**
 * Query for one page of polls, filtering is done by the server.
 * `pollGroup` takes precedence over `category`.
 */
export type PollListQuery = {
	category?: FilterType
	pollGroup?: number
	type?: PollType
	sortBy: SortType
	sortDirection: SortDirection
	offset: number
	limit: number
}

/**
 * Poll counts for the navigation, polls are loaded separately
 */
export type PollListMeta = {
	counts: Record<FilterType, number>
	pollGroupCounts: Record<number, number>
}

export type PaginatedPolls = {
	polls: Poll[]
	total: number
	status: StatusResults
}

export type PollsStore = {
	// current list view (category or poll group), pages are appended
	list: PaginatedPolls
	// non archived date polls for the combo sidebar
	datePolls: PaginatedPolls
	listMeta: PollListMeta & { status: StatusResults }
	// newest polls of expanded navigation entries, keyed by category id or poll group id
	navigationPolls: Partial<Record<FilterType | number, Poll[]>>
	meta: {
		pageSize: number
		maxPollsInNavigation: number
	}
	sort: {
		by: SortType
		reverse: boolean
	}
	categories: PollCategoryList
}
