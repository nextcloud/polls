<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'

import PublicFooter from '../components/Public/PublicFooter.vue'
import { InlineLink } from '../helpers/modules/InlineLink'
import { useSessionStore } from '../stores/session'

import PollTitle from '@/components/Poll/PollTitle.vue'
import PollInfoLine from '@/components/Poll/PollInfoLine.vue'
import RegistrationForm from '@/components/Public/RegistrationForm.vue'
import PollDescription from '@/components/Poll/PollDescription.vue'
import OptionPreview from '@/components/Options/OptionPreview.vue'

defineOptions({
	inheritAttrs: false,
})

const route = useRoute()
const router = useRouter()

const sessionStore = useSessionStore()

const loginLink = computed(() => {
	const redirectUrl = router.resolve({
		name: 'publicVote',
		params: { token: route.params.token },
	}).href

	return `${generateUrl('/login')}?redirect_url=${redirectUrl}`
})
</script>

<template>
	<NcAppContent class="register-view">
		<div class="register-view__col">
			<div class="register-view__header">
				<PollTitle />
				<PollInfoLine />
			</div>

			<div class="register-view__form">
				<RegistrationForm />
			</div>

			<div
				v-if="sessionStore.appSettings.useLogin"
				class="register-view__login">
				<!-- TRANSLATORS Position [link]...[/link] around the word to link. Will be replaced with <a href="..."> and </a> -->
				<InlineLink
					:text="
						t(
							'polls',
							'Or [link]login[/link] if you are a member of this site.',
						)
					"
					:href="loginLink" />
			</div>
		</div>

		<div class="register-view__col">
			<PollDescription />
			<OptionPreview />
		</div>
		<PublicFooter />
	</NcAppContent>
</template>

<style lang="scss">
.app-content.register-view {
	--bg-glass: rgb(from var(--color-background-assistant) r g b / 0.7);
	background: none !important;
	padding-bottom: 3rem !important;
	margin: 0 auto !important;
}

.register-view {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	align-content: flex-start;
	gap: 1rem;
	padding: 1.5rem 1rem;
	max-width: 100rem;

	.register-view__col {
		flex: 1 1 27rem;
		display: flex;
		flex-direction: column;
		gap: 1rem;
		min-width: 0;
	}

	.register-view__col > * {
		border-radius: 16px;
		box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
		backdrop-filter: blur(5px);
		-webkit-backdrop-filter: blur(5px);
		border: 1px solid rgb(from var(--color-border) r g b / 0.6);
		padding: 0.5rem;
		background-color: var(--bg-glass);
	}

	.register-view__header {
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.register-view__form {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;

		h3 {
			margin: 0 0 0.5rem;
		}

		[class*='section__'] {
			margin: 0.25rem 0;
		}
	}
}
</style>
