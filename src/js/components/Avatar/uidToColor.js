/*jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import md5 from 'md5'

/**
 * Originally taken from https://github.com/nextcloud/server/blob/master/core/js/placeholder.js
 */

const uidToColor = function(uid) {
	// Normalize hash
	let hash = uid.toLowerCase()

	// Already a md5 hash?
	if (hash.match(/^([0-9a-f]{4}-?){8}$/) === null) {
		hash = md5(hash)
	}

	hash = hash.replace(/[^0-9a-f]/g, '')

	function Color(r, g, b) {
		this.r = r
		this.g = g
		this.b = b
	}

	function stepCalc(steps, ends) {
		var step = new Array(3)
		step[0] = (ends[1].r - ends[0].r) / steps
		step[1] = (ends[1].g - ends[0].g) / steps
		step[2] = (ends[1].b - ends[0].b) / steps
		return step
	}

	function mixPalette(steps, color1, color2) {
		var palette = []
		palette.push(color1)
		var step = stepCalc(steps, [color1, color2])
		for (let i = 1; i < steps; i++) {
			var r = parseInt(color1.r + (step[0] * i))
			var g = parseInt(color1.g + (step[1] * i))
			var b = parseInt(color1.b + (step[2] * i))
			palette.push(new Color(r, g, b))
		}
		return palette
	}

	var red = new Color(182, 70, 157)
	var yellow = new Color(221, 203, 85)
	var blue = new Color(0, 130, 201) // Nextcloud blue
	// Number of steps to go from a color to another
	// 3 colors * 6 will result in 18 generated colors
	var steps = 6

	var palette1 = mixPalette(steps, red, yellow)
	var palette2 = mixPalette(steps, yellow, blue)
	var palette3 = mixPalette(steps, blue, red)

	var finalPalette = palette1.concat(palette2).concat(palette3)

	// Convert a string to an integer evenly
	function hashToInt(hash, maximum) {
		var finalInt = 0
		var result = []

		// Splitting evenly the string
		for (var i = 0; i < hash.length; i++) {
			// chars in md5 goes up to f, hex:16
			result.push(parseInt(hash.charAt(i), 16) % 16)
		}
		// Adds up all results
		for (var j in result) {
			finalInt += result[j]
		}
		// chars in md5 goes up to f, hex:16
		// make sure we're always using int in our operation
		return parseInt(parseInt(finalInt) % maximum)
	}
	return finalPalette[hashToInt(hash, steps * 3)]
}

export default uidToColor
