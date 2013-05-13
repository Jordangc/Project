<?php
class DBEHSP
{
  //file layout and navigation
  private $xsl_directory_path = './';
  private $xml_directory_path = 'xml/';
  private $login_page = 'index.php';
  private $logout_page = 'index.php';
  
  //mysql
  private $hostname_connDBEHSP = '10.42.42.245:58924';
  private $database_connDBEHSP = 'TCBES';
  private $username_connDBEHSP = 'TCBESadmin';
  private $password_connDBEHSP = 'TCBESpassword';
  
  //globals
  private $form_option_array = array(); //function formOption
  
  //housekeeping
  private $notice = array();
  public $debug = false;
  
  function __construct()
  {}

  function __destruct()
  {}

  private function array2xml($source_array,$relvar_name,$catalog_name='relvar', $element_name='attribute')
  {
    $xml =''; /*$xml = '<?xml version="1.0"?>';*/
	$xml .= '<'.$catalog_name.'>';
	$xml .= '<relvar_name>'.$relvar_name.'</relvar_name>';
	foreach($source_array as $record)
	{
	  $xml .= '<'.$element_name.'>';
	    foreach($record as $assoc_index=>$record_value)
		{
		  $xml .= '<'.$assoc_index.'>';
		  $xml .= $record_value;
		  $xml .= '</'.$assoc_index.'>';
		}
	  $xml .= '</'.$element_name.'>';
	}
	$xml .= '</'.$catalog_name.'>';
	

	$relvar = new simpleXMLElement($xml);
	foreach($relvar->attribute as $attribute)
	{
	  foreach($attribute->Field as $field)
	  {
	    $label = preg_split('/_/',$field);            //split form name/id and generate label name
		$label_name ='';
		foreach($label as $label_id=>$name)
		{
		    $label_name .= $name.' ';
	    }
		$label_name = substr($label_name,0,-1);
		$regexp = '/<Field> *'.$field.' *<\/Field>/';
		$xml = preg_replace($regexp,'<Field>'.$field.'</Field>."n".<label>'.$label_name.'</label>',$xml);
	  }
	}
	return $xml;
  }
  
  public function getInterface($relvar_name='',$prev_interface='',$next_interface='')
  {
    session_start();
    if($relvar_name=='')
	{
	  if(count($_SESSION['breadcrumb'])>0)
	  {
	    $relvar_name=trim($_SESSION['breadcrumb'][count($_SESSION['breadcrumb'])-1]);
      }
	}
	
	/*Progress bar
	if(isset($_SESSION['breadcrumb']))
	{
	  $num_form = count($_SESSION['breadcrumb']);
	  $breadcrumbs = '<div id="progress"><ol class="progress">';
	  $prev_bc='';
	  foreach(array_reverse($_SESSION['breadcrumb']) as $breadcrumb)
	  {
	    if($breadcrumb != $prev_bc)
		{
		  $breadcrumbs .= '<li>'.$breadcrumb.' ></li>';
		  $prev_bc = $breadcrumb;
		}
	  }
	  $breadcrumbs .= '<li>Finish!</li>';
	  $breadcrumbs .= '</ol></div>';
	  echo $breadcrumbs;
	}
	//Progress bar*/
	
    $this->getNotice(); //If there are any validation errors
    $xsl_file = 'c_form.xsl';
	
	if($relvar_name!='')
	{
 	    $array_result = array();
	    $connDBEHSP = mysql_connect($this->hostname_connDBEHSP, $this->username_connDBEHSP, $this->password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
        mysql_select_db($this->database_connDBEHSP, $connDBEHSP);
        $query_string = sprintf("describe %s", $relvar_name);
        $raw_result = mysql_query($query_string, $connDBEHSP) or die(mysql_error());
        $num_raw_result = mysql_num_rows($raw_result);

	    while($record = mysql_fetch_assoc($raw_result))
	    {
	      //データ�?リデーション用
		  $_SESSION['validation'][$relvar_name.'_'.$record['Field']]['type'] = preg_replace('/[0-9\(\)]*/','',$record['Type']); //data type
		  $_SESSION['validation'][$relvar_name.'_'.$record['Field']]['length'] = preg_replace('/[^0-9]*/','',$record['Type']);  //data length
		  
		  //フォーム出力用
		  array_push($array_result, $record);                                        //Associative Array
	    }
		
		//print_r($_SESSION['validation']);  //remove
		
	    $xsl = new DOMDocument();
	    $xml = new DOMDocument();
	
	    $xml -> /*load('test_data/test.xml');*/loadXML($this->array2xml($array_result,$relvar_name));                      //Convert array to XML
	
	    if(file_exists($this->xsl_directory_path.$xsl_file))
	    {
	      $xsl->/*load('test_data/test.xsl'); */load($this->xsl_directory_path.$xsl_file);
	      $xslt = new XSLTProcessor();
	      $xslt->importStylesheet($xsl);
	      $form = $xslt->transformToXML($xml);
	    }
	    else
	    {
	      $form='XSL file is not present:'.$this->xsl_directory_path.$xsl_file;
	    }
		
	    //$form = str_replace('[[HANDLER]]',$_SERVER['PHP_SELF'],$form);  //landing page
	    $form = str_replace('[[HANDLER]]','index.php',$form);  //landing page
	    $form = preg_replace('/(<\? *xml)+( *[A-Za-z0-9]* *= *"* *[A-Za-z0-9.]* *"* *)+(\?>)+/','',$form);            //remove xml declaration
		$this->formOption($relvar_name);
		//$form .= $this->form_option_array[$relvar_name];
		//print_r($this->form_option_array[$relvar_name]);
		//Form option specified with XML applied
        foreach($this->form_option_array as $target=>$elements)
		{
		  foreach($elements as $element_id=>$element_value)  //DOM identified by "relvarname_attributename"
		  {
		    $element_value = preg_replace('/(<\? *xml)+( *[A-Za-z0-9]* *= *"* *[A-Za-z0-9.]* *"* *)+(\?>)+/','',$element_value); //XML宣言除去
		    $regexp = '/< *input( *[A-Za-z0-9]* *= *"*[A-Za-z0-9_]*"*)*( *id *= *"*'.trim($target).'_'.trim($element_id).'"*)+( *[A-Za-z0-9]* *= *"*[A-Za-z0-9_\[\]]*"*)* *\/*>/'; //slash...
		    $form = preg_replace($regexp,$element_value,$form);
//if($this->debug){ echo $regexp;}
	      }
		}
		//イレギュラーフロー　AJAX用�?backリンクをインターフェース内�?�用�?�?�る；origin_interface�?�値�?�スタック�?�トップ（�?列�?�ボトム）�?��?�一�?�らbackリンク�?�用�?�?��?��?�
		if($prev_interface!='')
		{
		  if($_SESSION['breadcrumb'][count($_SESSION['breadcrumb'])-1]!=$_SESSION['origin_interface'])
		  {
		    $form = str_replace('[[prev_form]]','<a href="'.$prev_interface.'">Back</a>',$form);
		  }
		  else
		  {
		    $form = str_replace('[[prev_form]]','&nbsp;',$form);
		  }
	    }
		else
		{
		  $form = str_replace('[[prev_form]]','&nbsp;',$form);
		}
		
		if($next_interface)//for future extension
		{
		  $form = str_replace('[[next_form]]','<a href="'.$next_interface.'">Next</a>',$form);
	    }
		else
		{
		  $form = str_replace('[[next_form]]','&nbsp;',$form);
		}
		
		if(count($_SESSION['autofill'])>0)
		{
		  foreach($_SESSION['autofill'] as $field_id => $field_val)  //Auto fill the form from previously entered value
		  {
		   $form = str_replace('value="[['.$field_id.']]"','value="'.$field_val.'"',$form);
		  }
		  
		}
        $form = preg_replace('/value="\[\[[a-zA-Z0-9_]*\]\]"/','',$form);
		echo $form,	print_r($_SESSION['breadcrumb']),'<br /><br />',print_r($_SESSION['autofill']),'<br /><br />';  //debug
		
	  mysql_close($connDBEHSP);
	}
	else
	{
	  echo '<div class="interface"><p>The database has been updated.</p></div>';
	  //commit sql
	}
  }
  
  private function dynamicSQL($post_array)
  {
	
	//SECURITY: check if the table actually exists
	$connDBEHSP = mysql_connect($this->hostname_connDBEHSP, $this->username_connDBEHSP, $this->password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
    mysql_select_db($this->database_connDBEHSP, $connDBEHSP);
	  
    $query_string_ds = sprintf('show tables'); /*List of tables*/
	$raw_result_ds = mysql_query($query_string_ds, $connDBEHSP) or die();
	
	$valid_table=false;
	while($table = mysql_fetch_assoc($raw_result_ds)) /*Go through the list of tables to see if the target exists*/
	{
	  if($post_array['interface']==$table['Tables_in_ehsp'])
	  {
	    $valid_table = true;
	  }
	}
   
    $argid = array();
	$argval = array();
	if($valid_table)
	{
	  foreach($post_array as $formid=>$frmval)
	  {
	    if(!preg_match('/req_[a-zA-Z0-9_]*/',$formid)) /*Exclude Hidden Input*/
	    {
	      if($formid!='interface'&&$formid!='submit') /*Exclude container form and submit button*/
		  {
            $crop_length = strlen($post_array['interface'])+1;  //prefix "relvarname_"
	        array_push($argid,substr($formid,$crop_length));
			array_push($argval,'"'.$frmval.'"');
		  }
	    }
	  }
	  
	  try
	  {
	  $fields = implode(',',$argid);
	  $values = implode(',',$argval);
	  $create_sql = sprintf('insert into %s (%s) values (%s);',$post_array['interface'],$fields, $values);
	  $connDBEHSP = mysql_connect($this->hostname_connDBEHSP, $this->username_connDBEHSP, 
	                              $this->password_connDBEHSP) 
								  or trigger_error(mysql_error(),E_USER_ERROR);				  
      mysql_select_db($this->database_connDBEHSP, $connDBEHSP);

	  $raw_result = mysql_query($create_sql, $connDBEHSP) or die('<ul id="interface_navi"><li>MySQL Error Messagge: '.mysql_error($connDBEHSP).' - <a href="./">Go back</a></li></ul>');	  
	  }
	  catch (Exception $e)
	  {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
      }
	  return true;
	}
	else
	{
	  return false;//$post_array['interface'].': DNE'; /*Table did not exist*/
	}
  }
  
  public function set_xsl_directory_path($directory_path)
  {
    if(file_exists($directory_path))
	{
      $this->xsl_directory_path = $directory_path;
	  return true;
	}
	else
	{
	  return false;
	}
  }
  
  public function auth_login($user_name, $passwd)
  {

	  $connDBEHSP = mysql_connect($this->hostname_connDBEHSP, $this->username_connDBEHSP, $this->password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
      mysql_select_db($this->database_connDBEHSP, $connDBEHSP);
      $query_string_al = sprintf('select abbr3_collector, first_name, last_name, email, user_name from collector where user_name = %s and passwd = %s;', $user_name, $passwd);
	  $raw_result_al = mysql_query($query_string_al, $connDBEHSP) 
	  or die(($this->debug)?mysql_error():'<html><head><title>Error Page</title></head><body><p><h1>Bad Request</h1><a href="index.php">Go back.</a></p></body></html>'); //Query execution
	  $num_raw_result = mysql_num_rows($raw_result_al);
	//session_start();
    if($num_raw_result==1)  //think twice
    {
	  $log_user = mysql_fetch_assoc($raw_result_al);
	  session_start();
	  $_SESSION['uid'] = $log_user['abbr3_collector'];
	  $_SESSION['user_first'] = $log_user['first_name'];
      $_SESSION['user_last'] = $log_user['last_name'];	
      $_SESSION['user_email'] = $log_user['email'];
	  
	  mysql_close($connDBEHSP);
	  return true;
	}
	else
	{
	  mysql_close($connDBEHSP);
	  return false;
	}
  }
  
  public function auth_logout()
  {
    session_start();
    session_unset();
    session_destroy();
	header($login_page);
  }
  
  public function userInfo()
  {
    if($_SESSION['uid'])
	{
	  $current_user = $_SESSION['user_first'].' '.$_SESSION['user_last'].'  (Not '.$_SESSION['user_first'].'? <form style="display:inline;" action="'.basename($_SERVER['PHP_SELF']).'" method="post" name="logout_form" id="logout_form"><input type="submit" name="submit" id="submit" value="Logout" /></form>)';
	}
	else
	{
	  $current_user = '<a href='.$this->login_page.'>login</a>';
	}
	echo 'You are logged in as ', $current_user;
  }
  
  public function is_login()
  {
    return ($_SESSION['uid'])? true:false;
  }
  
  public function request_handler() //always called first
  {
    /*INITIALIZATION BEGIN*////////////////////////////////////////
    session_start();
	if($_SESSION['uid']=='')
	{
	  $_SESSION['breadcrumb'] = array();
	  $_SESSION['origin_interface']='';
	  $_SESSION['ajaxflow']='no';
	}
    //error index: input error
    
	/*if(isset)($_COOKIE)
	{
	  foreach($_COOKIE as $cookie_name=>$cookie_value)
	  {
	    
	  }
	}*/
	//Cナビゲーション,�?��?��?�セッション変数�?�読�?�込�?�
	$cconfig_file = 'xml/create.xml';
	$orderCreate = new DOMDocument();
    if(file_exists($cconfig_file)&&count($_SESSION['breadcrumb'])==0)
	{
	  $orderCreate->load($cconfig_file);
	  $create = new simpleXMLElement($orderCreate->saveXML());
	  foreach($create->interface as $interface)
	  {
	    array_push($_SESSION['breadcrumb'],trim((string)$interface[0]));       //スタック�?�フォーム�?�出�?�順番を記録
	  } 
	  $_SESSION['breadcrumb'] = array_reverse($_SESSION['breadcrumb']);
	}
	/*INITIALIZATION END*////////////////////////////////////////

    if(isset($_POST))//サブミッション�?��?��?��?�ら
	{
	  if(isset($_POST['submit']))
	  {
	    switch($_POST['submit'])                                          //Login handling
	    {
		  case 'Login': 
		               if(!($this->auth_login($this->quote($_POST['user_name']),$this->quote($_POST['password']))))
		               {
					     $this->notice['Login Failure'] = 'Wrong user name and/or password.';
					   }
		             break;
		  case 'Logout': $this->auth_logout();
		             break;
	      default:
		             break;
	    }
		

		
		if($_POST['submit']=='submit')
		{//必須項目�?�無視�?�ら
		  $this->notice=array();                                          //エラー�?ッファ�?期化
		  //必須項目�?ェック
		  $crop_length = strlen($_POST['interface'])+1;  //prefix "relvarname_"
		  foreach($_POST as $form_element=>$element_value)                 //form_element = name
		  {
		    //Auto input inheritance
		    if(preg_match('/req_[a-zA-Z0-9_]*/',$form_element)==0)  //exclude hidden field for required field
			{
			  $attrib_name = substr($form_element,$crop_length);  //extract pure attribute name
		      $_SESSION['autofill'][$attrib_name] = $element_value;         //自動入力用
			}
			/*�?リデーション*/
		    if($_POST['req_'.$form_element] == 1)                          //if required field is empty
		    {
		      if($element_value == '') //think twice, unique etc...       空�?�場�?�
		  	  {
		 	    $label_name = $this->getHumanReadable($form_element);
			    $this->notice[$form_element] = '"<em class="warning">'.$label_name.'</em>" is required.'.' <a href="#'.$form_element.'">Check</a> ';
			  }
			  else if(preg_match('/New Entry in/',$element_value))   //プレイスホルダー�?�場�?��?�?検討
			  {
			    $label_name = $this->getHumanReadable($form_element);
			    $this->notice[$form_element] = '"<em class="warning">'.$label_name.'</em>" is required.'.' <a href="#'.$form_element.'">Check</a> ';
			  }
		    }
		  }
		  if($_SESSION['breadcrumb'][count($_SESSION['breadcrumb'])-1]!=$_POST['interface'])
		  {
		    $this->bc_push($_POST['interface']);  //エラー時�?�戻り地点
	      }
		  if(count($this->notice)==0)//�?リデーションエラー�?��?��?�れ�?�
		  {
			$_SESSION['ajaxflow']='no';
		    if($this->dynamicSQL($_POST)) $this->bc_pop();
			//else echo
		  }
		}
	  }
	}
  }
  
  private function getHumanReadable($form_element)
  {
    $label = preg_split('/_/',$form_element);            //split form name/id and generate label name
			  $label_name ='';
			  foreach($label as $label_id=>$name)                  //トリミング
			  {
			    if($label_id>0)
				{
			      $label_name .= $name;
				  if(count($label) > ($label_id+1))
				  {
				    $label_name .= ' ';
				  }
				}
			  }
	return $label_name;
  }
  
  private function quote($text)
  {
    return 
     "'".
     htmlspecialchars((get_magic_quotes_gpc() ?
	 stripslashes($text) :
	 addslashes($text)))
	 ."'";
  }
  
  public function getNotice()
  {
    if($this->notice)
	{
      echo '<ul id="warnings">';
      foreach ($this->notice as $error_section=>$error_content)
	  {
        echo '<li>'.$error_content.'</li>';
	  }
	  echo '</ul>';
	  $this->notice = array();
	}
  }
  
  public function getLogin() //Login Form
  {
    echo '<p>Please sign in:</p>
		<form action="',basename($_SERVER['PHP_SELF']),'" method="POST" name="login_form" id="login_form">
        <label for="user_name">User Name</label>
        <input type="text" name="user_name" id="user_name" />
        <br />
        <label for="password">Password</label>
        <input type="password" name="password" id="password" />
        <br />
		<label for="persistentlogin">Remember Me</label>
		<input type="checkbox" name="persistentcookie" value="login">
		<br/>
        <label for="submit">Submit</label>
        <input type="submit" name="submit" id="submit" value="Login" />
        </form>';
	$this->getNotice();
  }
  
  private function formOption($relvar_name)                                     //Form detail controll xml
  {
    //error index: Form Option
	$form_option_xsl = 'o_select.xsl';
																				//Load XML
	$xml_path = $this->xml_directory_path.$relvar_name.'.xml';
	
	if(!file_exists($xml_path))
	{
	  $this->notice['Form Option'] = 'Specified XML file does not exist!';
	}
	else
	{
	  $this->notice = array(); //think twice..
	  $option_xml = new DomDocument;
	  $option_xml->load($xml_path);
	  if(!$option_xml)                                                          //File exists but empty
	  {
	    $this->notice['Form Option'] = 'Specified XML file is empty!';
	  }
	  else                                                                      //If XML successfully Loaded...
	  {
	    $option = new simpleXMLElement($option_xml->saveXML());
		if(trim($option->relvar) != $relvar_name)
		{
		  $this->notice['Form Option'] = 'Specified XML contains invalid elements!';
		}
	    //$option = simplexml_import_dom($option_xml);
		else
		{
		  foreach($option->action as $action)
	      {
	        $form_id = $action->attribute;
		    if($action->prefetch)													//Prepare prefetched dropdown
		    {
		      $prftch_relvar = (string)$action->prefetch->prftch_relvar;
		      $prftch_attribute = (string)$action->prefetch->prftch_attribute;
			  $prftched = (string)$action->attribute;
		  
		      $num_raw_result = 0;
		      $form = new DomDocument('1.0');
		  
		      $connDBEHSP = mysql_connect($this->hostname_connDBEHSP, $this->username_connDBEHSP, $this->password_connDBEHSP) or trigger_error(mysql_error(),E_USER_ERROR);
              mysql_select_db($this->database_connDBEHSP, $connDBEHSP);
              $query_string_fo = sprintf('select %s from %s;', $prftch_attribute, $prftch_relvar);
              $raw_result_fo = mysql_query($query_string_fo, $connDBEHSP) or die(mysql_error()); //Query execution
              $num_raw_result = mysql_num_rows($raw_result_fo);
		  
		      $prefetched = false;                                                     //to detect "select" is added
		      if($num_raw_result>0)                                                    //thtw, optional form elements
		      {
		        $prefetched = true;
		        $select = $form->appendChild($form->createElement('select')); //root
		        $element_name = $select->appendChild($form->createElement('element_name'));
			    $element_name->appendChild($form->createTextNode(trim($relvar_name).'_'.trim($prftch_attribute)));  //this form is for relvar_name, not prftch_relvar, prftch_relvar gives a list of values
		        while($option_element = mysql_fetch_assoc($raw_result_fo))			//Prepare prefetched dropdown
		        {
		          $dropdown = $select->appendChild($form->createElement('option'));
		          $dropdown->appendChild($form->createTextNode($option_element[trim($prftch_attribute)]));
		        }
		      }
	        }
			
	        $newentry = $action->newentry;
	        if($newentry->new_relvar)							//New entry might be needed
		    {
			    if(!$prefetched)
		        {
				  $form = new DomDocument('1.0');
			  	  $select = $form->appendChild($form->createElement('select')); //root
		          $element_name = $select->appendChild($form->createElement('element_name'));
			      $element_name->appendChild($form->createTextNode(trim($relvar_name).'_'.trim($prftch_attribute)));
			    }
		        $new_relvar = (string)$newentry->new_relvar;
		        $dropdown = $select->appendChild($form->createElement('option_new'));
				$dropdown->appendChild($form->createTextNode('New Entry in '.trim($new_relvar)));
		    }

		//$form->formatOutput = true;
            if($form != '')                                          //thtw: form is not empty
		    {
		      $xsl = new DOMDocument();
		  
              if(file_exists($this->xsl_directory_path.$form_option_xsl))
	          {
		        $xsl->load($this->xsl_directory_path.$form_option_xsl);
			    $xslt = new XSLTProcessor();
	            $xslt->importStylesheet($xsl);
	            $optional_form = $xslt->transformToXML($form);
			
		        $this->form_option_array[$relvar_name][$prftched] = $optional_form;//->saveXML();
	
		        //$this->form_option_array[$relvar_name][$new_relvar] = $optional_form;//->saveXML();

	          }
		      else
		      {
		        $this->notice['Form Option XSL'] = 'Specified XSL does not exist: '.$this->xsl_directory_path.$form_option_xsl;
		      }
		    }
	      }
		}
	  }
    }
  }
  
  public function ajax_handler()
  {
    session_start();
	if($_POST['ajax_type'])
	{
	  switch($_POST['ajax_type'])
	  {
	  case 'navigation':
	  /*フォームナビゲーション*/
	    if($_SESSION['ajaxflow']=='no')
        {
	      $_SESSION['ajaxflow']='yes';
	      if(isset($_POST['dropbread']))
          {
	        $_SESSION['origin_interface'] = $_POST['dropbread'];
          }
	    }
          //irreguler/AJAX flow
          if($_POST['dropbread'])
          {
		    if($_POST['dropbread']!=$_SESSION['breadcrumb'][count($_SESSION['breadcrumb'])-1])
			{
              $this->bc_push($_POST['dropbread']);//セッション変数�?�Push
			}
			else
			{
			  $this->bc_push($_POST['target']);//セッション変数�?�Push
			}
	        $_POST['current'] = $_POST['dropbread'];
          }
  
          //wayback flow
          if($_POST['pickbread']=='yes')
          {
            $_POST['current'] = $this->bc_pop();//セッション変数�?�らPop
          }
  
          //form generation
          echo $this->getInterface($_POST['target'],$_POST['current'],$_POST['next']);
	      /*,
	      '<em>CHECK: </em> CURRENT',$_SESSION['breadcrumb'][count($_SESSION['breadcrumb'])-1],
	      'ORIGIN: ',$_POST['current'],'<em>Ajax: </em>', 
	      print_r($_SESSION),'<br />';*/
		  break;
	  case 'validation':
	  /*フォーム�?リデーション*/
	     if($_SESSION['validation'][$_POST['fieldid']]['length']=='')
		 {
		   echo ' - OK';
		 }
		 else if(strlen($_POST['fieldval'])>$_SESSION['validation'][$_POST['fieldid']]['length'])
		 {
		   echo 'Input length too long!';
		 }
		 else
		 {
		   echo strlen($_POST['fieldval']),'/',$_SESSION['validation'][$_POST['fieldid']]['length'],' - OK';
		 }
	    break;
	   }
	}
  }
  
  public function bc_push($interface_name)
  {
    session_start();
    array_push($_SESSION['breadcrumb'],trim($interface_name));
  }
  
  public function bc_pop()
  {
    session_start();
	array_pop($_SESSION['breadcrumb']);
	$bread = $_SESSION['breadcrumb'][count($_SESSION['breadcrumb'])-1];
    return $bread;
  }
}
?>