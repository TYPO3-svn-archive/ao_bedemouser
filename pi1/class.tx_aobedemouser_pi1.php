<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Andreas Otto (andreas@php4win.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
/** 
 * Plugin 'AO BE demo user' for the 'ao_bedemouser' extension.
 *
 * @author	Andreas Otto <andreas@php4win.de>
 */

require_once 'DB.php';
require_once(PATH_tslib.'class.tslib_pibase.php');

define ('T3_DB_USER',	TYPO3_db_username);
define ('T3_DB_PWD',	TYPO3_db_password);
define ('T3_DB_HOST',	TYPO3_db_host);
define ('T3_DB_NAME',	TYPO3_db);
define ('T3_DB_TYPE',	'mysql');
define ('T3_DB_DSN',	T3_DB_TYPE.'://'.T3_DB_USER.':'.T3_DB_PWD.'@'.T3_DB_HOST.'/'.T3_DB_NAME);

define('XCLASS', $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ao_bedemouser/pi1/class.tx_aobedemouser_pi1.php']);

class tx_aobedemouser_pi1 extends tslib_pibase {
	var $prefixId = 'tx_aobedemouser_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_aobedemouser_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'ao_bedemouser';	// The extension key.

	/**
	  * @return $content
	  * @param $content string
	  * @param $conf string
	  * @desc Main function decides what needs to be done.
	*/
	function main($content,$conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		define ('AO_DB_USER',	$this->conf['createBEdemoUser.']['dbuser']);
		define ('AO_DB_PWD',	$this->conf['createBEdemoUser.']['dbpassword']);
		define ('AO_DB_HOST',	$this->conf['createBEdemoUser.']['dbhost']);
		define ('AO_DB_NAME',	$this->conf['createBEdemoUser.']['dbname']);
		define ('AO_DB_TYPE',	'mysql');
		define ('AO_DB_DSN',	AO_DB_TYPE.'://'.AO_DB_USER.':'.AO_DB_PWD.'@'.AO_DB_HOST.'/'.AO_DB_NAME);
		define ('AOBEDEMOUSER_STORE_DEMOUSER_SQL', ''); // Could have been TS but I rather hardcode it here.
		define ('AOBEDEMOUSER_CREATE_DEMOUSER_SQL', ''); // Could have been TS but I rather hardcode it here.

		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);

		if (isset($this->piVars['submit_button'])){
			$content = $this->ao_displayResult();
		}else{
			$content = $this->ao_displayForm();
		}

		return $content;
	}

	/**
	  * @return		$content
	  * @param		$error_class string
	  * @desc		Function ao_displayForm loads the form and displays error messages in case of errors.
	*/
	function ao_displayForm($error_class='') {
		$content = '';

		$template = $this->cObj->getSubpart($this->templateCode, '###DISPLAY_FORM###');

		$markerArray = array();

		$markerArray['###FORM_ACTION###']				= $this->pi_getPageLink($GLOBALS['TSFE']->id);
		$markerArray['###INTRODUCTORY_MESSAGE###']		= $this->pi_getLL('introductory_message');
		$markerArray['###USERNAME_LABEL###']			= $this->pi_getLL('username');
		$markerArray['###USERNAME_FIELD_NAME###']		= $this->prefixId.'[DATA][username]';
		$markerArray['###EMAIL_LABEL###']				= $this->pi_getLL('email');
		$markerArray['###EMAIL_FIELD_NAME###']			= $this->prefixId.'[DATA][email]';
		$markerArray['###PRIVACY_LABEL###']				= $this->pi_getLL('privacy');
		$markerArray['###PRIVACY_MESSAGE###']			= $this->pi_getLL('privacy_message');
		$markerArray['###PRIVACY_FIELD_NAME###']		= $this->prefixId.'[DATA][privacy]';
		$markerArray['###SUBMIT_BUTTON_LABEL###']		= $this->pi_getLL('submit_button_label');
		$markerArray['###SUBMIT_BUTTON_FIELD_NAME###']	= $this->prefixId.'[submit_button]';

		if(!empty($this->piVars['DATA']['username'])){
			$markerArray['###USERNAME_FIELD_VALUE###'] = $this->piVars['DATA']['username'];
		}else{
			$markerArray['###USERNAME_FIELD_VALUE###'] = '';
		}

		if(!empty($this->piVars['DATA']['email'])){
			$markerArray['###EMAIL_FIELD_VALUE###'] = $this->piVars['DATA']['email'];
		}else{
			$markerArray['###EMAIL_FIELD_VALUE###'] = '';
		}

		if($this->piVars['DATA']['privacy'] == 1){
			$markerArray['###PRIVACY_FIELD_CHECKED###'] = ' checked';
		}else{
			$markerArray['###PRIVACY_FIELD_CHECKED###'] = '';
		}

		if ($error_class['username']) {
			$markerArray['###USERNAME_ERROR###'] = $this->pi_getLL('username_error');
			$markerArray['###USERNAME_FIELD_VALUE###'] = '';
		}else{
			$markerArray['###USERNAME_ERROR###'] = '';
		}
		if ($error_class['email']) {
			$markerArray['###EMAIL_ERROR###'] = $this->pi_getLL('email_error');
			$markerArray['###EMAIL_FIELD_VALUE###'] = '';
		}else{
			$markerArray['###EMAIL_ERROR###'] = '';
		}

		$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray);

		//t3lib_div::debug($this->conf);

		return $content;
	}

	/**
	  * @return $content
	  * @desc Function ao_displayResult takes the data from ao_displayForm and validates it. On success ao_createDemoAccount is called. On error ao_displayForm is called with $error_class as optional parameter.
	*/
	function ao_displayResult() {
		$content = '';

		$error = FALSE;
		$this->piVars['DATA']['password'] = $this->ao_generatePassword();

		if(empty($this->piVars['DATA']['username'])){
			$error = TRUE;
			$error_class['username'] = TRUE;
		}

		if(ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.'@'.'[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.'[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$',$this->piVars['DATA']['email']) == 0){
			$error = TRUE;
			$error_class['email'] = TRUE;
		}

		if ($error == FALSE) {
			if($this->ao_createDemoAccount() == FALSE){
				$error = TRUE;
				$error_class['username'] = TRUE;
			}
		}


		if ($error == FALSE) {
			$template = $this->cObj->getSubpart($this->templateCode, '###DISPLAY_RESULT###');

			$markerArray = array();

			$markerArray['###RESULT_MESSAGE###'] = $this->pi_getLL('result_message');

			$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray);

			if($this->piVars['DATA']['privacy'] == 0){
				$privacy_note = $this->pi_getLL('result_email_privacy_0');
			}else{
				$privacy_note = $this->pi_getLL('result_email_privacy_1');
			}

			$message = sprintf($this->pi_getLL('result_email'),
											$this->piVars['DATA']['username'],
											$this->piVars['DATA']['password'],
											$this->conf['sendMail.']['demourl'],
											$privacy_note,
											$this->conf['sendMail.']['sendername']
											);

			$message = t3lib_div::breakTextForEmail($message, "\n", 72);
			$to = $this->piVars['DATA']['email'];
			$from = $this->conf['sendMail.']['sendername'].' <'.$this->conf['sendMail.']['senderemail'].'>';
			$subject = $this->pi_getLL('result_subject');

			@mail($to,$subject,$message,"From: $from\nx-mailer: php4win:mail 1.0.0\nreply-to: $from\nErrors-To: $from\n");

		}else if ($error == TRUE){
			$content = $this->ao_displayForm($error_class);
		}

		return $content;
	}

	/**
	  * @return $content
	  * @desc Function createDemoAccount checks for duplicate demo be users. If none are found the demo account will be created. If a duplicate record is found createDemoAccount returns FALSE.
	*/
	function ao_createDemoAccount() {
		$retVal = TRUE;

		$db = DB::connect(AO_DB_DSN);
		if (DB::isError($db)) {
			die($db->getMessage());
		}else{
			$query = sprintf("SELECT username FROM %s WHERE username = '%s'",
							'be_users',
							$this->piVars['DATA']['username']
							);

			$res = $db->query($query);
			if ($res->numRows() > 0){
				$retVal = FALSE;
			}else{
				$sql = "INSERT INTO %s (uid, pid, tstamp, username, password, admin, usergroup, disable, starttime, endtime, lang, email, db_mountpoints, options, crdate, cruser_id, realName, userMods, uc, file_mountpoints, fileoper_perms, lockToDomain, deleted, TSconfig, lastlogin, createdByAction, usergroup_cached_list) VALUES ('', 0, unix_timestamp(), '%s', '%s', 0, '%s', 0, 0, 0, '%s', '%s', '', 3, unix_timestamp(), 0, '%s', '', '', '', 7, '', 0, '', 0, 0, '')";
				$query = sprintf($sql,
								'be_users',
								$this->piVars['DATA']['username'],
								md5($this->piVars['DATA']['password']),
								$this->conf['createBEdemoUser.']['begroup'],
								$GLOBALS['TSFE']->config['config']['language'],
								$this->piVars['DATA']['email'],
								$this->piVars['DATA']['username']
								);
				$res = $db->query($query);
			}
		}

		if ($retVal == TRUE){
			$db = DB::connect(T3_DB_DSN);
			if (DB::isError($db)) {
				die($db->getMessage());
			}else{
				$query = sprintf("INSERT INTO %s (uid, pid, tstamp, crdate, cruser_id, sorting, deleted, hidden, username, email, privacy) VALUES ('', %s, unix_timestamp(), unix_timestamp(), 1, 256, 0, 0, '%s', '%s', %s);",
								'tx_aobedemouser_accounts',
								$this->conf['createBEdemoUser.']['recordstorage'],
								$this->piVars['DATA']['username'],
								$this->piVars['DATA']['email'],
								$this->piVars['DATA']['privacy']);
				$res = $db->query($query);
			}
		}

		return $retVal;
	}

	/**
  	  * @return $securePassword
	  * @desc Function ao_generatePassword generate AOL style passwords.
	*/
	function ao_generatePassword() {
		define ('CONFIG_NUMBER_OF_WORDS', 2);
		define ('CONFIG_MAX_NUMBER_OF_DIGIT_BETWEEN_WORDS', 2);
		define ('CONFIG_DIGITS_AFTER_LAST_WORD', FALSE);

		mt_srand ((float) microtime() * 1000000);
		$securePassword = '';

		$safeEnglishWords = $this->ao_getWordlist();
		$count = count($safeEnglishWords);

		for ($i=0; $i < CONFIG_NUMBER_OF_WORDS; $i++) {
			if ($i > 0) {
				$securePassword .= ucfirst ($safeEnglishWords[mt_rand(0,$count)]);
			}else{
				$securePassword .= $safeEnglishWords[mt_rand(0,$count)];
			}
			if (CONFIG_DIGITS_AFTER_LAST_WORD or $i + 1 != CONFIG_NUMBER_OF_WORDS) $securePassword .= mt_rand(0,pow(10,CONFIG_MAX_NUMBER_OF_DIGIT_BETWEEN_WORDS) -1);
		}

		return $securePassword;
	}

	/**
	  * @return $wordList
	  * @desc Function ao_getWordlist is a helper function to ao_createPassword.
	*/
	function ao_getWordlist() {
		// slightly modified data from http://www.rick.harrison.net/annex/specialeng.txt - many thanks Rick Harrison!
		$wordList = Array( 'a', 'able', 'about', 'above', 'accept', 'accident', 'accuse', 'across', 'act',
		'activist', 'actor', 'add', 'administration', 'admit', 'advise', 'affect', 'afraid', 'after', 'again',
		'against', 'age', 'agency', 'aggression', 'ago', 'agree', 'agriculture', 'aid', 'aim', 'air',
		'airplane', 'airport', 'alive', 'all', 'ally', 'almost', 'alone', 'along', 'already', 'also',
		'although', 'always', 'ambassador', 'amend', 'ammunition', 'among', 'amount', 'an', 'anarchy',
		'ancient', 'and', 'anger', 'animal', 'anniversary', 'announce', 'another', 'answer', 'any',
		'apologize', 'appeal', 'appear', 'appoint', 'approve', 'area', 'argue', 'arms', 'army', 'around',
		'arrest', 'arrive', 'art', 'artillery', 'as', 'ash', 'ask', 'assist', 'astronaut', 'asylum', 'at',
		'atmosphere', 'atom', 'attack', 'attempt', 'attend', 'automobile', 'autumn', 'awake', 'award', 'away',
		'back', 'bad', 'balance', 'ball', 'balloon', 'ballot', 'ban', 'bank', 'bar', 'base', 'battle', 'be',
		'beach', 'beat', 'beauty', 'because', 'become', 'bed', 'beg', 'begin', 'behind', 'believe', 'bell',
		'belong', 'below', 'best', 'betray', 'better', 'between', 'big', 'bill', 'bird', 'bite', 'bitter',
		'black', 'blame', 'blanket', 'bleed', 'blind', 'block', 'blood', 'blow', 'blue', 'boat', 'body',
		'boil', 'bomb', 'bone', 'book', 'border', 'born', 'borrow', 'both', 'bottle', 'bottom', 'box',
		'boy', 'brain', 'brave', 'bread', 'break', 'breathe', 'bridge', 'brief', 'bright', 'bring',
		'broadcast', 'brother', 'brown', 'build', 'bullet', 'burn', 'burst', 'bury', 'bus', 'business',
		'busy', 'but', 'buy', 'by', 'cabinet', 'call', 'calm', 'camera', 'campaign', 'can', 'cancel',
		'cancer', 'candidate', 'cannon', 'capital', 'capture', 'car', 'care', 'careful', 'carry', 'case',
		'cat', 'catch', 'cattle', 'cause', 'ceasefire', 'celebrate', 'cell', 'center', 'century',
		'ceremony', 'chairman', 'champion', 'chance', 'change', 'charge', 'chase', 'cheat', 'check',
		'cheer', 'chemicals', 'chieg', 'child', 'choose', 'church', 'circle', 'citizen', 'city',
		'civil', 'civilian', 'clash', 'clean', 'clear', 'clergy', 'climb', 'clock', 'close', 'cloth',
		'clothes', 'cloud', 'coal', 'coalition', 'coast', 'coffee', 'cold', 'collect', 'colony', 'color',
		'come', 'comedy', 'command', 'comment', 'committee', 'common', 'communicate', 'company', 'compete',
		'complete', 'compromise', 'computer', 'concern', 'condemn', 'condition', 'conference', 'confirm',
		'conflict', 'congratulate', 'congress', 'connect', 'conservative', 'consider', 'contain',
		'continent', 'continue', 'control', 'convention', 'cook', 'cool', 'cooperate', 'copy', 'correct',
		'cost', 'costitution', 'cotton', 'count', 'country', 'court', 'cover', 'cow',
		'coward', 'crash', 'create', 'creature', 'credit', 'crew', 'crime', 'criminal', 'crisis',
		'criticize', 'crops', 'cross', 'crowd', 'cruel', 'crush', 'cry', 'culture', 'cure', 'current',
		'custom', 'cut', 'dam', 'damage', 'dance', 'danger', 'dark', 'date', 'daughter', 'day', 'dead',
		'deaf', 'deal', 'debate', 'decide', 'declare', 'deep', 'defeat', 'defend', 'deficit', 'degree',
		'delay', 'delegate', 'demand', 'democracy', 'demonstrate', 'denounce', 'deny', 'depend', 'deplore',
		'deploy', 'describe', 'desert', 'design', 'desire', 'destroy', 'details', 'develop', 'device', 'dictator', 'die',
		'different', 'difficult', 'dig', 'dinner', 'diplomat', 'direct', 'direction', 'dirty', 'disappear', 'disarm', 'discover',
		'discuss', 'disease', 'dismiss', 'dispute', 'dissident', 'distance', 'distant', 'dive', 'divide', 'do', 'doctor', 'document',
		'dollar', 'door', 'down', 'draft', 'dream', 'drink', 'drive', 'drown', 'drugs', 'dry', 'during', 'dust', 'duty', 'each',
		'early', 'earn', 'earth', 'earthquake', 'ease', 'east', 'easy', 'eat', 'economy', 'edge', 'educate', 'effect', 'effort',
		'egg', 'either', 'elect', 'electricity', 'electron', 'element', 'embassy', 'emergency', 'emotion', 'employ', 'empty', 'end',
		'enemy', 'energy', 'enforce', 'engine', 'engineer', 'enjoy', 'enough', 'enter', 'eqipment', 'equal', 'escape', 'especially',
		'establish', 'even', 'event', 'ever', 'every', 'evidence', 'evil', 'evironment', 'exact', 'examine', 'example', 'excellent',
		'except', 'exchange', 'excite', 'excuse', 'execute', 'exile', 'exist', 'expand', 'expect', 'expel', 'experiment', 'expert',
		'explain', 'explode', 'explore', 'export', 'express', 'extend', 'extra', 'extreme', 'face', 'fact', 'factory', 'fail',
		'fair', 'fall', 'family', 'famous', 'fanatic', 'far', 'farm', 'fast', 'fat', 'father', 'fear', 'feast', 'federal', 'feed',
		'feel', 'female', 'fertile', 'few', 'field', 'fierce', 'fight', 'fill', 'film', 'final', 'find', 'fine', 'finish', 'fire',
		'firm', 'first', 'fish', 'fix', 'flag', 'flat', 'flee', 'float', 'flood', 'floor', 'flow', 'flower', 'fluid', 'fly', 'fog',
		'follow', 'food', 'fool', 'foot', 'for', 'force', 'foreign', 'forget', 'forgive', 'form', 'former', 'forward', 'free',
		'freeze', 'fresh', 'friend', 'frighten', 'from', 'front', 'fruit', 'fuel', 'funeral', 'furious', 'future', 'gain', 'game',
		'gas', 'gather', 'general', 'gentle', 'get', 'gift', 'girl', 'give', 'glass', 'go', 'goal', 'God', 'gold', 'good',
		'good-bye', 'goods', 'govern', 'government', 'grain', 'grandfather', 'grandmother', 'grass', 'gray', 'great', 'green',
		'grind', 'ground', 'group', 'grow', 'guarantee', 'guard', 'guerilla', 'guide', 'guilty', 'gun', 'hair', 'half', 'halt',
		'hang', 'happen', 'happy', 'harbor', 'hard', 'harm', 'hat', 'hate', 'he', 'head', 'headquarters', 'health', 'hear', 'heart',
		'heat', 'heavy', 'helicopter', 'help', 'here', 'hero', 'hide', 'high', 'hijack', 'hill', 'history', 'hit', 'hold', 'hole',
		'holiday', 'holy', 'home', 'honest', 'honor', 'hope', 'horrible', 'horse', 'hospital', 'hostage', 'hostile', 'hostilities',
		'hot', 'hotel', 'hour', 'house', 'how', 'however', 'huge', 'human', 'humor', 'hunger', 'hunt', 'hurry', 'hurt', 'husband',
		'I', 'ice', 'idea', 'if', 'illegal', 'imagine', 'immediate', 'import', 'important', 'improve', 'in', 'incident', 'incite',
		'include', 'increase', 'independent', 'industry', 'inflation', 'influence', 'inform', 'injure', 'innocent', 'insane',
		'insect', 'inspect', 'instead', 'instrument', 'insult', 'intelligent', 'intense', 'interest', 'interfere', 'international',
		'intervene', 'invade', 'invent', 'invest', 'investigate', 'invite', 'involve', 'iron', 'island', 'issue', 'it', 'jail',
		'jewel', 'job', 'join', 'joint', 'joke', 'judge', 'jump', 'jungle', 'jury', 'just', 'keep', 'kick', 'kidnap', 'kill', 'kind',
		'kiss', 'knife', 'know', 'labor', 'laboratory', 'lack', 'lake', 'land', 'language', 'large', 'last', 'late', 'laugh',
		'launch', 'law', 'lead', 'leak', 'learn', 'leave', 'left', 'legal', 'lend', 'less', 'let', 'letter', 'level', 'liberal',
		'lie', 'life', 'light', 'lightning', 'like', 'limit', 'line', 'link', 'liquid', 'list', 'listen', 'little', 'live', 'load',
		'local', 'lonely', 'long', 'look', 'lose', 'loud', 'love', 'low', 'loyal', 'luck', 'machine', 'mad', 'mail', 'main', 'major',
		'majority', 'make', 'male', 'man', 'map', 'march', 'mark', 'marker', 'mass', 'material', 'may', 'mayor', 'mean', 'measure',
		'meat', 'medicine', 'meet', 'melt', 'member', 'memorial', 'memory', 'mercenary', 'mercy', 'message', 'metal', 'method',
		'microscope', 'middle', 'militant', 'military', 'milk', 'mind', 'mine', 'mineral', 'minister', 'minor', 'minority', 'minute',
		'miss', 'missile', 'missing', 'mistake', 'mix', 'mob', 'moderate', 'modern', 'money', 'month', 'moon', 'more', 'morning',
		'most', 'mother', 'motion', 'mountain', 'mourn', 'move', 'much', 'murder', 'music', 'must', 'mystery', 'naked', 'name',
		'nation', 'navy', 'near', 'necessary', 'need', 'negotiate', 'neither', 'nerve', 'neutral', 'never', 'new', 'news', 'next',
		'nice', 'night', 'no', 'noise', 'nominate', 'noon', 'normal', 'north', 'not', 'note', 'nothing', 'now', 'nowhere', 'nuclear',
		'number', 'nurse', 'obey', 'object', 'observe', 'occupy', 'ocean', 'of', 'off', 'offensive', 'offer', 'officer', 'official',
		'often', 'oil', 'old', 'on', 'once', 'only', 'open', 'operate', 'opinion', 'oppose', 'opposite', 'oppress', 'or', 'orbit',
		'orchestra', 'order', 'organize', 'other', 'oust', 'out', 'over', 'overthrow', 'owe', 'own', 'pain', 'paint', 'palace',
		'pamphlet', 'pan', 'paper', 'parachute', 'parade', 'pardon', 'parent', 'parliament', 'part', 'party', 'pass', 'passenger',
		'passport', 'past', 'path', 'pay', 'peace', 'people', 'percent', 'perfect', 'perhaps', 'period', 'permanent', 'permit',
		'person', 'physics', 'piano', 'picture', 'piece', 'pilot', 'pipe', 'pirate', 'place', 'planet', 'plant', 'play', 'please',
		'plenty', 'plot', 'poem', 'point', 'poison', 'police', 'policy', 'politics', 'pollute', 'poor', 'popular', 'population',
		'port', 'position', 'possess', 'possible', 'postpone', 'pour', 'power', 'praise', 'pray', 'pregnant', 'prepare', 'present',
		'president', 'press', 'pressure', 'prevent', 'price', 'priest', 'prison', 'private', 'prize', 'probably', 'problem',
		'produce', 'professor', 'program', 'progress', 'project', 'promise', 'propaganda', 'property', 'propose', 'protect',
		'protest', 'proud', 'prove', 'provide', 'public', 'publication', 'publish', 'pull', 'pump', 'punish', 'purchase', 'pure',
		'purpose', 'push', 'put', 'question', 'quick', 'quiet', 'rabbi', 'race', 'radar', 'radiation', 'radio', 'raid', 'railroad',
		'rain', 'raise', 'rapid', 'rare', 'rate', 'reach', 'read', 'ready', 'real', 'realistic', 'reason', 'reasonable', 'rebel',
		'receive', 'recent', 'recession', 'recognize', 'record', 'red', 'reduce', 'reform', 'refugee', 'refuse', 'regret',
		'relations', 'release', 'religion', 'remain', 'remember', 'remove', 'repair', 'repeat', 'report', 'repress', 'request',
		'rescue', 'resign', 'resolution', 'responsible', 'rest', 'restrain', 'restrict', 'result', 'retire', 'return', 'revolt',
		'rice', 'rich', 'ride', 'right', 'riot', 'rise', 'river', 'road', 'rob', 'rock', 'rocket', 'roll', 'room', 'root', 'rope',
		'rough', 'round', 'rub', 'rubber', 'ruin', 'rule', 'run', 'sabotage', 'sacrifice', 'sad', 'safe', 'sail', 'salt', 'same',
		'satellite', 'satisfy', 'save', 'say', 'school', 'science', 'scream', 'sea', 'search', 'season', 'seat', 'second', 'secret',
		'security', 'see', 'seek', 'seem', 'seize', 'self', 'sell', 'senate', 'send', 'sense', 'sentence', 'separate', 'series',
		'serious', 'sermon', 'serve', 'set', 'settle', 'several', 'severe', 'sex', 'shake', 'shape', 'share', 'sharp', 'she', 'shell',
		'shine', 'ship', 'shock', 'shoe', 'shoot', 'short',
		'should', 'shout', 'show', 'shrink', 'shut', 'sick', 'side',
		'sign', 'signal', 'silence', 'silver', 'similar', 'simple', 'since', 'sing', 'sink', 'sister', 'sit', 'situation', 'size',
		'skeleton', 'skill', 'skull', 'sky', 'slave', 'sleep', 'slide', 'slow', 'small', 'smash', 'smell', 'smile', 'smoke',
		'smooth', 'snow', 'so', 'social', 'soft', 'soldier', 'solid', 'solve', 'some', 'son', 'soon', 'sorry', 'sort', 'sound',
		'south', 'space', 'speak', 'special', 'speed', 'spend', 'spill', 'spilt', 'spirit', 'split', 'sports', 'spread', 'spring',
		'spy', 'stab', 'stamp', 'stand', 'star', 'start', 'starve', 'state', 'station', 'statue', 'stay', 'steal', 'steam', 'steel',
		'step', 'stick', 'still', 'stomach', 'stone', 'stop', 'store', 'storm', 'story', 'stove', 'straight', 'strange', 'street',
		'stretch', 'strike', 'strong', 'struggle', 'stubborn', 'study', 'stupid', 'submarine', 'substance', 'substitute',
		'subversion', 'succeed', 'such', 'sudden', 'suffer', 'sugar', 'summer', 'sun', 'supervise', 'supply', 'support', 'suppose',
		'suppress', 'sure', 'surplus', 'surprise', 'surrender', 'surround', 'survive', 'suspect', 'suspend', 'swallow', 'swear',
		'sweet', 'swim', 'sympathy', 'system', 'take', 'talk', 'tall', 'tank', 'target', 'task', 'taste', 'tax', 'teach', 'team',
		'tear', 'tears', 'technical', 'telephone', 'telescope', 'television', 'tell', 'temperature', 'temporary', 'tense', 'term',
		'terrible', 'territory', 'terror', 'test', 'textiles', 'than', 'thank', 'that', 'the', 'theater', 'then', 'there', 'thick',
		'thin', 'thing', 'think', 'third', 'this', 'threaten', 'through', 'throw', 'tie', 'time', 'tired', 'tissue', 'to', 'today',
		'together', 'tomorrow', 'tonight', 'too', 'tool', 'top', 'torture', 'touch', 'toward', 'town', 'trade', 'tradition',
		'tragic', 'train', 'traitor', 'transport', 'trap', 'travel', 'treason', 'treasure', 'treat', 'treaty', 'tree', 'trial',
		'tribe', 'trick', 'trip', 'troops', 'trouble', 'truce', 'truck', 'trust', 'try', 'turn', 'under', 'understand', 'unite',
		'universe', 'university', 'unless', 'until', 'up', 'urge', 'urgent', 'use', 'usual', 'valley', 'value', 'vehicle', 'version',
		'veto', 'vicious', 'victim', 'victory', 'village', 'violate', 'violence', 'violin', 'virus', 'visit', 'voice', 'volcano',
		'vote', 'voyage', 'wages', 'wait', 'walk', 'wall', 'want', 'war', 'warm', 'warn', 'wash', 'waste', 'watch', 'water', 'wave',
		'way', 'weak', 'wealth', 'weapon', 'wear', 'weather', 'weigh', 'welcome', 'well', 'west', 'wet', 'what', 'wheat', 'wheel',
		'when', 'where', 'which', 'while', 'white', 'who', 'why', 'wide', 'wife', 'wild', 'will', 'willing', 'win', 'wind', 'window',
		'wire', 'wise', 'wish', 'with', 'withdraw', 'without', 'woman', 'wonder', 'wood', 'woods', 'word', 'work', 'world', 'worry',
		'worse', 'wound', 'wreck', 'write', 'wrong', 'year', 'yellow', 'yes', 'yesterday', 'yet', 'you', 'young');
		return $wordList;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ao_bedemouser/pi1/class.tx_aobedemouser_pi1.php'])	{
	include_once(XCLASS);
}
?>
