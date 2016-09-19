<?php

/**
 * Class PlayFairAlgorithm
 * Used to easily decrypt and encrypt messages
 */
class PlayFairAlgorithm {
	const ROWS = 5;
	const COLUMNS = 5;
	private $key = '';
	private $key_clean = array();
	private $matrix = array(self::ROWS);

	static $alphabet = array('a' => 1, 'b' => '1', 'c' => 1, 'd' => 1, 'e' => 1, 'f' => 1, 'g' => 1, 'h' => 1, 'i' => 1,
	                         'k' => 1, 'l' => 1, 'm' => 1, 'n' => 1, 'o' => 1, 'p' => 1, 'q' => 1, 'r' => 1, 's' => 1,
	                         't' => 1, 'u' => 1, 'v' => 1, 'w' => 1, 'x' => 1, 'y' => 1, 'z' => 1);

	public function __construct($key) {
		$key = str_replace('j', 'i', $key);
		$this->key = (String)$key;

		self::initializeMatrix();
	}

	private function initializeMatrix() {
		$this->prefillMatrix();

		$current_position = $this->saveKeyInMatrix();
		$i = $current_position->i;
		$j = $current_position->j;

		// add in order the letters of the alphabet
		foreach (self::$alphabet as $letter => $value) {

			$this->matrix[$i][$j] = $letter;
			$j++;

			// if end of row, continue onto next row
			if ($j == self::COLUMNS) {
				$i++;
				$j = 0;
			}

			// if end of matrix, we are finished
			if ($j == 0 && $i == self::ROWS) {
				break;
			}
		}

	}

	private function saveKeyInMatrix() {
		// transform key into array, and remove duplicate values + renumber keys
		$key = str_split($this->key);
		$key = array_unique($key);
		$key = array_values($key);

		$this->key_clean = $key;

		// initialize values
		$counter = 0;
		$i = 0;
		$j = 0;

		// save key
		while ($counter < sizeof($key)) {
			// save value
			$this->matrix[$i][$j] = $key[$counter];

			// remove letter from alphabet since it's already in use in password
			if (isset(self::$alphabet[$key[$counter]])) {
				unset(self::$alphabet[$key[$counter]]);
			}

			// increment counter and position
			$counter++;
			$j++;

			// if we reach end of row, go to beginning of next row
			if ($j == self::COLUMNS) {
				$j = 0;
				$i++;
			}
		}

		// return position of next free element in matrix
		$return_object = new stdClass();
		$return_object->i = $i;
		$return_object->j = $j;

		return $return_object;
	}

	public function encryptMessage($message) {
		$message = $this->prepareMessageForEncryption($message);
		$encrypted_message = array();

		for ($i = 0; $i < sizeof($message); $i = $i+2) {
			$translated_letters = $this->encryptLetters($message[$i], $message[$i+1], true);

			foreach($translated_letters as $letter) {
				array_push($encrypted_message, $letter);
			}
		}

		return $this->parseEncryptedMessage($encrypted_message);
	}

	private function parseEncryptedMessage($message) {
		$message_str = '';
		for ($i = 0; $i < sizeof($message); $i = $i + 2) {
			$message_str .= $message[$i] . $message[$i+1] . ' ';
		}

		return $message_str;
	}

	/** Helper function to translate the letters using the Playfair Rules
	 * @param $first_letter
	 * @param $second_letter
	 * @return array
	 */
	private function encryptLetters($first_letter, $second_letter) {
		$fl_position = $this->getPositionOfLetterInMatrix($first_letter);
		$sl_position = $this->getPositionOfLetterInMatrix($second_letter);

		// letters are on same row, get letter to right
		if ($fl_position->i == $sl_position->i) {
			return $this->encryptLettersRow($fl_position, $sl_position);
		}

		if ($fl_position->j == $sl_position->j) {
			return $this->encryptLettersColumn($fl_position, $sl_position);
		}

		return $this->encryptLettersRectangular($fl_position, $sl_position);
	}

	/** Given a letter, finds its position in the matrix and returns its coordinates
	 * @param $letter
	 * @return stdClass
	 */
	private function getPositionOfLetterInMatrix($letter) {
		for ($i = 0; $i < self::ROWS; $i++) {
			for ($j = 0; $j < self::COLUMNS; $j++) {
				if ($this->matrix[$i][$j] == $letter) {
					$return_object = new stdClass();
					$return_object->i = $i;
					$return_object->j = $j;
					return $return_object;
				}
			}
		}
	}

	/** Get next right letter in row. If at end, get first letter in row
	 * @param $fl_position
	 * @param $sl_position
	 * @return array
	 */
	private function encryptLettersRow($fl_position, $sl_position) {
		$result = array();

		if ($fl_position->j != self::COLUMNS - 1) {
				array_push($result, $this->matrix[$fl_position->i][$fl_position->j+1]);
		} else {
				array_push($result, $this->matrix[$fl_position->i][0]);
		}


		if ($sl_position->j != self::COLUMNS - 1) {
				array_push($result, $this->matrix[$sl_position->i][$sl_position->j + 1]);
		} else {
				array_push($result, $this->matrix[$sl_position->i][0]);
		}

		return $result;
	}


	/** Get next down letter in column. If at end, get first letter in column
	 * @param $fl_position
	 * @param $sl_position
	 * @return array
	 */
	private function encryptLettersColumn($fl_position, $sl_position) {
		$result = array();

		if ($fl_position->i != self::ROWS - 1) {
			array_push($result, $this->matrix[$fl_position->i+1][$fl_position->j]);
		} else {
			array_push($result, $this->matrix[0][$fl_position->j]);
		}


		if ($sl_position->i != self::COLUMNS - 1) {
			array_push($result, $this->matrix[$sl_position->i+1][$sl_position->j]);
		} else {
			array_push($result, $this->matrix[0][$sl_position->j]);
		}

		return $result;
	}


	private function decryptLettersRectangular($fl_position, $sl_position) {
		$result = array();

		array_push($result, $this->matrix[$fl_position->i][$sl_position->j]);
		array_push($result, $this->matrix[$sl_position->i][$fl_position->j]);

		return $result;
	}


	/** Adds X between repeating letters and pads with Z if needed
	 * @param $message
	 * @return array
	 */
	private function prepareMessageForEncryption($message) {
		$message = str_replace(' ', '', $message);
		$message = str_split($message);
		$new_message = array();

		for ($i = 0; $i < sizeof($message); $i++) {
			array_push($new_message, $message[$i]);

			if (($i != sizeof($message) - 1) && $message[$i] == $message[$i+1]) {
				array_push($new_message, 'x');
			}
		}

		if (sizeof($new_message)%2 != 0) {
			array_push($new_message, 'z');
		}

		return $new_message;
	}

	public function decryptMessage($message) {
		$message = $message = str_replace(' ', '', $message);
		$message = str_split($message);
		$decrypted_message = array();

		for ($i = 0; $i < sizeof($message); $i = $i+2) {
			$translated_letters = $this->decryptLetters($message[$i], $message[$i+1], true);

			foreach($translated_letters as $letter) {
				array_push($decrypted_message, $letter);
			}
		}

		return $this->parseDecryptedMessage($decrypted_message);
	}

	private function parseDecryptedMessage($message) {
		$decrypted_message = array();
		$size = sizeof($message);

		for($i = 0; $i < $size; $i++) {
			if ($message[$i] == 'x') {
				if ($message[$i-1] == $message[$i+1]) {
					continue;
				}
			}

			array_push($decrypted_message, $message[$i]);
		}

		// remove trailing z if it exists
		if ($decrypted_message[sizeof($decrypted_message)-1] == 'z') {
			unset($decrypted_message[sizeof($decrypted_message)-1]);
		}

		//turn into string and return
		return implode("", $decrypted_message);
	}

	/** Helper function to translate the letters using the Playfair Rules
	 * @param $first_letter
	 * @param $second_letter
	 * @return array
	 */
	private function decryptLetters($first_letter, $second_letter) {
		$fl_position = $this->getPositionOfLetterInMatrix($first_letter);
		$sl_position = $this->getPositionOfLetterInMatrix($second_letter);

		// letters are on same row, get letter to right
		if ($fl_position->i == $sl_position->i) {
			return $this->decryptLettersRow($fl_position, $sl_position);
		}

		if ($fl_position->j == $sl_position->j) {
			return $this->decryptLettersColumn($fl_position, $sl_position);
		}

		return $this->decryptLettersRectangular($fl_position, $sl_position);
	}

	/** Get next left letter in row. If at beginning, get last letter in row
	 * @param $fl_position
	 * @param $sl_position
	 * @return array
	 */
	private function decryptLettersRow($fl_position, $sl_position) {
		$result = array();

		if ($fl_position->j != 0) {
			array_push($result, $this->matrix[$fl_position->i][$fl_position->j-1]);
		} else {
			array_push($result, $this->matrix[$fl_position->i][self::COLUMNS-1]);
		}


		if ($sl_position->j != 0) {
			array_push($result, $this->matrix[$sl_position->i][$sl_position->j-1]);
		} else {
			array_push($result, $this->matrix[$sl_position->i][self::COLUMNS-1]);
		}

		return $result;
	}


	/** Get next up letter in column. If at beginning, get last letter in column
	 * @param $fl_position
	 * @param $sl_position
	 * @return array
	 */
	private function decryptLettersColumn($fl_position, $sl_position) {
		$result = array();

		if ($fl_position->i != 0) {
			array_push($result, $this->matrix[$fl_position->i-1][$fl_position->j]);
		} else {
			array_push($result, $this->matrix[self::ROWS-1][$fl_position->j]);
		}


		if ($sl_position->i != 0) {
			array_push($result, $this->matrix[$sl_position->i-1][$sl_position->j]);
		} else {
			array_push($result, $this->matrix[self::ROWS-1][$sl_position->j]);
		}

		return $result;
	}


	private function encryptLettersRectangular($fl_position, $sl_position) {
		$result = array();

		array_push($result, $this->matrix[$fl_position->i][$sl_position->j]);
		array_push($result, $this->matrix[$sl_position->i][$fl_position->j]);

		return $result;
	}

	/**
	 * Used for debugging
	 */
	private function printMatrix() {
		for ($i = 0; $i < self::ROWS; $i++) {
			for ($j = 0; $j < self::COLUMNS; $j++) {
				$this->matrix[$i][$j] = $this->matrix[$i][$j] ? $this->matrix[$i][$j] : 0;
				print_r($this->matrix[$i][$j] . ' ');
			}
			print_r(PHP_EOL);
		}
	}

	/**
	 * Fills matrix with empty strings
	 */
	private function prefillMatrix() {
		for ($i = 0; $i < self::ROWS; $i++) {
			$this->matrix[$i] = array(self::COLUMNS);

			for ($j = 0; $j < self::COLUMNS; $j++) {
				$this->matrix[$i][$j] = '';
			}
		}
	}
}

/**
 * Start of program
 */

if ($argc != 4) {
	printf("Incorrect number of parameters. Usage: php q3.php <_key_> <_filename_> <encrypt/decrypt>");
	exit();
}

$key = (String)$argv[1];

if (!$key) {
	printf("First parameter is not a string. The key must be a string");
	exit();
}

$filename = (String)$argv[2];

$message = file_get_contents($filename, FILE_USE_INCLUDE_PATH);

if (!$message) {
	printf("Filename is incorrect or empty. Please make sure that the file is in the same folder as the script and double check the name");
	exit();
}

$playfair = new PlayFairAlgorithm($key);
$encrypt = ((String)$argv[3] == 'encrypt') ? 1 : 0;

if ($encrypt) {
	$encrypted_message = $playfair->encryptMessage($message);
	printf($encrypted_message);
	file_put_contents($filename, $encrypted_message, FILE_USE_INCLUDE_PATH);
} else {
	$decrpyted_message = $playfair->decryptMessage($message);
	printf($decrpyted_message);
	file_put_contents($filename, $decrpyted_message, FILE_USE_INCLUDE_PATH);
}

exit();
