/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// fallow-ignore-file circular-dependency

import type { OptionDto } from '../../stores/options.types'
import type { Poll } from '../../stores/poll.types'
import type { Vote } from '../../stores/votes.types'

import { useOptionsStore } from '../../stores/options.ts'
import { usePollStore } from '../../stores/poll.ts'
import { useVotesStore } from '../../stores/votes.ts'

const StoreHelper = {
	updateStores(data: { poll?: Poll; votes?: Vote[]; options?: OptionDto[] }) {
		const pollStore = usePollStore()
		const votesStore = useVotesStore()
		const optionsStore = useOptionsStore()

		if (Object.hasOwn(data, 'poll')) {
			pollStore.$patch(data.poll as Poll)
		}
		if (Object.hasOwn(data, 'votes')) {
			votesStore.votes = data.votes as Vote[]
		}
		if (Object.hasOwn(data, 'options')) {
			optionsStore.options = optionsStore.optionsDtoToOptions(
				data.options as OptionDto[],
			)
		}
	},
}

export { StoreHelper }
