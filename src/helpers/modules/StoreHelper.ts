/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { useVotesStore, Vote } from '../../stores/votes'
import { Poll, usePollStore } from '../../stores/poll'
import { Option, useOptionsStore } from '../../stores/options'

const StoreHelper = {
	updateStores(data: { poll?: Poll; votes?: Vote[]; options?: Option[] }) {
		const pollStore = usePollStore()
		const votesStore = useVotesStore()
		const optionsStore = useOptionsStore()

		if (Object.hasOwn(data, 'polls')) {
			pollStore.$patch(data.poll as Poll)
		}
		if (Object.hasOwn(data, 'votes')) {
			votesStore.list = data.votes as Vote[]
		}
		if (Object.hasOwn(data, 'options')) {
			optionsStore.list = data.options as Option[]
		}
	},
}

export { StoreHelper }
