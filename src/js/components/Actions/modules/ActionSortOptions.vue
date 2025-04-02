<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action sort-options">
		<NcButton variant="tertiary"
			:title="caption"
			:aria-label="caption"
			@click="clickAction()">
			<template #icon>
				<SortByDateOptionIcon v-if="isRanked && pollType === 'datePoll'" />
				<SortByOriginalOrderIcon v-else-if="isRanked && pollType === 'textPoll'" />
				<SortByRankIcon v-else />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
import { NcButton } from '@nextcloud/vue'
import SortByOriginalOrderIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import SortByRankIcon from 'vue-material-design-icons/FormatListNumbered.vue'
import SortByDateOptionIcon from 'vue-material-design-icons/SortClockAscendingOutline.vue'

export default {
	name: 'ActionSortOptions',

	components: {
		SortByRankIcon,
		SortByOriginalOrderIcon,
		SortByDateOptionIcon,
		NcButton,
	},

	computed: {
		...mapState({
			isRanked: (state) => state.options.ranked,
			pollType: (state) => state.poll.type,
		}),

		caption() {
			if (this.isRanked && this.pollType === 'datePoll') {
				return t('polls', 'Date order')
			}

			if (this.isRanked && this.pollType === 'textPoll') {
				return t('polls', 'Original order')
			}

			return t('polls', 'Ranked order')
		},
	},

	methods: {
		...mapMutations({
			clickAction: 'options/setRankOrder',
		}),
	},
}
</script>
