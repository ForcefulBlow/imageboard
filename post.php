<?php 
 //ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
    function checkCaptcha(){
        return true; //for future 
    }
    function getCaptcha(){
        $im = imagecreate(150, 50);
        // White background and blue text
        $bg = imagecolorallocate($im, 255, 255, 255);
        $textcolor = imagecolorallocate($im, 0, 0, 255);
        imagestring($im, 5, 0, 0, 'No captcha yet!', $textcolor);
        header('Content-type: image/png');
        imagepng($im);
        imagedestroy($im);
        die;
    }

    function getPostText(){
        // limit chars (i.e. tell user to fuck off with his text of wall)
        // htmlspecialchars
        // striptags
        // br
        // return it

        if (strlen($_POST["upost"]) > 15000){
            echo "Fuck off with wall of text, we can't read";
            die;
        }else{
            $result=htmlspecialchars($_POST["upost"]);
            $result=strip_tags($result);
            $result=str_replace("\n", "<br>", $result);
            if (substr_count($result, "<br>")>35){
                echo "Fuck off with your reddit spacing, we can't read this";
                die;
            }
            return $result;
        }

    }
    
    function getPostName(){
        if (strlen($_POST["uname"]) > 128){
            echo "Fuck off u dont have name dis long";
            die;
        }else{
            $result=htmlspecialchars($_POST["uname"]);
            $result=strip_tags($result);
            return $result;
        }

    }
    function uploadImage(){

    $check=getimagesize($_FILES["ufile"]["tmp_name"]);
    $imageFileType=strtolower(pathinfo($_FILES["ufile"]["name"], PATHINFO_EXTENSION));
        if($imageFileType!="jpg" && $imageFileType!="png" && $imageFileType!="jpeg" && $imageFileType!="gif" && $imageFileType!="webp" && $imageFileType!="jfif" && $_FILES["postFile"]["name"]!=""){
        return FALSE;
    }
    if ($check && ($check[0]*$check[1]<3500*3500) &&  $_FILES["ufile"]["size"] < 1024*1024*5 ){
        $name = md5_file($_FILES['ufile']['tmp_name']);
        move_uploaded_file($_FILES["ufile"]["tmp_name"], "img/".$name.".".$imageFileType);
        try{
            $new_image=imagecreatetruecolor(150,150);
            switch($imageFileType){
                case "jpg":
                case "jpeg":
                case "jfif":
                      $old_image = imagecreatefromjpeg("img/".$name.".".$imageFileType);
                break;
                case "png":
                      $old_image = imagecreatefrompng("img/".$name.".".$imageFileType);
                break;

                case "gif":
                      $old_image = imagecreatefromgif("img/".$name.".".$imageFileType);
                break;
                case "webp":
                      $old_image = imagecreatefromwebp("img/".$name.".".$imageFileType);
                break;
            }
            imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, 150, 150, $check[0], $check[1]);
            imagejpeg($new_image, "thmb/".$name.".jpeg");
             imagedestroy($old_image);
  imagedestroy($new_image);
                $out["img"]="img/".$name.".".$imageFileType;
                $out["thmb"]="thmb/".$name.".jpeg";
            return  $out;
        }catch (Exception $e) {
            return false;
        }



    }
    return false;
    }
    function remakeIndex(){
        $files = glob('threads/*');
            usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
        });
        $output='
<center>
    <form action="./post.php?newthread=yes" method="post" enctype="multipart/form-data">
        <input type="text" name="uname" value="Anonymous" style="width:80%"> <br>
        <textarea name="upost" placeholder="Ur post here sir" style="width:80%; height:150px;"></textarea> <br>
        <input type="file" name="ufile"><br>
        <img src="./post.php?captcha=yes"><br>
        <input type="text" name="ucaptcha"><br>
        <input type="submit">
    </form><br> <h2> Do not forget to F5 the thread </h2> 
</center>  <hr> <br> ';
        foreach(  $files as $i){
            $temp = array();
            preg_match('/<!-- OP -->(.*?)<!-- PO -->/', file_get_contents($i),$temp);
            $output .= $temp[1];
        }

        file_put_contents("threads.html", $output, LOCK_EX);
    }

    if($_GET["captcha"]=="yes"){
            getCaptcha();
    }else if ($_GET["newthread"]=="yes"){
        $threadname=md5(microtime());
        $postform='<div><center><a href="/threads.html"><h2>catalog</h2></a>
    <form action="/post.php?reply='.$threadname.'" method="post" enctype="multipart/form-data" >
        <input type="text" name="uname" value="Anonymous" style="width:80%"> <br>
        <textarea name="upost" placeholder="Ur post here sir" style="width:80%; height:150px;"></textarea> <br>
        <input type="file" name="ufile"><br>
        <img src="/post.php?captcha=yes"><br>
        <input type="text" name="ucaptcha"><br>
        <input type="submit">
    </form> <br> <h2> Do not forget to F5 the thread </h2>  <hr> 
</center></div>';
        $uname=getPostName();
        $utext=getPostText();


        if (!($i=uploadImage())){
            echo "um, u forgot image m8";
            die;
        }
        $img=$i["img"];
        $thmb=$i["thmb"];
        $newThread='<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">'."<!-- OP --> <div><span><b><a href=\"/threads/$threadname.html\"> OP $uname </a> </b></span><br><span> <a href='/$img'><img src='/$thmb' align='left'></a> $utext</span>  </div> <BR CLEAR='left' /> <hr> <!-- PO --> $postform";

        file_put_contents("threads/$threadname.html", $newThread, LOCK_EX);
        remakeIndex();
           $rand=random_int( 1, 9999999);
        echo "Gread success! your browser supports redirect, we don't so you go to <a href='/threads/$threadname.html?rand=$rand'>here </a>";
    }else if (isset($_GET["reply"])){
        $threadname=$_GET["reply"];
        $uname=getPostName();
        $utext=getPostText();
        if(file_exists("threads/$threadname.html") && ctype_alnum($threadname)){
            //allgood
        }else{  
            echo "Thread doesnt exist";
            die;
        }

        if (!($i=uploadImage())){
             $newThread=" <div><span><b>". substr(md5(time()),0,7) . " $uname  </b></span><br><span> $utext</span>  </div> <BR CLEAR='left' /> <hr> ";

        }else{
        $img=$i["img"];
        $thmb=$i["thmb"];
        $newThread=" <div><span><b>". substr(md5(time()),0,7) . " $uname  </b></span><br><span> <a href='/$img'><img src='/$thmb' align='left'></a> $utext</span>  </div> <BR CLEAR='left' /> <hr> ";
        }
        file_put_contents("threads/$threadname.html", $newThread, LOCK_EX|FILE_APPEND);
        remakeIndex();
        $rand=random_int( 1, 99999999);
        echo "Gread success! your browser supports redirect, we don't so you go to <a href='/threads/$threadname.html?randid=$rand'>here </a>";

    }else{
        echo "wtf ar u doing man, u no go here, much danger, one more time and your balls will die from radiation";
    }

?>