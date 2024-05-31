/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const files = import.meta.glob('./*.js', { eager: true })

const modules = {}
for (const key in files) {
	if (key !== './index.js') {
		modules[key.replace(/(\.\/|\.js)/g, '')] = files[key].default
	}
}

export default modules
