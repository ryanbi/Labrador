<?php

##########################################################################
# Copyright 2013, Philip Ewels (phil.ewels@babraham.ac.uk)               #
#                                                                        #
# This file is part of Labrador.                                         #
#                                                                        #
# Labrador is free software: you can redistribute it and/or modify       #
# it under the terms of the GNU General Public License as published by   #
# the Free Software Foundation, either version 3 of the License, or      #
# (at your option) any later version.                                    #
#                                                                        #
# Labrador is distributed in the hope that it will be useful,            #
# but WITHOUT ANY WARRANTY; without even the implied warranty of         #
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          #
# GNU General Public License for more details.                           #
#                                                                        #
# You should have received a copy of the GNU General Public License      #
# along with Labrador.  If not, see <http://www.gnu.org/licenses/>.      #
##########################################################################

/*
Script to handle NCBI GEO lookups using GSE accessions
Provides a function if included, returns JSON if called directly
*/

require_once('../includes/start.php');

function get_geo_project ($acc, $editing = false) {

	global $dblink;
	// Get the first XML file with GEO ID accessions, using the supplied GEO accession
	// Only get the info we want for the Project
	// uses eSearch

	$results = array();
	$results['message'] = "";
	$results['status'] = 1;

	if(substr($acc, 0, 3) == 'GSM'){
		$results['status'] = 0;
		$results['message'] = "Accession is a GEO sample, not series. Needs to start GSE not GSM.";
		return $results;
	} else if(substr($acc, 0, 3) !== 'GSE'){
		$results['status'] = 0;
		$results['message'] = "Accession does not start with GSE. ";
		return $results;
	}


	$url_1 = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=gds&term='.$acc.'&usehistory=y';
	$xml_1 = simplexml_load_file($url_1);
	if($xml_1 === FALSE){
		$results['status'] = 0;
		$results['message'] = "Could not load GEO information. This usually means that the NCBI GEO API is down, try again later. API call URL: $url_1";
		return $results;
	}
	// Check if we have any Ids - if not, accession probably wrong
	if(!isset($xml_1->IdList->Id)){
		$results['status'] = 0;
		$results['message'] = "No datasets found with accession $acc";
		return $results;
	}
	$WebEnv = $xml_1->WebEnv;

	// Get the second XML file with GEO meta data and dataset information
	$url_2 = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=gds&query_key=1&WebEnv='.$WebEnv;
	$xml_2 = simplexml_load_file($url_2);
	if($xml_2 === FALSE){
		$results['status'] = 0;
		$results['message'] = "Could not load second NCBI GEO API call: $url_2";
		return $results;
	}
	// Check for an error
	if(isset($xml_2->ERROR)) {
		$results['status'] = 0;
		$results['message'] = "Second NCBI GEO API call returned an error: ".$xml_2->ERROR;
		return $results;
	}

	$firstDocSum = true;
	foreach($xml_2->children() as $DocSum){
		// All of what we want is found in the first DocSum node
		if($firstDocSum){
			$firstDocSum = false;
			foreach($DocSum->children() as $child) {
				switch ($child->attributes()->Name) {
					case 'title':
						$results['title'] = (string)$child;
						break;
					case 'summary':
						$results['description'] = (string)$child;
						break;
					case 'PubMedIds':
						$results['PMIDs'] = array();
						foreach ($child->Item as $pmid){
							$results['PMIDs'][] = (string)$pmid[0];
						}
						break;
				}
			}

		}
	}

	////////////////////
	// Ok, let's go hunting for a SRP accession using the SRA database instead...
	////////////////////
	$url_3 = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=sra&term='.$acc.'&usehistory=y';
	$xml_3 = simplexml_load_file($url_3);
	if($xml_3 !== FALSE){
		// Check if we have any Ids - if not, accession probably wrong
		if(isset($xml_3->IdList->Id)){
			$WebEnv = $xml_3->WebEnv;

			// Get the fourth XML file with SRA meta data and dataset information
			$url_4 = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=sra&query_key=1&WebEnv='.$WebEnv;
			$xml_4 = simplexml_load_file($url_4);
			if($xml_4 !== FALSE){
				// Check for an error
				if(!isset($xml_4->ERROR)) {
					$sra_xml_string = false;
					$firstDocSum = true;
					foreach($xml_4->children() as $DocSum){
						// All of what we want is found in the first DocSum node
						if($firstDocSum){
							$results['sra_accession'] = 'here_6';
							$firstDocSum = false;
							foreach($DocSum->children() as $child) {
								if($child->attributes()->Name == 'ExpXml') {
									$sra_xml_string = (string)$child;
								}
							}
						}
					}
					if($sra_xml_string){
						$pos = strpos($sra_xml_string, 'SRP');
						if($pos){
							$results['sra_accession'] = substr($sra_xml_string, $pos, 9);
						}
					}
				}
			}
		}
	}




	// Check to see if we already have this accession
	$sql = sprintf("SELECT `id`, `name` FROM `projects` WHERE `accession_geo` LIKE '%%%s%%'", mysqli_real_escape_string($dblink, $acc));
	$projects = mysqli_query($dblink, $sql);
	if(mysqli_num_rows($projects) > 0){
		$project = mysqli_fetch_array($projects);
		if($project['id'] != $editing){
			$results['message'] = '<strong>WARNING:</strong> There is already a project with this accession: <a href="project.php?id='.$project['id'].'">'.$project['name'].'</a>';
		}
	}


	return $results;
}

// Script is being called directly (ajax)
if(basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
	if(isset($_GET['acc'])){
		if(isset($_GET['editing'])){
			$results = get_geo_project ($_GET['acc'], $_GET['editing']);
		} else {
			$results = get_geo_project ($_GET['acc'], false);
		}
		echo json_encode($results, JSON_FORCE_OBJECT);
	} else {
		$results = array(
			'status' => 0,
			'message' => "No accession provided"
		);
		echo json_encode($results, JSON_FORCE_OBJECT);
	}
}


?>
