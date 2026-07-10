/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosResponse } from 'axios'
import type { Job, JobsList } from './api.types'

import { createCancelTokenHandler, httpInstance } from './HttpApi.js'

// eslint-disable-next-line prefer-const -- assigned below, after `adminJobs` is fully defined
let cancelTokenHandlerObject: ReturnType<typeof createCancelTokenHandler>

const adminJobs = {
	getJobsList(): Promise<AxiosResponse<{ jobs: JobsList }>> {
		return httpInstance.request({
			method: 'GET',
			url: 'administration/jobs',
			signal: cancelTokenHandlerObject[
				this.getJobsList.name
			].handleRequestCancellation().signal,
		})
	},

	runJob(job: Job): Promise<AxiosResponse<{ job: Job }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'administration/job/run',
			data: {
				job: job.className,
			},

			signal: cancelTokenHandlerObject[
				this.runJob.name
			].handleRequestCancellation().signal,
		})
	},
}

cancelTokenHandlerObject = createCancelTokenHandler(adminJobs)

export default adminJobs
