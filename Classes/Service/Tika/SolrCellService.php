<?php
namespace ApacheSolrForTypo3\Tika\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\File;


/**
 * A Tika service implementation using a Solr server
 *
 * @package ApacheSolrForTypo3\Tika\Service
 */
class SolrCellService extends AbstractTikaService {

	/**
	 * Takes a file reference and extracts the text from it.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string
	 */
	public function extractText(File $file) {
		// TODO: Implement extractText() method.
	}

	/**
	 * Takes a file reference and extracts its meta data.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return array
	 */
	public function extractMetaDate(File $file) {
		// FIXME move connection building to EXT:solr
		// explicitly using "new" to bypass \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance() or
		// providing a Factory

		// EM might define a different connection than already in use by
		// Index Queue
		$solr = new \tx_solr_SolrService(
			$this->configuration['solrHost'],
			$this->configuration['solrPort'],
			$this->configuration['solrPath'],
			$this->configuration['solrScheme']
		);

		$localTempFilePath = $file->getForLocalProcessing(FALSE);

		$query = GeneralUtility::makeInstance(
			'ApacheSolrForTypo3\\Tika\\Service\\SolrCellQuery',
			$localTempFilePath
		);
		$query->setExtractOnly();
		$response = $solr->extract($query);

		$metaData = $this->solrResponseToArray($response[1]);

		$this->cleanupTempFile($localTempFilePath, $file);

		$this->log('Meta Data Extraction using Solr', array(
			'file'            => $file,
			'solr connection' => (array) $solr,
			'query'           => (array) $query,
			'response'        => $response,
			'meta data'       => $metaData
		));

		return $metaData;
	}

	/**
	 * Takes a file reference and detects its content's language.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string Language ISO code
	 */
	public function detectLanguageFromFile(File $file) {
		throw new UnsupportedOperationException(
			'The Tika Solr service does not support language detection',
			1423457153
		);
	}

	/**
	 * Takes a string as input and detects its language.
	 *
	 * @param string $input
	 * @return string Language ISO code
	 */
	public function detectLanguageFromString($input) {
		throw new UnsupportedOperationException(
			'The Tika Solr service does not support language detection',
			1423457153
		);
	}

	/**
	 * Turns the nested Solr response into the same format as produced by a
	 * local Tika jar call
	 *
	 * @param array $metaDataResponse The part of the Solr response containing the meta data
	 * @return array The cleaned meta data, matching the Tika jar call format
	 */
	protected function solrResponseToArray(array $metaDataResponse) {
		$cleanedData = array();

		foreach ($metaDataResponse as $dataName => $dataArray) {
			$cleanedData[$dataName] = $dataArray[0];
		}

		return $cleanedData;
	}
}