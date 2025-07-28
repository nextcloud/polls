<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import ShareIcon from 'vue-material-design-icons/ShareVariant.vue'

import ConfigBox from '../Base/modules/ConfigBox.vue'
import ShareItem from './ShareItem.vue'
import UserSearch from '../User/UserSearch.vue'

import { useSharesStore } from '../../stores/shares'
import { showError } from '@nextcloud/dialogs'
import type { User } from '../../Types'

const sharesStore = useSharesStore()
const { info } = defineProps<{
	info?: string
}>()

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
			:search-types="[0]"
			@user-selected="(user: User) => addShare(user)" />

		<div v-if="sharesStore.shares" class="shares-list shared">
			<TransitionGroup tag="div" name="list" :css="false">
				<ShareItem
					v-for="share in sharesStore.active"
					:key="share.id"
					:share="share" />
			</TransitionGroup>
		</div>
	</ConfigBox>
</template>

<style lang="scss">
.shares-list.shared {
	border-top: 1px solid var(--color-border);
	padding-top: 24px;
	margin-top: 16px;
}
</style>
