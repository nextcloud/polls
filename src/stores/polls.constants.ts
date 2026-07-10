/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// fallow-ignore-file circular-dependency

import type { Poll } from './poll.types'
import type { PollCategoryList, SortOption, SortType } from './polls.types'

import { t } from '@nextcloud/l10n'
import { DateTime } from 'luxon'
import ParticipatedIcon from 'vue-material-design-icons/AccountCheckOutline.vue'
import MyPollsIcon from 'vue-material-design-icons/AccountOutline.vue'
import ArchivedPollsIcon from 'vue-material-design-icons/ArchiveOutline.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import RelevantIcon from 'vue-material-design-icons/ExclamationThick.vue'
import PrivatePollsIcon from 'vue-material-design-icons/Key.vue'
import ClosedPollsIcon from 'vue-material-design-icons/LockOutline.vue'
import AllPollsIcon from 'vue-material-design-icons/Poll.vue'
import AdministrationIcon from 'vue-material-design-icons/ShieldCrownOutline.vue'
import { useSessionStore } from './session'

export const sortOption: { [key in SortType]: SortOption } = {
	created: {
		id: 'created',
		sortProperty: 'status.created',
		name: t('polls', 'Created'),
		ariaLabel: t('polls', 'Sort by created date'),
	},
	title: {
		id: 'title',
		sortProperty: 'configuration.title',
		name: t('polls', 'Title'),
		ariaLabel: t('polls', 'Sort by title'),
	},
	access: {
		id: 'access',
		sortProperty: 'configuration.access',
		name: t('polls', 'Access'),
		ariaLabel: t('polls', 'Sort by access level'),
	},
	owner: {
		id: 'owner',
		sortProperty: 'owner.displayName',
		name: t('polls', 'Owner'),
		ariaLabel: t('polls', 'Sort by name of the owner'),
	},
	expire: {
		id: 'expire',
		sortProperty: 'configuration.expire',
		name: t('polls', 'Expiration'),
		ariaLabel: t('polls', 'Sort by expiration date'),
	},
	interaction: {
		id: 'interaction',
		sortProperty: 'status.lastInteraction',
		name: t('polls', 'Activity'),
		ariaLabel: t('polls', 'Sort by activity'),
	},
}

export const pollCategories: PollCategoryList = {
	relevant: {
		id: 'relevant',
		name: t('polls', 'Relevant'),
		title: t('polls', 'Relevant polls'),
		description: t(
			'polls',
			'Relevant polls which are relevant to you, because you are a participant, the owner or you are invited. Only polls not older than 100 days compared to creation, last activity, expiration or latest option (for date polls) are shown.',
		),
		pinned: false,
		iconComponent: RelevantIcon,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived
			&& DateTime.fromSeconds(poll.status.relevantThreshold).diffNow('days')
				.days > -100
			&& (poll.currentUserStatus.isInvolved
				|| (poll.permissions.view && poll.configuration.access !== 'open')),
	},
	my: {
		id: 'my',
		name: t('polls', 'My polls'),
		title: t('polls', 'My polls'),
		description: t('polls', 'These are all polls where you are the owner.'),
		pinned: false,
		iconComponent: MyPollsIcon,
		showInNavigation: () => useSessionStore().appPermissions.pollCreation,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.currentUserStatus.isOwner,
	},
	private: {
		id: 'private',
		name: t('polls', 'Private polls'),
		title: t('polls', 'Private polls'),
		description: t('polls', 'All private polls, to which you have access.'),
		pinned: false,
		iconComponent: PrivatePollsIcon,
		showInNavigation: () => useSessionStore().appPermissions.pollCreation,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived
			&& poll.permissions.view
			&& poll.configuration.access === 'private',
	},
	participated: {
		id: 'participated',
		name: t('polls', 'Participated'),
		title: t('polls', 'Participated'),
		description: t('polls', 'All polls in which you participated.'),
		pinned: false,
		iconComponent: ParticipatedIcon,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.currentUserStatus.countVotes > 0,
	},
	open: {
		id: 'open',
		name: t('polls', 'Openly accessible polls'),
		title: t('polls', 'Openly accessible polls'),
		description: t(
			'polls',
			'A complete list with all openly accessible polls on this site.',
		),
		pinned: false,
		iconComponent: OpenPollIcon,
		showInNavigation: () => useSessionStore().appPermissions.pollCreation,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.configuration.access === 'open',
	},
	all: {
		id: 'all',
		name: t('polls', 'All polls'),
		title: t('polls', 'All polls'),
		description: t('polls', 'All polls, where you have access to.'),
		pinned: false,
		iconComponent: AllPollsIcon,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived && poll.permissions.view,
	},
	closed: {
		id: 'closed',
		name: t('polls', 'Closed polls'),
		title: t('polls', 'Closed polls'),
		description: t('polls', 'All closed polls, where voting is disabled.'),
		pinned: false,
		iconComponent: ClosedPollsIcon,
		showInNavigation: () => true,
		filterCondition: (poll: Poll) =>
			!poll.status.isArchived
			&& poll.status.isExpired
			&& poll.permissions.view,
	},
	archived: {
		id: 'archived',
		name: t('polls', 'Archive'),
		title: t('polls', 'My archived polls'),
		description: t('polls', 'Your archived polls are only accessible to you.'),
		pinned: true,
		iconComponent: ArchivedPollsIcon,
		showInNavigation: () => useSessionStore().appPermissions.pollCreation,
		filterCondition: (poll: Poll) =>
			poll.status.isArchived && poll.permissions.view,
	},
	admin: {
		id: 'admin',
		name: t('polls', 'Administration'),
		title: t('polls', 'Administrative access'),
		description: t(
			'polls',
			'You can delete, archive and take over polls in this list, but access is still not possible.',
		),
		pinned: true,
		iconComponent: AdministrationIcon,
		showInNavigation: () => !!useSessionStore().currentUser?.isAdmin,
		filterCondition: (poll: Poll) =>
			useSessionStore().currentUser.id !== poll.owner.id,
	},
}
