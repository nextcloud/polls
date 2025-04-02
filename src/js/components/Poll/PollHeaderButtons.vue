<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="poll-header-buttons">
		<UserMenu v-if="showUserMenu" />
		<NcPopover :focus-trap="false">
			<template #trigger>
				<NcButton :title="caption"
					:aria-label="caption"
					variant="tertiary">
					<template #icon>
						<PollInformationIcon />
					</template>
				</NcButton>
			</template>
			<PollInformation />
		</NcPopover>
		<ExportPoll v-if="allowDownload" />
	</div>
</template>

<script>
import { mapState } from 'vuex'
import { NcButton, NcPopover } from '@nextcloud/vue'
import PollInformationIcon from 'vue-material-design-icons/InformationOutline.vue'
import PollInformation from '../Poll/PollInformation.vue'
import UserMenu from '../User/UserMenu.vue'
import ExportPoll from '../Export/ExportPoll.vue'

export default {
	name: 'PollHeaderButtons',
	components: {
		PollInformationIcon,
		NcPopover,
		NcButton,
		UserMenu,
		ExportPoll,
		PollInformation,
	},

	data() {
		return {
			caption: t('polls', 'Poll informations'),
		}
	},

	computed: {
		...mapState({
			permissions: (state) => state.poll.permissions,
			allowDownload: (state) => state.acl.appPermissions.pollDownload,
		}),

		showUserMenu() {
			return this.$route.name !== 'publicVote' || this.permissions.vote || this.permissions.subscribe
		},
	},

	beforeDestroy() {
		this.$store.dispatch({ type: 'poll/reset' })
	},
}

</script>

<style lang="scss">
.poll-header-buttons {
	display: flex;
	flex: 0;
	justify-content: flex-end;
	align-self: flex-end;
	border-radius: var(--border-radius-pill);
}

.icon.icon-settings.active {
	display: block;
	width: 44px;
	height: 44px;
}

</style>
