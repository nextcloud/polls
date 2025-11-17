<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { Logger } from '../../../helpers/modules/logger'
import { t } from '@nextcloud/l10n'

import { AdminAPI } from '../../../Api'
import { onMounted, ref } from 'vue'
import type { Job, JobsList } from '../../../Api/modules/api.types'
import { NcActionButton, NcListItem } from '@nextcloud/vue'
import { DateTime } from 'luxon'
import PlayIcon from 'vue-material-design-icons/Play.vue'
import NotSupportedIcon from 'vue-material-design-icons/MinusCircleOutline.vue'
import JobExecutedIcon from 'vue-material-design-icons/CheckCircleOutline.vue'

const jobsList = ref<JobsList>({})
const executedJobs = ref(new Set<string>())

const jobHintsOne: Array<string> = [
	t(
		'polls',
		'Please understand, that the jobs were defined as asynchronous jobs by intention.',
	),
	t(
		'polls',
		'Starting the jobs does not mean, that the rules for these actions are overridden.',
	),
]

const jobHintsTwo: Array<string> = [
	t(
		'polls',
		'Each job can only be run once. If you want to rerun them, you have to refresh the page.',
	),
]

const jobsDescription: Record<
	string,
	{
		name: string
		description: string
	}
> = {
	AutoReminderCron: {
		name: 'Autoreminder',
		description: t('polls', 'Sends out pending automatic reminders.'),
	},
	JanitorCron: {
		name: 'Janitor',
		description: t(
			'polls',
			'Cleans up old or irrelevant data from the system to maintain optimal performance and organization.',
		),
	},
	NotificationCron: {
		name: 'Notification',
		description: t(
			'polls',
			'Sends out pending notifications about poll updates.',
		),
	},
}

function getDescription(job: Job): string {
	if (job.name in jobsDescription) {
		return jobsDescription[job.name].description
	}
	return t('polls', 'Description is missing.')
}

function getLastRun(job: Job): { text: string; title: string } {
	switch (job.lastRun) {
		case -1:
			return {
				text: t('polls', 'Execution failed'),
				title: t(
					'polls',
					'The last execution of this job has failed. Please check the logs for more details.',
				),
			}
		case undefined:
		case 0:
		case null:
			return {
				text: t('polls', 'Not run yet'),
				title: t('polls', 'This job has never been run yet.'),
			}
		default:
			return {
				text: DateTime.fromSeconds(job.lastRun).toRelative() as string,
				title: DateTime.fromSeconds(job.lastRun).toLocaleString(
					DateTime.DATETIME_SHORT,
				),
			}
	}
}

async function runJob(job: Job) {
	try {
		executedJobs.value.add(job.name)
		const response = await AdminAPI.runJob(job)
		const executedJob = response.data.job
		jobsList.value[executedJob.name] = executedJob
	} catch (error) {
		jobsList.value[job.name].lastRun = -1
		Logger.error('Error on executing job', { error, job })
	}
}
async function loadJobs() {
	try {
		const response = await AdminAPI.getJobsList()
		jobsList.value = response.data.jobs
	} catch (error) {
		jobsList.value = {}
		Logger.error('Error on fetching jobs list', { error })
	}
}
/**
 * fetch the jobs list on component mount
 */
onMounted(() => {
	loadJobs()
})
</script>

<template>
	<div class="user_settings">
		<div class="job_hints">
			<p>
				{{ jobHintsOne.join(' ') }}
			</p>
			<p>
				{{ jobHintsTwo.join(' ') }}
			</p>
		</div>
		<ul class="jobs_list">
			<NcListItem
				v-for="job in jobsList"
				:key="job.name"
				:name="job.name"
				:details="getLastRun(job).text"
				:title="getLastRun(job).title"
				:active="!executedJobs.has(job.name) && job.manuallyRunnable"
				force-display-actions>
				<template #subname>
					{{ getDescription(job) }}
				</template>
				<template #actions>
					<NcActionButton
						close-after-click
						:disabled="
							executedJobs.has(job.name) || !job.manuallyRunnable
						"
						:name="t('polls', 'Request Execution')"
						:aria-label="t('polls', 'Request job execution')"
						@click="runJob(job)">
						<template #icon>
							<JobExecutedIcon
								v-if="executedJobs.has(job.name)"
								:title="
									t('polls', '{jobName} has been executed.', {
										jobName: job.name,
									})
								" />
							<PlayIcon
								v-else-if="job.manuallyRunnable"
								:title="
									t('polls', 'Start {jobName} manually.', {
										jobName: job.name,
									})
								" />
							<NotSupportedIcon
								v-else
								:title="
									t(
										'polls',
										'{jobName} does not support manually run.',
										{ jobName: job.name },
									)
								" />
						</template>
					</NcActionButton>
				</template>
			</NcListItem>
		</ul>
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
