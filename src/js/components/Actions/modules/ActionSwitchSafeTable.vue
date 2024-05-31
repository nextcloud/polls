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
import { mapMutations } from 'vuex'
import { NcButton } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

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

	methods: {
		...mapMutations({
			switchSafeTable: 'poll/switchSafeTable',
		}),

		clickAction() {
			this.switchSafeTable()
		},
	},
}
</script>
