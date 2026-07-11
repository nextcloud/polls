<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import PublicFooter from '../components/Public/PublicFooter.vue'
import OptionPreview from '@/components/Options/OptionPreview.vue'
import PollDescription from '@/components/Poll/PollDescription.vue'
import PollInfoLine from '@/components/Poll/PollInfoLine.vue'
import PollTitle from '@/components/Poll/PollTitle.vue'
import RegistrationForm from '@/components/Public/RegistrationForm.vue'
import { InlineLink } from '../helpers/modules/InlineLink.ts'
import { useSessionStore } from '../stores/session.ts'

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
			<div class="guest-box">
				<PollTitle />
				<PollInfoLine />
			</div>

			<div class="guest-box">
				<RegistrationForm />
			</div>

			<div v-if="sessionStore.appSettings.useLogin" class="guest-box">
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
			<PollDescription class="guest-box" />
			<OptionPreview class="guest-box" />
		</div>
		<PublicFooter />
	</NcAppContent>
</template>

<style lang="scss">
#content-vue.app-polls {
	background-color: revert;
	backdrop-filter: revert;
}

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

	.guest-box {
		--color-text-maxcontrast: var(
			--color-text-maxcontrast-background-blur,
			var(--color-main-text)
		);
		color: var(--color-main-text);
		background-color: var(--color-main-background-blur);
		padding: calc(3 * var(--default-grid-baseline));
		border-radius: var(--border-radius-container);
		box-shadow: 0 0 10px var(--color-box-shadow);
		display: inline-block;
		-webkit-backdrop-filter: var(--filter-background-blur);
		backdrop-filter: var(--filter-background-blur);
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
