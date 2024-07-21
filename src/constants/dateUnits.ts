/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

export type DateUnit = {
	name: string
	value: string
}

export const dateUnits: DateUnit[] = [
	{ name: t('polls', 'Minute'), value: 'minute' },
	{ name: t('polls', 'Hour'), value: 'hour' },
	{ name: t('polls', 'Day'), value: 'day' },
	{ name: t('polls', 'Week'), value: 'week' },
	{ name: t('polls', 'Month'), value: 'month' },
	{ name: t('polls', 'Year'), value: 'year' },
]
