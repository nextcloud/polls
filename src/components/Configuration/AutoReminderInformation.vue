<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
</script>

<template>
	<div class="auto-reminder-information">
		<h1>
			{{
				t('polls', 'The automatic reminder is sent to all shares via email:')
			}}
		</h1>
		<h2>{{ t('polls', 'For polls with expiration') }}</h2>
		<ul>
			<li>
				{{
					t(
						'polls',
						'48 hours before the expiration date, if the poll is created more than 5 days before the expiration.',
					)
				}}
			</li>
			<li>
				{{
					t(
						'polls',
						'36 hours before the expiration date, if the poll is created more than 2 and less than 5 days before the expiration.',
					)
				}}
			</li>
		</ul>
		<h2>{{ t('polls', 'For date polls without expiration') }}</h2>
		<ul>
			<li>
				{{
					t(
						'polls',
						'48 hours before the first date option, if the poll is created more than 5 days before the first date option.',
					)
				}}
			</li>
			<li>
				{{
					t(
						'polls',
						'36 hours before the first date option, if the poll is created more than 2 and less than 5 days before the first date option.',
					)
				}}
			</li>
		</ul>
		<h2>{{ t('polls', 'No reminder is sent') }}</h2>
		<ul>
			<li>{{ t('polls', 'For text polls without expiration.') }}</li>
			<li>
				{{
					t(
						'polls',
						'For polls created less than 2 days before the expiration.',
					)
				}}
			</li>
			<li>
				{{
					t(
						'polls',
						'If a share already got a reminder (no matter, if the expiration date got changed).',
					)
				}}
			</li>
			<li>{{ t('polls', 'If the poll is already closed.') }}</li>
		</ul>
	</div>
</template>

<style lang="scss">
.auto-reminder-information {
	padding: 8px;
	* {
		margin: revert;
		padding: revert;
		font-size: revert;
		text-decoration: revert;
		list-style: revert;
		opacity: revert;
		min-height: revert;
	}
}
</style>
