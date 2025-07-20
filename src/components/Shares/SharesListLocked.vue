<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import ConfigBox from '../Base/modules/ConfigBox.vue'
import LockedIcon from 'vue-material-design-icons/Lock.vue'
import ShareItem from './ShareItem.vue'
import { t } from '@nextcloud/l10n'
import { useSharesStore } from '../../stores/shares.ts'

const sharesStore = useSharesStore()

const configBoxProps = {
	lockedShares: {
		name: t('polls', 'Locked shares (read only access)'),
	},
}
</script>

<template>
	<ConfigBox v-if="sharesStore.locked.length" v-bind="configBoxProps.lockedShares">
		<template #icon>
			<LockedIcon />
		</template>
		<TransitionGroup tag="div" name="list" :css="false" class="shares-list">
			<ShareItem
				v-for="share in sharesStore.locked"
				:key="share.id"
				:share="share" />
		</TransitionGroup>
	</ConfigBox>
</template>
