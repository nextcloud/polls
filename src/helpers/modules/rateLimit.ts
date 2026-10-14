/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosError } from '@nextcloud/axios'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

/**
 * Check, if the request was rejected by the server's rate limiter
 *
 * @param error error thrown by axios
 */
function isRateLimited(error: unknown): boolean {
	return (error as AxiosError)?.response?.status === 429
}

/**
 * Inform the user about the rate limitation
 */
function showRateLimitError(): void {
	showError(t('polls', 'Too many requests. Please wait a few minutes and try again.'))
}

export { isRateLimited, showRateLimitError }
