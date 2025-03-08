<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref } from 'vue'
	import { t } from '@nextcloud/l10n'

	import NcModal from '@nextcloud/vue/components/NcModal'

	import ShareIcon from 'vue-material-design-icons/ShareVariant.vue'

	import { ConfigBox, QrModal } from '../Base/index.js'
	import ShareItem from './ShareItem.vue'
	import UserSearch from '../User/UserSearch.vue'
	import SharePublicAdd from './SharePublicAdd.vue'
	import ShareItemAllUsers from './ShareItemAllUsers.vue'
	import MarkUpDescription from '../Poll/MarkUpDescription.vue'

	import { usePollStore } from '../../stores/poll.ts'
	import { useSharesStore, Share } from '../../stores/shares.ts'
	import { useSessionStore } from '../../stores/session.ts'

	const pollStore = usePollStore()
	const sharesStore = useSharesStore()
	const sessionStore = useSessionStore()

	const qrModal = ref(false)
	const qrText = ref('')
	const configBoxProps = {
		sharesList: {
			name: t('polls', 'Shares'),
		},
	}

	function openQrModal(share: Share) {
		qrText.value = share.URL
		qrModal.value = true
	}

</script>

<template>
	<ConfigBox v-bind="configBoxProps.sharesList">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch v-if="sessionStore.appPermissions.addShares" class="add-share" />
		<ShareItemAllUsers v-if="sessionStore.appPermissions.allAccess" />
		<SharePublicAdd v-if="sessionStore.appPermissions.publicShares && sessionStore.appPermissions.addShares && sessionStore.appPermissions.addSharesExternal" />

		<div v-if="sharesStore.active.length" class="shares-list shared">
			<TransitionGroup tag="div"
				name="list"
				:css="false">
				<ShareItem v-for="(share) in sharesStore.active"
					:key="share.id"
					:share="share"
					@show-qr-code="openQrModal(share)" />
			</TransitionGroup>
		</div>

		<NcModal v-if="qrModal" size="small" @close="qrModal=false">
			<QrModal :name="pollStore.configuration.title"
				:description="pollStore.configuration.description"
				:encode-text="qrText"
				class="modal__content">
				<template #description>
					<MarkUpDescription />
				</template>
			</QrModal>
		</NcModal>
	</ConfigBox>
</template>

<style lang="scss">
.shares-list.shared {
	border-top: 1px solid var(--color-border);
	padding-top: 24px;
	margin-top: 16px;
}

</style>
