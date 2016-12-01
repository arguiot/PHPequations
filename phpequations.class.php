<?php
/**--------------------------------------------------------- 
  * @class	phpEquations
  *
  * @desc	Find roots or solve system of linear, 
  * 		 polynomial and trigonometric equations.
  *
  * 		 It uses a variant of Newton's method
  *		 to solve equations.
  *
  *		 Equations can be entered in natural
  *		 format. For example:
  *		 x + y = 4-2
  *		 x + 2*y = 8 - x
  *
  * @author	Naveed ur Rehman
  *---------------------------------------------------------
  */
class phpequations
{  
     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val integer Stores the status of equations
       *		    whether loaded or not.
       *---------------------------------------------------------
       */
	protected $isloaded = 0;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds the cleaned equations in array.
       *---------------------------------------------------------
       */
	protected $equationsarray;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds the equations in array after parsing.
       *---------------------------------------------------------
       */
	protected $equationsparses;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array 2-level, holds blocks of equations.
       *	     Used for optimization purpose.
       *---------------------------------------------------------
       */
	protected $equationsblocks;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds information about parsed equation
       *	     blocks. Used for optimization purpose.
       *---------------------------------------------------------
       */
	protected $blocksparses;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds jacobian matrix in algebraic format.
       *---------------------------------------------------------
       */
	protected $jmatrix;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds list of secure maths functions.
       *---------------------------------------------------------
       */
	protected $securefunctions;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds list of constants.
       *---------------------------------------------------------
       */
	protected $constants;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds the list of determinant templates upto 
       *	     available in phpequations.resource.dat
       *---------------------------------------------------------
       */
	protected $determinanttemplates;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val float Iteration step.
       *	     (default: 0.01)
       *---------------------------------------------------------
       */
	protected $step=0.01;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val integer Maximum number of iterations.
       *	       (default: 100)
       *---------------------------------------------------------
       */
	protected $maxiterations=100;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val integer Accuracy upto the number of digits after decimal.
       *	       (default: 100)
       *---------------------------------------------------------
       */
	protected $accuracy=4;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val integer Maximum allowed time of execution in 
       *	       seconds.
       *	       (default: 60)
       *---------------------------------------------------------
       */
	protected $maxtime=60;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val integer Maximum number of allowed	variables in 
       *	       equations block.
       *	       (default: 8)
       *---------------------------------------------------------
       */
	protected $maxvariables=8;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds the list of errors.
       *---------------------------------------------------------
       */
	protected $errors;

     /**--------------------------------------------------------- 
       * A protected variable
       *
       * @val array Holds the list of mathematical operators.
       *	     Used for parsing purpose.
       *
       *	     DO NOT CHANGE!
       *---------------------------------------------------------
       */
	protected $operations = array('+','-','*','/','=',',',')',' ');

     /**--------------------------------------------------------- 
       * Sets value of $securefunctions, $constants and load
       *  determinant templates from phpequations.resource.dat
       *  in $determinanttemplates. Also reset the status of
       *  different variables by calling reset().
       *
       * @return void
       *---------------------------------------------------------
       */
	public function __construct()
	{
	 $this->securefunctions =array("pow","sin","cos","tan","asin","acos","atan","sinh","cosh","tanh","asinh","acosh","atanh","atan2","ceil","exp","floor","log","log10","min","max","pi","rad2deg","rand","round","sqrt");
	 $this->constants = array("PI"=>3.14159);
	 if(file_exists($datafile="phpequations.resource.dat"))
	 {
	  $data = $this->xml2array(file_get_contents($datafile));
	  $dtemps = $data["determinanttemplates"];
	  if(is_array($dtemps))
	  {
	   foreach($dtemps as $key=>$value)
	   {$this->determinanttemplates[substr($key,1)]=$value;}
	  }
	 }
	 $this->reset();
	}

     /**--------------------------------------------------------- 
       * Add secure functions in run-time.
       *
       * Accepts the new function name and update
       * $securefunctions.
       *
       * @param string $newfunction Name of new function
       *
       * @return integer okey (1) or error (0)
       *---------------------------------------------------------
       */
	public function addsecurefunction($newfunction)
	{
	 if($newfunction)
	 {
	  $newfunction = preg_replace("/[^a-zA-Z0-9_]/", "",$newfunction );
	  if($newfunction && !in_array($newfunction,$this->securefunctions))
	  {$this->securefunctions[]=$newfunction;return(1);}
	  else
	  {$this->adderror("$newfunction is already a secure function");return(0);}
	 }
	}

     /**--------------------------------------------------------- 
       * Retrieve an array or existance of secured function.
       *
       *
       * @param string $n NULL or name of secured function
       *
       * @return array/string list of secured functions/name
       *---------------------------------------------------------
       */
	public function getsecurefunctions($n=NULL){if($n==NULL){return($this->securefunctions);}else{return($this->securefunctions[$n]);}}

     /**--------------------------------------------------------- 
       * Add constants in run-time.
       *
       * Accepts the new constant along with its value and update
       * $constants.
       *
       * @param string $newconstant Name of new constant
       * @param string $value Value of constant
       *
       * @return integer okey (1) or error (0)
       *---------------------------------------------------------
       */
	public function addconstant($newconstant,$value)
	{
	 $newconstant = preg_replace("/[^a-zA-Z0-9_]/", "",strtoupper($newconstant));
	 if(!isset($this->constants[$newconstant]))
	 {$this->constants[$newconstant]=(float)($value);return(1);}
	 else
	 {$this->adderror("$newconstant is already a constant");return(0);}
	}

     /**--------------------------------------------------------- 
       * Retrieve an array or existance of constant.
       *
       * @param string $n NULL or name of constant
       *
       * @return array/string List of constants/name
       *---------------------------------------------------------
       */
	public function getconstants($n=NULL){if($n==NULL){return($this->constants);}else{return($this->constants[$n]);}}

     /**--------------------------------------------------------- 
       * Set step in run-time.
       *
       * Accepts the new step and update $step.
       *
       * @param floa $step New step
       *
       * @return integer okey (1) or error (0)
       *---------------------------------------------------------
       */
	public function setstep($step){$step=(float)($step);if($step>0){$this->step=$step;return(1);}else{$this->adderror("$step is an invalid number for step");return(0);}}

     /**--------------------------------------------------------- 
       * Retrieve current step
       *
       * @return float Step as set
       *---------------------------------------------------------
       */
	public function getstep(){return($this->step);}

     /**--------------------------------------------------------- 
       * Set maximum number of iterations in run-time.
       *
       * Accepts the number of maximum iterations
       *  and update $maxiterations.
       *
       * @param float $maxiterations New maximum iterations
       *
       * @return integer okey (1) or error (0)
       *---------------------------------------------------------
       */
	public function setmaxiterations($maxiterations){$maxiterations=(int)($maxiterations);if($maxiterations>0){$this->maxiterations=$maxiterations;return(1);}else{$this->adderror("$maxiterations is an invalid number for maximum iterations");return(0);}}

     /**--------------------------------------------------------- 
       * Retrieve number of maximum iterations.
       *
       * @return integer Number of maximum iterations as set.
       *---------------------------------------------------------
       */
	public function getmaxiterations(){return($this->maxiterations);}

     /**--------------------------------------------------------- 
       * Set accuracy in run-time.
       *
       * Accepts new value for accuracy and update $accuracy.
       *
       * @param integer $accuracy New accuracy
       *
       * @return integer okey (1) or error (0)
       *---------------------------------------------------------
       */
	public function setaccuracy($accuracy){$accuracy=(float)($accuracy);if($accuracy>0){$this->accuracy=$accuracy;return(1);}else{$this->adderror("$accuracy is an invalid number for accuracy");return(0);}}

     /**--------------------------------------------------------- 
       * Retrieve accuracy.
       *
       * @return integer Current accuracy as set.
       *---------------------------------------------------------
       */
	public function getaccuracy(){return($this->accuracy);}

     /**--------------------------------------------------------- 
       * Set maximum execution time (sec) in run-time.
       *
       * Accepts new value for maximum execution time
       *  and update $maxtime.
       *
       * @param integer $maxtime New maximum execution time (sec)
       *
       * @return integer okey (1) or error (0)
       *---------------------------------------------------------
       */
	public function setmaxtime($maxtime){$maxtime=(int)($maxtime);if($maxtime>0){$this->maxtime=$maxtime;return(1);}else{$this->adderror("$maxtime is an invalid number for maximum time");return(0);}}

     /**--------------------------------------------------------- 
       * Retrieve maximum allowed time.
       *
       * @return integer Maximum execution time as set.
       *---------------------------------------------------------
       */
	public function getmaxtime(){return($this->maxtime);}

     /**--------------------------------------------------------- 
       * Set maximum number of variables in block in run-time.
       *
       * Accepts new value for maximum number of variables in
       *  an equation's block and update $maxvariables.
       *
       * @param integer $maxtime New maximum execution time (sec)
       *
       * @return integer okey (1) or error (0)
       *---------------------------------------------------------
       */
	public function setmaxvariables($maxvariables){$maxvariables=(int)($maxvariables);if($maxvariables>0){$this->maxvariables=$maxvariables;return(1);}else{$this->adderror("$maxvariables is an invalid number for maximum variables");return(0);}}

     /**--------------------------------------------------------- 
       * Retrieve number of maximum variables allowed in block.
       *
       * @return integer Number of maximum variables as set.
       *---------------------------------------------------------
       */
	public function getmaxvariables(){return($this->maxvariables);}

     /**--------------------------------------------------------- 
       * Reset value of $isloaded, $equationsarray,
       *  $equationsparses, $equationsblocks, $blocksparses 
       *  and $jmatrix.
       *
       * @return void
       *---------------------------------------------------------
       */
	protected function reset()
	{
	 $this->isloaded=0;
	 $this->equationsarray=NULL;
	 $this->equationsparses=NULL;
	 $this->equationsblocks=NULL;
	 $this->blocksparses=NULL;
	 $this->jmatrix=NULL;
	}

     /**--------------------------------------------------------- 
       * A public function for loading equations given in
       *  natural format.
       *
       * If everything goes fine, $loaded value changes to 1.
       *  Otherwise, list of errors can be retrieved
       *  from errors().
       *
       * @param string $equations Mathematical equations in
       * 			   natural format.
       *
       * @return integer Number of maximum variables as set.
       *---------------------------------------------------------
       */
	public function loadequations($equations)
	{
	 $this->reset();

	 $equations = $this->str_replacemass(" \t<>:~`!@$^&|?","",$equations);
	 $equations = $this->str_replacemass(";","\r\n",$equations);
	 $equations = $this->str_replacemass("%","*(100)",$equations);

	 if(!$equations){$this->adderror("Can not load equations");return(0);}

	 $this->equationsarray = $this->loadequationsarray($equations);
	 if(!$this->equationsarray){$this->adderror("Can not load equations");return(0);}

	 $this->equationsparses = $this->loadequationsparses($this->equationsarray);
	 if(!$this->equationsparses){$this->adderror("Can not load equations");return(0);}

	 $this->equationsblocks = $this->loadequationsblocks($this->equationsarray);
	 if(!$this->equationsblocks){$this->adderror("Can not load equations");return(0);}

	 $this->blocksparses = $this->loadblocksparses($this->equationsblocks);
	 if(!$this->blocksparses){$this->adderror("Can not load equations");return(0);}

	 $this->jmatrix = $this->loadjmatrix($this->equationsblocks);
	 if(!$this->jmatrix){$this->adderror("Can not load equations");return(0);}

	 $this->isloaded=1; //Everything is fine!
	}

     /**--------------------------------------------------------- 
       * A public function for obtaining status before actually
       *  solving equations.
       *
       * Mainly, it returns the value of $isloaded.
       *
       * @return integer Equations were loaded properly or not?
       *---------------------------------------------------------
       */
	public function isloaded(){if($this->isloaded==1){return(1);}else{return(0);}}

     /**--------------------------------------------------------- 
       * A public function obtaining array of cleaned equations.
       *
       * It may also be used to obtain particular equation
       *  by giving its number.
       *
       * Mainly, it returns $equationsarray.
       *
       * @param integer $n equation number
       *
       * @return array/integer Array of cleaned equations or
				nth number equation
       *---------------------------------------------------------
       */
	public function getequationsarray($n=NULL){if($n==NULL){return($this->equationsarray);}else{return($this->equationsarray[$n]);}}

     /**--------------------------------------------------------- 
       * A public function obtaining array of parsed equations.
       *
       * It may also be used to obtain particular equation
       *  by giving its number.
       *
       * Mainly, it returns $equationsparses.
       *
       * @param integer $n equation number
       *
       * @return array/integer Array of parsed equations or
				nth number equation
       *---------------------------------------------------------
       */
	public function getequationsparses($n=NULL){if($n==NULL){return($this->equationsparses);}else{return($this->equationsparses[$n]);}}

     /**--------------------------------------------------------- 
       * A public function obtaining array of blocks of
       *  equations.
       *
       * It may also be used to obtain particular block
       *  by giving its number.
       *
       * Mainly, it returns $equationsblocks.
       *
       * @param integer $n block number
       *
       * @return array/integer Array of block or
				nth number block
       *---------------------------------------------------------
       */
	public function getequationsblocks($n=NULL){if($n==NULL){return($this->equationsblocks);}else{return($this->equationsblocks[$n]);}}

     /**--------------------------------------------------------- 
       * A public function obtaining array of parsed blocks of
       *  equations.
       *
       * It may also be used to obtain particular parsed block
       *  by giving its number.
       *
       * Mainly, it returns $blocksparses.
       *
       * @param integer $n parsed block number
       *
       * @return array/integer Array of parsed block or
				nth number parsed block
       *---------------------------------------------------------
       */
	public function getblocksparses($n=NULL){if($n==NULL){return($this->blocksparses);}else{return($this->blocksparses[$n]);}}

     /**--------------------------------------------------------- 
       * A public function obtaining array jacobian matrix.
       *
       * Mainly, it returns $jmatrix.
       *
       * @param integer $n particular row of matrix
       *
       * @return array Array of jacobian matrix or its nth row
       *---------------------------------------------------------
       */
	public function getjmatrix($n=NULL){if($n==NULL){return($this->jmatrix);}else{return($this->jmatrix[$n]);}}

     /**--------------------------------------------------------- 
       * A protected function for loading equations in array.
       *
       * @param array $equationsarray Array of equations
       *
       * @return array Equations
       *---------------------------------------------------------
       */
	protected function loadequationsarray($equationsarray)
	{
	 $equations = explode("\r\n",$equationsarray);
	 if(!is_array($equations)){return(0);}
	 foreach($equations as $key=>$value)
	 {
	  if($value)
	  {
	   if(strpos(" $value","//")){$value=explode("//",$value);$value=$value[0];}
	   if(strpos($value,"#"))
	   {
	    foreach($this->constants as $key2=>$value2)
	    {$value = str_replace($key2.'#',$value2,$value);}
	   }
	   if(strpos($value,"#")){$this->adderror("Undefined constant ($value)");return(0);}
	   if($value)
	   {
	   if(!strpos($value,"=")){$value="$value=0";} 
	   $valuex=explode("=",$value);
	   foreach($valuex as $key2=>$value2)
	   {
	    if($key2>0)
	    {
	     if($value2){$value3=$valuex[0]."-(".$value2.")=0";}else{$value3=$valuex[0].'=0';}
	     ++$c;
	     $E[$c]=$value3;
	    }
	   }
	   }
	  }
	 }
	 $allvParses=array();
	 foreach($E as $key=>$value)
	 {
	  $vParses = $this->expressionparser($value);
	  $allvParses=array_merge($vParses,$allvParses);
	  if(!is_array($vParses)){$this->adderror("Can not load equations array");return(0);}
	  foreach($vParses as $key2=>$value2)
	  {
	   foreach($this->operations as $key3=>$value3)
	   {
	    $E[$key]=str_replace($value2.$value3,'$'.$value2.$value3,$E[$key]);
	   }
	  }
	 }
	if(count($allvParses)!=count($E)){$this->adderror("Number of equations (".count($E).") are not equal to number of variables (".count($allvParses).")");return(0);}
	return($E);
	}

     /**--------------------------------------------------------- 
       * A protected function for loading parsed equations
       * in an array.
       *
       * @param array $equations Array of loaded equations
       *
       * @return array Equations
       *---------------------------------------------------------
       */
	protected function loadequationsparses($equations)
	{
	 foreach($equations as $key=>$value)
	 {
	  $temp = $this->expressionparser($value);
	  if(!is_array($temp)){$this->adderror("Can not load equations parses");return(0);}
	  $E[$key] = $temp;
	 }
	 return($E);
	}

     /**--------------------------------------------------------- 
       * A protected function for loading equations blocks
       * in an array.
       *
       * @param array $equationsarray Array of loaded equations
       *
       * @return array Blocks
       *---------------------------------------------------------
       */
	protected function loadequationsblocks($equationsarray)
	{
	 $n=0;
	 $E = $equationsarray;
	 if(!is_array($E)){return(0);}
	 foreach($E as $key=>$value)
	 {
	  $Px = $this->equationsparses[$key];
	  sort($Px);
	  $P["count"][$key] = count($Px);
	  $P["flag"][$key] = implode(".",$Px).".";
	 }
	for($counter=1;$counter<=count($E);$counter++)
	{
	 if(!count($P["count"])){break;}
	 if(min($P["count"])==1)
	 {
	  foreach($P["count"] as $key=>$value)
	  {
	   if($value==1)
	   {
	    $block[++$n][$key]=$E[$key];
	    $unsetvar = $P["flag"][$key];
	    foreach($P["flag"] as $key2=>$value2)
	    {
	     if($unsetvar)
	     {
	      if(strpos(" ".$P["flag"][$key2],$unsetvar))
	      {
	       $P["flag"][$key2] = str_replace($unsetvar,"",$P["flag"][$key2]);
	       $P["count"][$key2]-=1;
	      }
	     }
	    }
	    unset($P["flag"][$key]);
	    unset($P["count"][$key]);
	   }
	  }
	 }
	 else
	 {
	  $min = min($P["count"]);
	  foreach($P["flag"] as $key=>$value)
	  {
	   if($P["count"][$key]==$min)
	   {unset($newblock);
	    foreach($P["flag"] as $key2=>$value2)
	    {
	     if($key2!=$key && $value2==$value)
	     {
	      $newblock[$key2]=$E[$key2];
	     }
	    }
	   if(is_array($newblock))
	   {
	    $newblock[$key]=$E[$key];
	    $block[++$n]=$newblock;
	    foreach($newblock as $key3=>$value3)
	    {
	     $reducevars=explode(".",$P["flag"][$key3]); 
	     foreach($P["flag"] as $key4=>$value4)
	     {
	      foreach($reducevars as $key5=>$value5)
	      {
	       if($value5)
	       {
                $unsetvar = $value5.".";//echo "[$unsetvar] ";
       		if(strpos(" ".$P["flag"][$key4],$unsetvar))
	        {
        	 $P["flag"][$key4] = str_replace($unsetvar,"",$P["flag"][$key4]);
	         $P["count"][$key4]-=1;
        	}
	       }
	      }
	     }
	     unset($P["flag"][$key3]);
	     unset($P["count"][$key3]);
	   }
	  }
	 }
  	}
        }
        }
	if(count($P["count"]))
	{
	 ++$n;
	 foreach($P["count"] as $key=>$value)
	 {
	  $block[$n][$key]=$E[$key];
         }
         }
	return($block);
	}

     /**--------------------------------------------------------- 
       * A protected function for loading equations parsed blocks
       * in an array.
       *
       * @param array $equationsblocks Array of equations blocks
       *
       * @return array Parsed blocks
       *---------------------------------------------------------
       */
	protected function loadblocksparses($equationsblocks)
	{
	 $overall = array('$'.'junk');
	 foreach($equationsblocks as $blockkey=>$equations)
	 {
	  foreach($equations as $equationkey=>$equation)
	  {
	   $equationparses = $this->equationsparses[$equationkey];
	   foreach($equationparses as $variable)
	   {if(!isset($blocksparses[$blockkey][$variable]) && !in_array($variable,$overall)){$blocksparses[$blockkey][$variable]=$variable;$overall[$variable]=$variable;}}
	  }
	 }
	$jeqno=count($this->equationsarray);
	foreach($blocksparses as $key=>$value)
	{
	 if(count($value)<=1)
	 {
	  $jeqno++;
	  $newvariable = '$'.'junkvariable'.$jeqno;
	  $newjequation = $newvariable.'=0';
	  $this->equationsarray[$jeqno]=$newjequation;
	  $this->equationsparses[$jeqno]=$this->expressionparser($this->getexpression($newjequation));
	  $this->equationsblocks[$key][$jeqno]=$newjequation;
	  $blocksparses[$key][$newvariable]=$newvariable;
	 }
	}
	 if(count($this->equationsblocks)>count($blocksparses))
	 {
	  for($i=count($blocksparses)+1;$i<=count($this->equationsblocks);$i++)
	  {
	   unset($this->equationsblocks[$i]);
	  }
	 }
	 foreach($blocksparses as $key=>$value)
	 {
	  if(count($value)>$this->maxvariables)
	  {$this->adderror("Number of variables in block ($key) exceeded maximum allowed variables (".($this->maxvariables).")");return(0);}
	 }
	return($blocksparses);
	}

     /**--------------------------------------------------------- 
       * A protected function for loading jacobian matrix
       * in an array.
       *
       * @param array $equationsblocks Array of equations blocks
       *
       * @return array Jacobian matrix
       *---------------------------------------------------------
       */
	protected function loadjmatrix($equationsblocks)
	{
	 foreach($equationsblocks as $blockkey=>$blockequations)
	 {
	  unset($V);$varcount=0;$eqcount=0;
	  $V = $this->blocksparses[$blockkey];
	  $blockeqcount = count($blockequations);
	  foreach($blockequations as $equationkey=>$equation)
	  {
	   $eqcount++;$varcount=0;
	   $expression = $this->getexpression($equation);
	   foreach($V as $variable)
	   {$varcount++;
	    $celloffset = str_replace($variable,"($variable+".$this->step.")",$expression);
	    $cells[$blockkey][$eqcount][$varcount] = "(($celloffset)-($expression))/(".$this->step.")";
	   }
	  }
	 }
	 return($cells);
	}

     /**--------------------------------------------------------- 
       * A protected function for parsing a mathematical 
       * expression.
       *
       * @param string $expression A mathematical expression
       *
       * @return array Parsed information
       *---------------------------------------------------------
       */
	protected function expressionparser($expression)
	{
	 $expression.=" ";
	 $con = "";
	 for($z=0;$z<=strlen($expression)-1;$z++)
	 {
	  $m = substr($expression,$z,1);
	  if($m=="(")
	  {
	   $t = "functions";
	   $b=1;
	  } 
	  if(in_array($m,$this->operations))
	  {
	   if(is_numeric($con))
	   {$t = "numbers";}
	   elseif(is_string(substr($con,0,1)))
	   {$t="variables";}
	   else
	   {$t = "entities";}
	   $b=1;
	  }  
	  if($b==1)
	  {
	   if($con)
	   {
	    if($t=="variables"){$s[$con]=$con;}
	    if($t=="functions" && !in_array($con,$this->securefunctions)){$this->adderror("$con is an insecure function");return(0);}
	    $con="";  
	   }
	    $b=0;
	  }
	  else
	  {
	   $con .= $m;
	  }
	 }
	 return($s);
	}

     /**--------------------------------------------------------- 
       * A protected function for creating unqiue variable names
       * 
       *
       * @param string $expression A mathematical expression
       *
       * @return array Parsed information
       *---------------------------------------------------------
       */
	protected function digno($n,$l=5){return(str_repeat("0",$l-strlen($n)).$n);}

     /**--------------------------------------------------------- 
       * A protected function for getting list of variables
       *  sorted as per their name lengths.
       *
       * @param array $P Array of variables
       *
       * @return array Sorted list of variables
       *---------------------------------------------------------
       */
	protected function lengthsort($P)
	{
	 foreach($P as $key=>$value)
	 {
	  $N[strlen($value)][]=$value;
	 }
	 krsort($N);
	 foreach($N as $key1=>$value1)
	 {
	  $B = $value1;
	  foreach($B as $key=>$value)
	  {
	   $F[]=$value;
	  }
	 }
	return($F);
	}

     /**--------------------------------------------------------- 
       * A protected function evaluating expression.
       *
       * @param string $expression Mathematical expression
       * @param array $values array of variable=value
       *
       * @return float Evaluated answer of given expression
       *---------------------------------------------------------
       */
	protected function evaluate($expression,$values)
	{
	 extract($values);
	 eval('$'.'ans'.'='.$expression.';');
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to convert equation into expression
       *
       * @param string $equation Mathematical equation
       *
       * @return float Mathematical expression
       *---------------------------------------------------------
       */
	protected function getexpression($equation)
	{
	 $equation = explode("=",$equation);
	 return($equation[0]);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain minor matrix.
       *
       * @param array $j Complete matrix
       * @param integer $pivot_row Pivoted row
       * @param integer $pivot_col Pivoted column
       * @param integer $autoreduce Auto-reduce to number
       *
       * @return array/float Minor matrix/value
       *---------------------------------------------------------
       */
	protected function minor($j,$pivot_row,$pivot_col,$autoreduce=1)
	{
	 foreach($j as $row_key=>$row)
	 {
	  if($row_key!=$pivot_row)
	  {$counter_row++;
	   foreach($row as $col_key=>$cell)
	   {
	    if($col_key!=$pivot_col)
	    {$counter_col++;
	     $M[$counter_row][$counter_col]=$cell;
	    }
	   }
	   $counter_col=0;
	  }
	 }
	 if(count($M)==2 && $autoreduce)
	 {
	  $M = $M[1][1]*$M[2][2]-$M[1][2]*$M[2][1];
	  return($M);
	 }
	 else
	 {
	  return($M);  
	 }
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain determinant of a matrix.
       *
       * @param array $j Given matrix
       *
       * @return float Determinant
       *---------------------------------------------------------
       */
	protected function determinant($j)
	{
	 $l = count($j);
	 if(isset($this->determinanttemplates[$l]))
	 {
	  eval('$'.'Detx='.$this->determinanttemplates[$l].';');
	  return($Detx);
	 }
	 $maxzero = 0;
	 for($row=1;$row<=$l;$row++)
	 {$thiszero=0;
	  for($col=1;$col<=$l;$col++)
	  {
	   if(!$j[$row][$col]){$thiszero++;}
	  }
	 if($thiszero>$maxzero){$pivot_row=$row;$maxzero=$thiszero;}
	 }
	 if(!$pivot_row){$pivot_row = 1;}	//to optimize, should be one having maximum zero
	 for($col=1;$col<=$l;$col++)
	 {
	  if($j[$pivot_row][$col])
	  {
	   $M = $this->determinant($this->minor($j,$pivot_row,$col,0));
	   $ans += $this->signcell($pivot_row+$col)*$j[$pivot_row][$col]*$M;}
	  }
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain adjoint of a matrix.
       *
       * @param array $j Given matrix
       *
       * @return array Adjoint matrix
       *---------------------------------------------------------
       */
	protected function adjoint($j)
	{
	 $j = $this->cofactor($j);
	 $j = $this->transposemat($j);
	 return($j);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain cofactors of a matrix.
       *
       * @param array $j Given matrix
       *
       * @return array Cofactors of given matrix
       *---------------------------------------------------------
       */
	protected function cofactor($j)
	{
	 $l = count($j);
	 for($row=1;$row<=$l;$row++)
	 {
	  for($col=1;$col<=$l;$col++)
	  {
	   $M = $this->minor($j,$row,$col,0);
	   $Det = $this->determinant($M);
	   $cell = (float)($this->signcell($row+$col))*$Det;
	   $ans[$row][$col]=$cell;
	  }
	 }
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain transpose of a matrix.
       *
       * @param array $j Given matrix
       *
       * @return array Transpose matrix
       *---------------------------------------------------------
       */
	protected function transposemat($j)
	{
	 $l = count($j);
	 for($row=1;$row<=$l;$row++)
	 {
	  for($col=1;$col<=$l;$col++)
	  {
	   $ans[$col][$row]=$j[$row][$col];
	  }
	 } 
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain inverse of a matrix.
       *
       * @param array $j Given matrix
       *
       * @return array Inverse matrix
       *---------------------------------------------------------
       */
	protected function inverseMat($j)
	{
	 $Det = $this->determinant($j);
	if(!$Det){return(0);}
	 $AdjointMat = $this->adjoint($j);
	 $l = count($j);
	 for($row=1;$row<=$l;$row++)
	 {
	  for($col=1;$col<=$l;$col++)
	  {
	   $ans[$row][$col]=$AdjointMat[$row][$col]/$Det;
	  }
	 } 
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain matrix my multiplying
       *  two matrices (A.B)
       *
       * @param array $j1 Given matrix (A)
       * @param array $j2 Given matrix (B)
       *
       * @return array Matrix
       *---------------------------------------------------------
       */
	protected function multiplyMat($j1,$j2)
	{
	 $rows_j1 = $this->getmatrows($j1);
	 $rows_j2 = $this->getmatrows($j2);
	 $cols_j1 = $this->getmatcols($j1);
	 $cols_j2 = $this->getmatcols($j2);
	 $matsize = $this->$cols_j1 ; //or $rows_j1 whatever
	 if($cols_j1 !=$rows_j2 ){return;}
	 for($row=1;$row<=$rows_j1;$row++)
	 {
	  for($col=1;$col<=$cols_j2;$col++)
	  {
	   $rowvector = $this->rowvector($j1,$row);
	   $colvector = $this->colvector($j2,$col);
	   $ans[$row][$col]=$this->multiplyvectors($rowvector,$colvector);
	  }
	 }
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain matrix my dividing
       *  two matrices (A/B).
       *
       * @param array $num Given numerator matrix (A)
       * @param array $den Given denominator matrix (B)
       *
       * @return array Matrix
       *---------------------------------------------------------
       */
	protected function dividemat($num,$den)
	{
	 $den = $this->inversemat($den);
	 $ans = $this->multiplymat($den,$num);
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain row vector from a matrix
       *
       * @param array $j Given matrix
       * @param array $row Numberth of row
       *
       * @return array Vector
       *---------------------------------------------------------
       */
	protected function rowvector($j,$row)
	{
	 $l = count($j);
	 for($v=1;$v<=$l;$v++)
	 {
	  $ans[$v]=$j[$row][$v];
	 }
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain column vector from a
       *  matrix
       *
       * @param array $j Given matrix
       * @param array $col Numberth of column
       *
       * @return array Vector
       *---------------------------------------------------------
       */
	protected function colvector($j,$col)
	{
	 $l = count($j);
	 for($v=1;$v<=$l;$v++)
	 {
	  $ans[$v]=$j[$v][$col];
	 }
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain multiplicaiton of 
       *  two vectors
       *
       * @param array $v1 First vector
       * @param array $v2 Second vector
       *
       * @return float Multiplication result of vectors
       *---------------------------------------------------------
       */
	protected function multiplyvectors($v1,$v2)
	{
	 $l = count($v1);
	 for($v=1;$v<=$l;$v++)
	 {
	  $ans += $v1[$v]*$v2[$v];
	 }
	 return($ans);
	}

     /**--------------------------------------------------------- 
       * A protected function to obtain count of matrix rows
       *
       * @param array $j Given matrix
       *
       * @return integer Count of matrix rows
       *---------------------------------------------------------
       */
	protected function getmatrows($j){return(count($j));}

     /**--------------------------------------------------------- 
       * A protected function to obtain count of matrix columns
       *
       * @param array $j Given matrix
       *
       * @return integer Count of matrix columns
       *---------------------------------------------------------
       */
	protected function getmatcols($j){return(count($j[1]));}

     /**--------------------------------------------------------- 
       * A public function to print maxtrix nicely.
       *   Use <pre></pre>.
       *
       * @param array $j Given matrix
       *
       * @return void
       *---------------------------------------------------------
       */
	public function printmatrix($j)
	{
	if(!is_array($j)){echo "Not array.";return;}
	 foreach($j as $row_key=>$row)
	 {
	  foreach($row as $col_key=>$cell)
	  {
	   echo "($row_key,$col_key)=>$cell\t";
	  }
	  echo "\r\n";
	 }
	  echo "\r\n";
	}

     /**--------------------------------------------------------- 
       * A protected function to get the sign of cell while
       *  calculating cofactors.
       *
       * @param integer $n Cell number
       *
       * @return integer +1 or -1 representing sign
       *---------------------------------------------------------
       */
	protected function signcell($n){if($this->iseven($n)){return(1);}else{return(-1);}}

     /**--------------------------------------------------------- 
       * A protected function to check if the number is even
       *
       * @param integer $n A number
       *
       * @return integer Yes (1) or No (0)
       *---------------------------------------------------------
       */
	protected function iseven($n){if($n % 2 == 0){return(1);}else{return(0);}}

     /**--------------------------------------------------------- 
       * A protected function to convert XML into array
       *
       * @param string $xml XML string
       *
       * @return array Converted array
       *---------------------------------------------------------
       */
	protected function xml2array($xml)
	{
	 $array = json_encode((array)@simplexml_load_string($xml));
	 if($array!="[false]")
	 {
	  $array = json_decode($array,1);
	  return($array);
	 }
	 else
	 {
	  return(0);
	 }
	}

     /**--------------------------------------------------------- 
       * A protected function to replace characters in a string
       *
       * @param string $searchwhat This is what to replace
       * @param string $replacewith This is what to replace with
       * @param string $instring The main string
       *
       * @return string Operated string
       *---------------------------------------------------------
       */
	protected function str_replacemass($searchwhat,$replacewith,$instring)
	{
	 if($searchwhat)
	 {
	  for($z=0;$z<strlen($searchwhat);$z++){$instring=str_replace(substr($searchwhat,$z,1),$replacewith,$instring);}
	 }
	 return($instring);
	}

     /**--------------------------------------------------------- 
       * A protected function to add error in array of errors
       *
       * @param string $prompt Error prompt
       *
       * @return void
       *---------------------------------------------------------
       */	
	protected function adderror($prompt){$this->errors[]=$prompt;}

     /**--------------------------------------------------------- 
       * A public function to receiver list of errors
       *
       * @return array List of errors
       *---------------------------------------------------------
       */
	public function errors(){return($this->errors);}

     /**--------------------------------------------------------- 
       * A public function to receiver count of errors
       *
       * @return integer Count of errors
       *---------------------------------------------------------
       */
	public function error(){return($this->errors[count($this->errors)-1]);}

     /**--------------------------------------------------------- 
       * A public function to retrieve if there is any error
       *  yet occured.
       *
       * @return integer Yes (1) or No (0)
       *---------------------------------------------------------
       */
	public function iserror(){if(count($this->errors)){return(1);}else{return(0);}}

     /**--------------------------------------------------------- 
       * A public function to obtain solution of equation(s).
       *
       * @return array Variable = Value
       *---------------------------------------------------------
       */
	public function solve($equations="")
	{
	$t = time();
	 if($equations){$this->loadequations($equations);}
	 if(!$this->isloaded){$this->adderror("Can not solve");return(0);}
	 foreach($this->jmatrix as $blockkey=>$matrix)
	 {
	  unset($G);
	  $varcount=0;
	  foreach($this->blocksparses[$blockkey] as $variable)
	  {$varcount++;$G[$varcount]=1;$varindex[$varcount]=substr($variable,1);$assign[$varindex[$varcount]]=1;}
	  $eqcount=0;
	  $F = $this->equationsblocks[$blockkey];
	  foreach($F as $key=>$value){$E[++$eqcount]=$value;}
	  unset($F);
	  $J=$matrix;
	  $size = count($J);
	  $iteration=0;$solved=0; //for a block
	  while($iteration<=$this->maxiterations && $solved==0)
	  {
	   if( (time()-$t) > $this->maxtime)
	   {$this->adderror("Time out (".$this->maxtime.")");return(0);}
	   $iteration++;
	   foreach($E as $equationkey=>$equation)
	   {
	    $Err[$equationkey][1]= $this->evaluate($this->getexpression($E[$equationkey]),$assign);
	   }
	   $solved=1;
	   foreach($Err as $value)
	   {
	    if(round($value[1],$this->accuracy)!=0){$solved=0;break;}
	   }
	   if($solved==1){$solved=1;break;}
	   for($row=1;$row<=$size;$row++)
	   {
	    for($col=1;$col<=$size;$col++)
	    {
	     $SolvedJ[$row][$col] = $this->evaluate($J[$row][$col],$assign);
	    }
	   }
	   $DelV = $this->dividemat($Err,$SolvedJ);
	   for($row=1;$row<=$size;$row++)
	   {
	    $assign[$varindex[$row]]-=@$DelV[$row][1];
	   }
	  }
	 if($iteration>$this->maxiterations)
	 {$this->adderror("Number of iterations in block ($blockkey) exceeded maximum limit (".$this->maxiterations.")");return(0);}
	 }
	 foreach($assign as $key=>$value)
	 {
	  if(substr($key,0,strlen('junkvariable'))=='junkvariable')
	  {unset($assign[$key]);}
	  else
	  {$tans=(round($value,$this->accuracy));if($tans==0){$tans=0;};$assign[$key]=$tans;}
	 }
	 ksort($assign);
	 return($assign);
	}
	}
?>