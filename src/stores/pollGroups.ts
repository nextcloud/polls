/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import orderBy from 'lodash/orderBy'

import { t } from '@nextcloud/l10n'
import { useSessionStore } from './session'
import { usePollsStore } from './polls'

import { PollGroupsAPI } from '../Api'

import { Logger } from '../helpers'

import type { AxiosError } from '@nextcloud/axios'
import type { Poll } from './poll.types'
import type { PollGroup } from './pollGroups.types'

export const usePollGroupsStore = defineStore('pollGroups', () => {
	const pollGroups = ref<PollGroup[]>([])
	const updating = ref(false)

	/**
	 * Currently selected pollsgroup or undefined if not in a pollsgroup route
	 * @return {PollGroup | undefined} The current poll group if in a group route, otherwise undefined
	 */
	const currentPollGroup = computed((): PollGroup | undefined => {
		const sessionStore = useSessionStore()
		if (sessionStore.route.name === 'group') {
			return pollGroups.value.find(
				(group) => group.slug === sessionStore.route.params.slug,
			)
		}
		return undefined
	})

	/**
	 * Sort poll groups by title in ascending order
	 * Groups without accessible polls are hidden
	 * @return {PollGroup[]} Sorted poll groups, sorted by title in ascending order
	 */
	const pollGroupsSorted = computed((): PollGroup[] => {
		const pollsStore = usePollsStore()
		return orderBy(
			pollGroups.value.filter(
				(group) => (pollsStore.listMeta.pollGroupCounts[group.id] ?? 0) > 0,
			),
			['title'],
			['asc'],
		)
	})

	/**
	 * Returns a list of poll groups the poll can be added to.
	 *
	 * @param pollId - The ID of the poll to check.
	 * @return {PollGroup[]} List of poll groups that do not include the given pollId.
	 */
	function addablePollGroups(pollId: number): PollGroup[] {
		return pollGroups.value.filter((group) => !group.pollIds.includes(pollId))
	}

	/**
	 * Sets the current poll group attributes with the given payload.
	 * This function updates the current poll group in the store without saving it to the API
	 * as a temporary state.
	 * @param payload
	 * @param payload.name
	 * @param payload.titleExt
	 * @param payload.description
	 */
	function setCurrentPollGroup(payload: {
		name?: string
		titleExt?: string
		description?: string
	}): void {
		if (!currentPollGroup.value) {
			throw new Error('No current poll group set')
		}

		pollGroups.value = pollGroups.value.map((group) => {
			if (group.id === currentPollGroup.value?.id) {
				return {
					...group,
					name: payload.name ?? group.name,
					titleExt: payload.titleExt ?? group.titleExt,
					description: payload.description ?? group.description,
				}
			}
			return group
		})
	}

	async function writeCurrentPollGroup(): Promise<PollGroup | undefined> {
		if (!currentPollGroup.value) {
			throw new Error('No current poll group set')
		}

		try {
			const response = await PollGroupsAPI.updatePollGroup({
				...currentPollGroup.value,
			})

			addOrUpdatePollGroupInList({ pollGroup: response.data.pollGroup })

			return response.data.pollGroup
		} catch (error) {
			if ((error as AxiosError)?.code === 'ERR_CANCELED') {
				return
			}
			Logger.error('Error updating poll group', {
				error,
				pollGroup: currentPollGroup.value,
			})
			throw error
		}
	}

	function addOrUpdatePollGroupInList(payload: { pollGroup: PollGroup }) {
		pollGroups.value = pollGroups.value
			.filter((g) => g.id !== payload.pollGroup.id)
			.concat(payload.pollGroup)
	}

	async function addPollToPollGroup(payload: {
		pollId: number
		pollGroupId?: number
		groupTitle?: string
	}) {
		const pollsStore = usePollsStore()
		let poll: Poll

		try {
			const response = await PollGroupsAPI.addPollToGroup(
				payload.pollId,
				payload.pollGroupId,
				payload.groupTitle,
			)
			addOrUpdatePollGroupInList({ pollGroup: response.data.pollGroup })
			poll = response.data.poll
		} catch (error) {
			if ((error as AxiosError)?.code === 'ERR_CANCELED') {
				return
			}
			Logger.error('Error adding poll to group', {
				error,
				payload,
			})
			// resync the lists, the refresh must not mask the error of the write
			await pollsStore.load().catch(() => undefined)
			throw error
		}

		await refreshPollLists(
			poll,
			'Error refreshing the polls after adding a poll to a group',
		)
	}

	async function removePollFromGroup(payload: {
		pollGroupId: number
		pollId: number
	}): Promise<void> {
		let poll: Poll

		try {
			const response = await PollGroupsAPI.removePollFromGroup(
				payload.pollGroupId,
				payload.pollId,
			)

			if (response.data.pollGroup === null) {
				// If the poll group was removed (=== null), remove it from the store
				pollGroups.value = pollGroups.value.filter(
					(group) => group.id !== payload.pollGroupId,
				)
			} else {
				// Otherwise, update the poll group in the store
				addOrUpdatePollGroupInList({ pollGroup: response.data.pollGroup })
			}
			poll = response.data.poll
		} catch (error) {
			if ((error as AxiosError)?.code === 'ERR_CANCELED') {
				return
			}
			Logger.error('Error removing poll from group', {
				error,
				payload,
			})
			throw error
		}

		await refreshPollLists(
			poll,
			'Error refreshing the polls after removing a poll from a group',
		)
	}

	/**
	 * Update the poll in the polls store and refresh the counts and lists
	 *
	 * Only called after the write succeeded, so a failing refresh is logged
	 * instead of being reported to the caller as a failed write
	 *
	 * @param poll the poll as returned by the write
	 * @param message log message of a failing refresh
	 */
	async function refreshPollLists(poll: Poll, message: string): Promise<void> {
		const pollsStore = usePollsStore()

		try {
			await pollsStore.addOrUpdatePollGroupInList({ poll })
		} catch (error) {
			Logger.error(message, { error })
		}
	}

	function getPollGroupName(PollGroupId: number): string {
		const group = pollGroups.value.find((group) => group.id === PollGroupId)
		if (group) {
			return group.name
		}
		return t('polls', 'Invalid Group ID')
	}

	return {
		pollGroups,
		updating,
		pollGroupsSorted,
		currentPollGroup,
		addablePollGroups,
		setCurrentPollGroup,
		setPollGroupElement: addOrUpdatePollGroupInList,
		writeCurrentPollGroup,
		addPollToPollGroup,
		removePollFromGroup,
		getPollGroupName,
	}
})
