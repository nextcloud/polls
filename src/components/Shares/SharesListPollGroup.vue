<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { User } from '../../Types/index.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import ShareIcon from 'vue-material-design-icons/ShareVariantOutline.vue'
import ConfigBox from '../Base/modules/ConfigBox.vue'
import UserSearch from '../User/UserSearch.vue'
import ShareItem from './ShareItem.vue'
import { useSharesStore } from '../../stores/shares.ts'

const { info } = defineProps<{
	info: string
}>()
const sharesStore = useSharesStore()
const configBoxProps = {
	sharesList: {
		name: t('polls', 'Shares'),
	},
}

async function addShare(user: User) {
	try {
		await sharesStore.add(user, 'pollGroup')
	} catch {
		showError(t('polls', 'Error while adding share'))
	}
}
</script>

<template>
	<ConfigBox v-bind="configBoxProps.sharesList" :info="info">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch
			class="add-share"
			:aria-label="t('polls', 'Add shares')"
			:placeholder="t('polls', 'Type to add an individual share')"
			:searchTypes="[0]"
			@userSelected="(user: User) => addShare(user)" />

		<template v-if="sharesStore.shares">
			<TransitionGroup tag="ul" name="list">
				<ShareItem
					v-for="share in sharesStore.active"
					:key="share.id"
					tag="li"
					:share="share" />
			</TransitionGroup>
		</template>
	</ConfigBox>
</template>

<style lang="scss">
.shares-list.shared {
	border-top: 1px solid var(--color-border);
	padding-top: 24px;
	margin-top: 16px;
}
</style>
