/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Shared ref for the active route — kept in a separate module so that stores
// can import it without triggering the router creation (and its createWebHistory
// side-effects) in contexts like the Dashboard widget.
import { shallowRef } from 'vue'
import type { RouteLocationNormalized } from 'vue-router'

export const activeRoute = shallowRef<RouteLocationNormalized>({
	meta: {},
	params: {},
	query: {},
	hash: '',
	path: '/',
	name: null,
	matched: [],
	fullPath: '/',
	redirectedFrom: undefined,
} as unknown as RouteLocationNormalized)
