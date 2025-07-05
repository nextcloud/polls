<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'

import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
import SpeakerBigIcon from 'vue-material-design-icons/BullhornVariant.vue'
import DescriptionIcon from 'vue-material-design-icons/TextBox.vue'

import { ConfigBox } from '../Base/index.ts'

import { usePollGroupsStore } from '../../stores/pollGroups.ts'
import { showError, showInfo } from '@nextcloud/dialogs'
import { useRoute } from 'vue-router'
import { router } from '../../router.ts'
import ConfigTitlePollGroup from './ConfigNamePollGroup.vue'
import ConfigTitleExtPollGroup from './ConfigTitleExtPollGroup.vue'
import ConfigDescriptionPollGroup from './ConfigDescriptionPollGroup.vue'

const pollGroupsStore = usePollGroupsStore()

const route = useRoute()

async function updatePollGroup() {
	try {
		// block the modal to prevent double submission
		pollGroupsStore.updating = true
		const pollGroup = await pollGroupsStore.writeCurrentPollGroup()
		if (pollGroup) {
			if (route.name === 'group' && pollGroup.slug !== route.params.slug) {
				// if the slug has changed, we need to reroute
				router.push({
					name: 'group',
					params: { slug: pollGroup.slug },
				})
				showInfo(
					t(
						'polls',
						'Note: Based on the name change, the URL has also changed',
					),
				)
			}
		}
	} catch {
		showError(t('polls', 'Error updating poll group'))
	} finally {
		pollGroupsStore.updating = false
	}
}
</script>

<template>
	<div class="edit-poll-group">
		<ConfigBox :name="t('polls', 'Name')">
			<template #icon>
				<SpeakerIcon />
			</template>

			<ConfigTitlePollGroup @change="updatePollGroup" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Extended title')">
			<template #icon>
				<SpeakerBigIcon />
			</template>
			<ConfigTitleExtPollGroup @change="updatePollGroup" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Description')">
			<template #icon>
				<DescriptionIcon />
			</template>
			<ConfigDescriptionPollGroup @change="updatePollGroup" />
		</ConfigBox>
	</div>
</template>

<style lang="scss">
.edit-poll-group {
	background-color: var(--color-main-background);
	padding: 8px 20px;

	.create-buttons {
		display: flex;
		justify-content: flex-end;
		gap: 8px;
	}

	.input-textarea {
		width: 99%;
		resize: vertical;
	}

	.helper {
		min-height: 1.5rem;
		font-size: 0.8em;
		opacity: 0.8;
	}
}
</style>
