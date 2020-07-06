<?php 
namespace App\Http\Traits;
use DB;
use App\Dictionary;
trait SpellcheckerTrait {
    private $englishWords =array();
    public $VERSION = "1.0.0";
    
    // input misspelled word
	private $input = null;
                      
	private $shortest = -1;
                      
	private $final_word = null;
                                  
    private $error = null;    
    
    // a text to display before displaying a corrected text.
    // it is not used if the text is not wrong...
    private $prepend = null;
    
    // holds an array of spell errors occuring in users input and 
    // their corrections.
    protected $input_errors = array();
                                  
    // global check if spellerrors exist.
    private $haserrors = false;
    public $isCorrectMatch = false;
    public $isCorrectMatchWord = '';
    public $isCorrectWordtoMatch = '';
	
	public function checkSuggestionInit($string = null){ 
		$this->isCorrectWordtoMatch = '';
		$this->englishWords = DB::select("select word from dictionary where status = '1'");
		if(is_string($string) && !empty($string)){
		    $this->input = $string;
            $this->correct();
        } else {
            $this->error = "error";
        }
    }
    
    protected function correct(){
        
        if(empty($this->input)){
            $this->error = "There is nothing to correct";
            return;
        }
        
        $this->isCorrectWordtoMatch = $this->input;                    
	    $input_break = preg_split("/[^a-zA-Z0-9\-_]/", $this->input);
	    $break ='';          
        foreach($input_break  as $input_breaks){
			if($break !='') {
				$break = $break.' '.$input_breaks;
			} else {
				$break = $input_breaks;
			}
		}
		$break = trim(strtolower($break));
		$closest = "";
		// loop through words to find the closest
		foreach ($this->englishWords as $wordDic) {
			$word = '';
			$word = strtolower($wordDic->word);
			if(strtolower($this->isCorrectWordtoMatch) == strtolower($word)) {
				$this->isCorrectMatch = true;
				break;
			}

			// calculate the distance between the input word,
			// and the current word
			$lev = levenshtein(strtolower($break), strtolower($word));

			// check for an exact match
			if ($lev == 0) {

				// closest word is this one (exact match)
				$closest = $break;
				$this->shortest = -1;

				// break out of the loop; we've found an exact match
				break;
			}

			// if this distance is less than the next found shortest
			// distance, OR if a next shortest word has not yet been found
			if ($lev <= $this->shortest || $this->shortest < 0) {
				// set the closest match, and shortest distance
				$closest  = $word;
				// Keeping the upper cases...
				if(preg_match("/[A-Z]/", $break)){
					if(preg_match("/[a-z]/", $break))
						$closest = ucwords($closest);
					else 
						$closest = strtoupper($closest);
					
				}
				$this->shortest = $lev;
			}
		}
		if($this->shortest > -1){
			array_push($this->input_errors, array($break,$closest));
			if($this->haserrors!=true)$this->haserrors = true;
		}
		
		$this->final_word .= $closest." ";
    
        $this->final_word = substr($this->final_word, 0, strlen($this->final_word) - 1);

    }
                                  
    function is_correct(){
        
        if(empty($this->input)){
            $this->error = "There is nothing to verify";
            return;
        }
        
        if($this->shortest > -1)
                return true;
        else
            return false;
    }
                                  
    function prepend($string){
        
        $this->prepend = $string;
        
    }
                                  
    function corrected(){
        if(!empty($this->input)){
			if($this->isCorrectMatch) {
				$this->isCorrectMatch = false;
				return array('word' =>$this->isCorrectWordtoMatch,"match" => true);
			} else {
				return array('word' =>($this->haserrors == true?$this->prepend:"").$this->final_word,"match" => false);
			}
        } else {
            return array('word' =>$this->input,"match" => false);
        }
    }
                                  
    function spell_errors(){
        return $this->input_errors;
    }
                                  
    function error(){
        
        return $this->error;
        
    }
}
?>

