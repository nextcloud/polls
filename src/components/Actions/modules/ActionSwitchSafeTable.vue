<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="action toggle-sidebar">
		<NcButton type="primary"
			:aria-label="caption"
			@click="clickAction()">
			{{ caption }}
		</NcButton>
	</div>
</template>

<script>
import { mapStores } from 'pinia'
import { NcButton } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

export default {
	name: 'ActionSwitchSafeTable',

	components: {
		NcButton,
	},

	data() {
		return {
			caption: t('polls', 'Reveal them'),
		}
	},

	computed: {
		...mapStores(usePollStore),
	},

	methods: {
		clickAction() {
			this.pollStore.switchSafeTable()
		},
	},
}
</script>
