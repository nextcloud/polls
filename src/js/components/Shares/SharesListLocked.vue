<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ConfigBox v-if="lockedShares.length" v-bind=" configBoxProps.lockedShares">
		<template #icon>
			<LockedIcon />
		</template>
		<TransitionGroup is="div"
			name="list"
			:css="false"
			class="shares-list">
			<ShareItem v-for="(share) in lockedShares"
				:key="share.id"
				:share="share" />
		</TransitionGroup>
	</ConfigBox>
</template>

<script>
import { mapGetters } from 'vuex'
import { ConfigBox } from '../Base/index.js'
import LockedIcon from 'vue-material-design-icons/Lock.vue'
import ShareItem from './ShareItem.vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'SharesListLocked',

	components: {
		LockedIcon,
		ConfigBox,
		ShareItem,
	},

	data() {
		return {
			configBoxProps: {
				lockedShares: {
					name: t('polls', 'Locked shares (read only access)'),
				},
			},
		}
	},

	computed: {
		...mapGetters({
			lockedShares: 'shares/locked',
		}),
	},
}
</script>
