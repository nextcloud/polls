<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { useSessionStore } from '../../stores/session'

const sessionStore = useSessionStore()
</script>

<template>
	<footer class="guest-box">
		<span class="footer__legal-links">
			<a
				v-if="sessionStore.appSettings.finalImprintUrl"
				:href="sessionStore.appSettings.finalImprintUrl"
				class="legal"
				target="_blank"
				rel="noreferrer noopener">
				{{ t('polls', 'Legal notice') }}
			</a>
			<template
				v-if="
					sessionStore.appSettings.finalImprintUrl
					&& sessionStore.appSettings.finalPrivacyUrl
				">
				·
			</template>
			<a
				v-if="sessionStore.appSettings.finalPrivacyUrl"
				:href="sessionStore.appSettings.finalPrivacyUrl"
				class="legal"
				target="_blank"
				rel="noreferrer noopener">
				{{ t('polls', 'Privacy policy') }}
			</a>
		</span>
	</footer>
</template>
