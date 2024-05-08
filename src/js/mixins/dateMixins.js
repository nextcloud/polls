/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export const dateUnits = {
	data() {
		return {
			dateUnits: [
				{ name: t('polls', 'Minute'), value: 'minute' },
				{ name: t('polls', 'Hour'), value: 'hour' },
				{ name: t('polls', 'Day'), value: 'day' },
				{ name: t('polls', 'Week'), value: 'week' },
				{ name: t('polls', 'Month'), value: 'month' },
				{ name: t('polls', 'Year'), value: 'year' },
			],
		}
	},
}
