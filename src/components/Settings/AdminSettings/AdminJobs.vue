<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { Logger } from '../../../helpers/index.ts'
import { t } from '@nextcloud/l10n'

import NcButton, { ButtonVariant } from '@nextcloud/vue/components/NcButton'

import { AdminAPI } from '../../../Api/index.ts'

const autoreminder = {
	text: t('polls', 'Run autoreminder'),
	disabled: false,
}

const janitor = {
	text: t('polls', 'Run janitor'),
	disabled: false,
}

const notification = {
	text: t('polls', 'Run notification'),
	disabled: false,
}

/**
 * start AutoReminder job
 */
async function runAutoReminderJob() {
	try {
		AdminAPI.runAutoReminder()
		autoreminder.disabled = true
		autoreminder.text = t('polls', 'Autoreminder started')
	} catch (error) {
		autoreminder.text = t('polls', 'Autoreminder failed')
		Logger.error('Error on executing autoreminder job', { error })
	} finally {
		autoreminder.disabled = true
	}
}

/**
 * start Janitor job
 */
async function runJanitorJob() {
	try {
		AdminAPI.runJanitor()
		janitor.text = t('polls', 'Janitor started')
	} catch (error) {
		janitor.text = t('polls', 'Janitor failed')
		Logger.error('Error on executing janitor job', { error })
	} finally {
		janitor.disabled = true
	}
}

/**
 * start Notification job
 */
async function runNotificationJob() {
	try {
		AdminAPI.runNotification()
		notification.text = t('polls', 'Notification started')
	} catch (error) {
		notification.text = t('polls', 'Notification failed')
		Logger.error('Error on executing notification job', { error })
	} finally {
		notification.disabled = true
	}
}
</script>

<template>
	<div class="user_settings">
		<div class="job_hints">
			<p>
				{{
					t(
						'polls',
						'Please understand, that the jobs were defined as asynchronous jobs by intention.',
					)
				}}
				{{
					t(
						'polls',
						'Only use them, if it is absolutely neccessary (i.error. your cron does not work properly) or for testing.',
					)
				}}
				{{
					t(
						'polls',
						'Starting the jobs does not mean, that the rules for these actions are overridden.',
					)
				}}
			</p>
			<p>
				{{
					t(
						'polls',
						'Each job can only be run once. If you want to rerun them, you have to refresh the page.',
					)
				}}
				{{
					t(
						'polls',
						'If you want to see the result, please check the logs.',
					)
				}}
			</p>
		</div>
		<div class="job_buttons_section">
			<NcButton
				:variant="ButtonVariant.Primary"
				:aria-label="autoreminder.text"
				:disabled="autoreminder.disabled"
				@click="runAutoReminderJob()">
				{{ autoreminder.text }}
			</NcButton>

			<NcButton
				:variant="ButtonVariant.Primary"
				:aria-label="janitor.text"
				:disabled="janitor.disabled"
				@click="runJanitorJob()">
				{{ janitor.text }}
			</NcButton>

			<NcButton
				:variant="ButtonVariant.Primary"
				:aria-label="notification.text"
				:disabled="notification.disabled"
				@click="runNotificationJob()">
				{{ notification.text }}
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss">
.user_settings {
	.job_buttons_section {
		display: flex;
		flex-wrap: wrap;
		margin-top: 20px;
		gap: 12px;
	}
	.job_hints p {
		margin-bottom: 0.5em;
	}
}
</style>
