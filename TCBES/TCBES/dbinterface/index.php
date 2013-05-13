<?php
  require_once('_DBEHSP.php');
  $dbio = new DBEHSP();
  $dbio->debug = true;
  $dbio->set_xsl_directory_path('xsl/');
  $dbio->request_handler();
?>
<html>
  <head>
    <title>Test Harness</title>
	<style>
     @import url('layout.css');
	</style>
	<script src="jquery.js" type="text/javascript"></script>
	<script>
	  $(document).ready(function(){
/*AJAXデータチェック*////////////////////////////////////////////////////////////////
	    var typecount = new Array();
		$("div.interface form input").live("keyup",function(event){
		  var target_input = "div.interface form input#"+$(this).attr("id");
		  var target_input_id = $(this).attr("id");
		  var target_input_invalid = "invalid_"+target_input_id;
		  /*文字数字以外のキー操作は除外、スペースはカウント*/
		  if(event.keyCode>45||event.keyCode==32)  
		  {
		      typecount[$(this).attr("id")] = $(this).val().length;
		  }
		  /*3文字以上入力されたらチェック開始*/
		  if(typecount[$(this).attr("id")]>=3)  
		  {
		    $.post(
		    '_ajax.php', 
		    {
		      ajax_type:"validation",
		      fieldid: $(this).attr('id'),
			  fieldval: $(this).val()
	        }, 
		    function(data)
		    {
			  /*show validation error*/
		      if(data.length>0)
			  {
			    if($("span#"+target_input_invalid).length>0)  /*すでにエラーが表示されていれば入れ替え*/
				{
				  $("span#"+target_input_invalid).replaceWith("<span class=\"invalid\" id=\""+target_input_invalid+"\">"+data+"</span>");
				}
				else
				{
			      $(target_input).after("<span class=\"invalid\" id=\""+target_input_invalid+"\">"+data+"</span>");
				}
			  }
		    });
		  }
		});
/*AJAXデータチェックここまで*////////////////////////////////////////////////////////////////

		/*New Entryが選択されたら新規項目を挿入するインターフェースを呼び出す。*/
        $("select option.newentry").live("click", function(event){
		  $.post(
		  '_ajax.php', 
		  {
		    ajax_type:"navigation",
		    target: $(this).val().replace("New Entry in ",""),
            dropbread:$('div.interface form').attr('id')
	      }, 
		  /*
		  //要改善
		  //この置換は不可変更結束、解決策としてはプリフィックスを定義しテーブル名と結合してIDとすること
		  //この場合、プリフィックスはコンフィグレーションファイルに定義し、先述のIDをoptionの値としPHPから
		  //JavaScriptに渡すようにすること。これにより置換は必要だが、可変更結束となる。
		  */
		  function(data) {
		    if(data.length>0)
			{
			  $("div#ui div.interface").replaceWith(data);
			  $("ul.warnings").remove();
			}
		  });
	    });
		/*イレギュラー呼び出し終わり*/
		
		$("ul#interface_navi li a").live("click", function(event){
           event.preventDefault();
		  		  $.post(
		  '_ajax.php', 
		  {
		    ajax_type:"navigation",
		    target: $(this).attr("href"),
			pickbread:"yes"
			/*戻るだけ(ナビゲーション履歴はスタックで実装、AJAX部ではPushとPopのみコアで制御)*/
	      }, 
		  function(data) {
		    if(data.length>0)
			{
			  $("div#ui div.interface").replaceWith(data);
			  $("ul.warnings").remove();
			}
		  });
		});
	  });
	  
	  /*再検討*/
	  $('ul#warnings li a').live("click", function(event){
	     $($(this).attr("href")).attr("class","required");
	  });
	  
	  $('div#ui div.interface input').live("click", function(event){
	     $(this).attr("class","");
	  });
	</script>
  </head>
  <body>
    
<?php
	  if($dbio->is_login())
	  {
?>
    <div id="account_menu">
<?php
	    $dbio->userInfo();
		
?>
    </div>
    <h1>DB_EHSP INTERFACE</h1>
	<div id="ui">
<?php
        /*if($dbio->origin_interface)
		{
	      $dbio->getInterface($dbio->origin_interface);
		}
		else
		{
		*/
		  $dbio->getInterface();
		//}
?>
     </div>
<?php
      }
	  else
	  {
        $dbio->getLogin();
	  }
?>
  </body>
</html>
