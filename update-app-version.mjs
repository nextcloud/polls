/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import fs from 'fs/promises'
import { parseString, Builder } from 'xml2js'

const xmlFilePath = './appinfo/info.xml'

/**
 * Asynchronously reads a file.
 * @function
 * @param {string} path - The path to the file.
 * @param {string} encoding - The character encoding of the file.
 * @return {Promise<string>} A promise that contains the file content as a string.
 */
const readFileAsync = (path, encoding) => fs.readFile(path, encoding)

/**
 * Asynchronously writes a file.
 * @function
 * @param {string} path - The path to the file.
 * @param {string} data - The data to be written to the file.
 * @param {string} encoding - The character encoding of the file.
 * @return {Promise<void>} A promise that is fulfilled after writing the file.
 */
const writeFileAsync = (path, data, encoding) => fs.writeFile(path, data, encoding)

/**
 * Syncronizes app version with package version
 * @function
 * @throws {Error} If an error occurs while updating the XML file.
 */
const updateXml = async () => {
	try {
		// Read the current version from package.json
		const packageJsonContent = await readFileAsync('./package.json', 'utf-8')
		const { version: newVersion } = JSON.parse(packageJsonContent)

		// Read the XML file
		const data = await readFileAsync(xmlFilePath, 'utf-8')

		// Parse the XML data
		const result = await parseXmlAsync(data)

		// Update the version in the XML (under info.version)
		result.info.version = newVersion

		// Build the updated XML
		const xmlBuilder = new Builder()
		const updatedXml = xmlBuilder.buildObject(result)

		// Write the updated XML back to the file
		await writeFileAsync(xmlFilePath, updatedXml, 'utf-8')

		console.info(`${xmlFilePath} successfully updated.`)
	} catch (error) {
		throw new Error(`Error updating ${xmlFilePath}: ${error.message}`)
	}
}

/**
 * Asynchronously parses XML data.
 * @function
 * @param {string} data - The XML data as a string.
 * @return {Promise<object>} A promise that contains the parsed XML as a JavaScript object.
 */
const parseXmlAsync = (data) =>
	new Promise((resolve, reject) => {
		parseString(data, (err, result) => {
			if (err) {
				reject(err)
			} else {
				resolve(result)
			}
		})
	})

// Perform the update
updateXml().catch((error) => console.error(error))
