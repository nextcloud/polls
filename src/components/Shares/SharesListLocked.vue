<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import ConfigBox from '../Base/modules/ConfigBox.vue'
import LockedIcon from 'vue-material-design-icons/LockOutline.vue'
import ShareItem from './ShareItem.vue'
import { t } from '@nextcloud/l10n'
import { useSharesStore } from '../../stores/shares'

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
		<TransitionGroup tag="ul" name="list">
			<ShareItem
				v-for="share in sharesStore.locked"
				:key="share.id"
				tag="li"
				:share="share" />
		</TransitionGroup>
	</ConfigBox>
</template>
