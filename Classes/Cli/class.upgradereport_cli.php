<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ingo Schmitt <is@marketing-factory.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once(PATH_t3lib . 'class.t3lib_cli.php');

/**
 * Class tx_upgradereport_cli
 */
class tx_upgradereport_cli extends t3lib_cli {

	/**
	 * @var Tx_Upgradereport_Domain_Repository_IssueRepository
	 */
	protected $issueRepository;

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function tx_upgradereport_cli() {
		// Running parent class constructor
		parent::t3lib_cli();

		$this->issueRepository = t3lib_div::makeInstance('Tx_Upgradereport_Domain_Repository_IssueRepository');

		// Adding options to help archive:
		$this->cli_options = array();
		$this->cli_options[] = array('report ', 'Detailed Report including extension, codeline and check');
		$this->cli_options[] = array('overview ', 'Just an overall overview');
		$this->cli_options[] = array('help ', 'Display this message');

		// Setting help texts:
		$this->cli_help['name'] = 'CLI Upgradereport Agent';
		$this->cli_help['synopsis'] = 'cli_dispatch.phpsh upgradereport {task}' . "\n";

		$this->cli_help['description'] = 'Executes the report of the upgradereport extension on CLI Basis';
		$this->cli_help['examples'] = './typo3/cli_dispatch.phpsh upgradereport report';
		$this->cli_help['author'] = 'Ingo Schmitt <is@marketing-factory.de>';
	}

	/**
	 * CLI engine
	 *
	 * @param array $argv command line arguments
	 * @return string
	 */
	public function cli_main($argv) {
		$task = ((string)$this->cli_args['_DEFAULT'][1]) ?: '';

		// Analysis type:
		switch ($task) {
			case 'overview':
				$this->cli_echo($this->overview());
				break;
			case 'report':
				$this->cli_echo($this->report());
				break;
			default:
				$this->cli_validateArgs();
				$this->cli_help();
				exit;
		}
	}

	/**
	 * Renders a Report of Extensions as ASCII
	 *
	 * @return string
	 */
	private function report() {
		$report = '';
		$registry = Tx_Upgradereport_Service_Check_Registry::getInstance();
		$issuesWithInspections = $this->issueRepository->findAllGroupedByExtensionAndInspection();
		foreach ($issuesWithInspections as $extensionKey => $inspections) {
			$count = 0;
			foreach ($inspections as $issues) {
				/** @var Tx_Upgradereport_Domain_Model_Issue $singleIssue */
				foreach ($issues as $singleIssue) {
					if ($count == 0) {
						// Render Extension Key
						$report .= '----------------------------------------------------------------' . "\n";
						$report .= '+ Extension : ' . sprintf('%-49s', $singleIssue->getExtension()) . "+\n";
						$report .= '----------------------------------------------------------------' . "\n";

					}
					$check = $registry->getActiveCheckByIdentifier($singleIssue->getInspection());
					$report .= $check->getResultAnalyzer()->getSolution($singleIssue) . "\n";
					$count ++;
				}
			}
			$report .= 'Total : ' . $count . ' issues in ' . $extensionKey;
			$report .= "\n";
			$report .= "\n";
		}
		return $report;
	}

	/**
	 * @return string	Report of Issues
	 */
	private function overview() {
		$report = '';
		$issues = 0;
		$registry = Tx_Upgradereport_Service_Check_Registry::getInstance();
		$checks = $registry->getActiveChecks();
		foreach ($checks as $singleCheck) {
			$processor = $singleCheck->getProcessor();
			$processor->executeCheck();
			foreach ($processor->getIssues() as $issue) {
				$this->issueRepository->add($issue);
			}
			$issues = $issues + count($processor->getIssues());
			$report .= 'Check:' . $singleCheck->getTitle() . ' has ' . count($processor->getIssues()) . ' issues ';
			$report .= "\n";
		}
		$report .= "\n" . 'Total Issues : ' . $issues . "\n";
		return $report;
	}

}

$cleanerObj = t3lib_div::makeInstance('tx_upgradereport_cli');
$cleanerObj->cli_main($_SERVER['argv']);

?>