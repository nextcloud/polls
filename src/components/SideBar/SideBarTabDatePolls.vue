<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { onMounted } from 'vue'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

import { usePollsStore } from '../../stores/polls'
import { useComboStore } from '../../stores/combo'

import UserItem from '../User/UserItem.vue'

const pollsStore = usePollsStore()
const comboStore = useComboStore()

/**
 * Append the next page of date polls
 */
async function loadMore() {
	try {
		await pollsStore.loadMoreDatePolls()
	} catch {
		showError(t('polls', 'Error loading more polls'))
	}
}

onMounted(async () => {
	try {
		await pollsStore.loadDatePolls()
	} catch {
		showError(t('polls', 'Error loading polls'))
	}
})
</script>

<template>
	<div class="side-bar-tab-polls">
		<div
			v-for="poll in pollsStore.datePolls.polls"
			:key="poll.id"
			:class="['poll-item', { listed: comboStore.pollIsListed(poll.id) }]"
			@click="comboStore.togglePollItem(poll.id)">
			<UserItem :user="poll.owner" condensed />
			<div class="poll-title-box">
				{{ poll.configuration.title }}
			</div>
		</div>
		<NcButton
			v-if="pollsStore.hasMoreDatePolls"
			wide
			:disabled="pollsStore.datePolls.status === 'loading'"
			@click="loadMore">
			<template v-if="pollsStore.datePolls.status === 'loading'" #icon>
				<NcLoadingIcon :size="20" />
			</template>
			{{ t('polls', 'Load more') }}
		</NcButton>
	</div>
</template>

<style lang="scss">
.poll-item {
	display: flex;
	align-items: center;
	&.listed {
		background-color: var(--color-polls-background-yes);
		margin: 8px 0;
		border-bottom: 1px solid var(--color-border);
		border-radius: var(--border-radius-element);
		box-shadow: 2px 2px 6px var(--color-box-shadow);
	}
	.poll-title-box {
		transition: background-color 1s ease-out;
	}
}
</style>
