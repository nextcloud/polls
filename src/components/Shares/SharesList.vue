<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'

import NcModal from '@nextcloud/vue/components/NcModal'

import ShareIcon from 'vue-material-design-icons/ShareVariantOutline.vue'

import QrModal from '../Base/modules/QrModal.vue'
import ConfigBox from '../Base/modules/ConfigBox.vue'
import ShareItem from './ShareItem.vue'
import UserSearch from '../User/UserSearch.vue'
import SharePublicAdd from './SharePublicAdd.vue'
import ShareItemAllUsers from './ShareItemAllUsers.vue'
import MarkDownDescription from '../Poll/MarkDownDescription.vue'

import { usePollStore } from '../../stores/poll'
import { useSharesStore } from '../../stores/shares'
import { useSessionStore } from '../../stores/session'

import type { User } from '../../Types'
import type { Share } from '../../stores/shares.types'

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

/**
 *
 * @param share
 */
function openQrModal(share: Share) {
	qrText.value = share.URL
	qrModal.value = true
}

async function addShare(user: User) {
	try {
		await sharesStore.add(user)
	} catch {
		showError(t('polls', 'Error while adding share'))
	}
}
</script>

<template>
	<ConfigBox v-bind="configBoxProps.sharesList">
		<template #icon>
			<ShareIcon />
		</template>

		<UserSearch
			v-if="sessionStore.appPermissions.addShares"
			class="add-share"
			:aria-label="t('polls', 'Add shares')"
			:placeholder="t('polls', 'Type to add an individual share')"
			@user-selected="(user: User) => addShare(user)" />

		<!-- <template v-if="sharesStore.active.length"> -->
		<TransitionGroup tag="ul" name="list">
			<ShareItemAllUsers
				v-if="sessionStore.appPermissions.allAccess"
				tag="li" />

			<SharePublicAdd
				v-if="
					sessionStore.appPermissions.publicShares
					&& sessionStore.appPermissions.addShares
					&& sessionStore.appPermissions.addSharesExternal
				"
				tag="li" />

			<ShareItem
				v-for="share in sharesStore.active"
				:key="share.id"
				:share="share"
				tag="li"
				@show-qr-code="openQrModal(share)" />
		</TransitionGroup>
		<!-- </template> -->

		<NcModal v-if="qrModal" size="small" @close="qrModal = false">
			<QrModal
				:name="pollStore.configuration.title"
				:description="pollStore.configuration.description"
				:encode-text="qrText"
				class="modal__content">
				<template #description>
					<MarkDownDescription />
				</template>
			</QrModal>
		</NcModal>
	</ConfigBox>
</template>
