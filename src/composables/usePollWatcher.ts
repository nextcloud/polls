/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { watch, onBeforeUnmount } from 'vue'
import { useSessionStore } from '../stores/session'
import { usePollStore } from '../stores/poll'
import { generateUrl } from '@nextcloud/router'
// eslint-disable-next-line import/default
import PollWatcherWorker from '../workers/pollWatcher.worker?worker'
import { Logger } from '../helpers/index.ts'
import type { WatcherResponse } from './usePollWatcher.types'
import { forEach } from 'lodash'
import { usePollsStore } from '../stores/polls.ts'
import { useOptionsStore } from '../stores/options.ts'
import { useCommentsStore } from '../stores/comments.ts'

export const usePollWatcher = (interval = 30000) => {
	const sessionStore = useSessionStore()
	const pollStore = usePollStore()
	const pollsStore = usePollsStore()
	const votesStore = usePollStore()
	const optionsStore = useOptionsStore()
	const commentsStore = useCommentsStore()

	const baseUrl = generateUrl('apps/polls/')

	let worker: Worker | null = null

	const startWorker = (pollId: number | null | undefined, updateType: string) => {
		if (worker) {
			worker.terminate()
			worker = null
		}

		worker = new PollWatcherWorker()
		worker.postMessage({ pollId, updateType, interval, baseUrl, token: sessionStore.token, watcherId: sessionStore.watcher.id })

		worker.onmessage = (e) => {
			const { type, message, updates } = e.data

			switch (type) {
				case 'info':
					Logger.info(`[PollWatcher] ${message}`)
					break

				case 'debug':
					Logger.debug(`[PollWatcher] ${message}`)
					break

				case 'error':
				case 'fatal':
					Logger.error(`[PollWatcher] ${message}`)
					break

				case 'update':
					Logger.info(`[PollWatcher] ${message}`)
					if (Array.isArray(updates)) {
						handleWatcherUpdates(updates)
					}
					break

				default:
					Logger.warn('[PollWatcher] Unknown message type:', { type })
			}
		}
	}

	const stopWorker = () => {
		if (worker) {
			worker.terminate()
			worker = null
			Logger.info('[PollWatcher] Worker stopped.')
		}
	}

	const getTasksFromUpdates = (updates: WatcherResponse[], currentPollId: number): string[] => {
		const tasks = new Set<string>()

		for (const update of updates) {
			if (update.pollId === currentPollId) {
				tasks.add(update.table)
			} else if (update.table === 'polls') {
				if (update.pollId === currentPollId) {
					tasks.add('poll')
				}
				tasks.add('polls')
			}
		}

		return Array.from(tasks)
	}

	const handleWatcherUpdates = (updates: WatcherResponse[]) => {
		const tasks = getTasksFromUpdates(updates, pollStore.id)
		Logger.info('[PollWatcher] Updates received:', { updates })
		forEach(tasks, (task) => {
			switch (task) {
				case 'poll':
					pollStore.load()
					break
				case 'polls':
					pollsStore.load()
					break
				case 'votes':
					votesStore.load()
					optionsStore.load()
					break
				case 'options':
					pollStore.load()
					break
				case 'comments':
					commentsStore.load()
					break
			}
		})
	}

	watch(
		[() => pollStore.id, () => sessionStore.appSettings.updateType],
		([pollId, updateType]) => {
			startWorker(pollId, updateType)
		},
		{ immediate: true },
	)

	onBeforeUnmount(stopWorker)
}
