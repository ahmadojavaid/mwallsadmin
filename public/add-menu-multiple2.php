<?php
  include_once('includes/connect_database.php'); 
?>

<?php

error_reporting(E_ALL & ~E_NOTICE);
@ini_set('post_max_size', '64M');
@ini_set('upload_max_filesize', '64M');

// database constants
define('DB_DRIVER', 'mysql');
define('DB_SERVER', $host);
define('DB_SERVER_USERNAME', $user);
define('DB_SERVER_PASSWORD', $pass);
define('DB_DATABASE', $database);

$dboptions = array(
    PDO::ATTR_PERSISTENT => FALSE,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

try {
  $DB = new PDO(DB_DRIVER . ':host=' . DB_SERVER . ';dbname=' . DB_DATABASE, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, $dboptions);
} catch (Exception $ex) {
  echo $ex->getMessage();
  die;
}

if (isset($_POST["sub2"])) {

  $category_name = $_POST['category_name'];
  // include resized library
  require_once('./php-image-magician/php_image_magician.php');
  $msg = "";
  $valid_image_check = array("image/gif", "image/jpeg", "image/jpg", "image/png", "image/bmp");
  if (count($_FILES["user_files"]) > 0) {
    $folderName = "upload/";

    $sql = "INSERT INTO tbl_gallery (cat_id, image) VALUES ($category_name, :img)";
    $stmt = $DB->prepare($sql);

    for ($i = 0; $i < count($_FILES["user_files"]["name"]); $i++) {

      if ($_FILES["user_files"]["name"][$i] <> "") {

        $image_mime = strtolower(image_type_to_mime_type(exif_imagetype($_FILES["user_files"]["tmp_name"][$i])));
        // if valid image type then upload
        if (in_array($image_mime, $valid_image_check)) {

          $ext = explode("/", strtolower($image_mime));
          $ext = strtolower(end($ext));
          $filename = rand(10000, 990000) . '_' . time() . '.' . $ext;
          $filepath = $folderName . $filename;

          if (!move_uploaded_file($_FILES["user_files"]["tmp_name"][$i], $filepath)) {
            $emsg .= "Failed to upload <strong>" . $_FILES["user_files"]["name"][$i] . "</strong>. <br>";
            $counter++;
          } else {
            $smsg .= "<strong>" . $_FILES["user_files"]["name"][$i] . "</strong> uploaded successfully. <br>";

            $magicianObj = new imageLib($filepath);
            $magicianObj->resizeImage(150, 150);
            $magicianObj->saveImage($folderName . 'thumbs/' . $filename, 100);

            /*             * ****** insert into database starts ******** */
            try {
              $stmt->bindValue(":img", $filename);
              $stmt->execute();
              $result = $stmt->rowCount();
              if ($result > 0) {
                // file uplaoded successfully.
              } else {
                // failed to insert into database.
              }
            } catch (Exception $ex) {
              $emsg .= "<strong>" . $ex->getMessage() . "</strong>. <br>";
            }
            /*             * ****** insert into database ends ******** */
          }
        } else {
          $emsg .= "<strong>" . $_FILES["user_files"]["name"][$i] . "</strong> not a valid image. <br>";
        }
      }
    }


    $msg .= (strlen($smsg) > 0) ? successMessage($smsg) : "";
    $msg .= (strlen($emsg) > 0) ? errorMessage($emsg) : "";
  } else {
    $msg = errorMessage("You must upload atleast one file");
  }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--iOS/android/handheld specific -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Upload multiple images create thumbnails and save path to database with php and mysql">
    <meta name="keywords" content="php, mysql, thumbnail,upload image, check mime type">
    <meta name="author" content="Shahrukh Khan">

    <script src="css/js/jquery-1.9.0.min.js"></script>
    <script>
      $(document).ready(function() {
        $(".add").click(function() {
          $('<div><input class="files" name="user_files[]" type="file" ><span class="rem" ><br><a href="javascript:void(0);" >Remove</span><hr></div>').appendTo(".contents");

        });
        $('.contents').on('click', '.rem', function() {
          $(this).parent("div").remove();
        });

      });
    </script>
  </head>
  <body>
    <div id="container">
      <div id="body">
		<div class="col-md-12">
			<h1>Add Multiple Image <?php echo isset($error['add_menu']) ? $error['add_menu'] : '';?></h1>
			<hr />
		</div>
		
        <article>
          <?php echo $msg; ?>
          <div class="col-md-12">
              <form name="f2" action="" method="post" enctype="multipart/form-data">
                <fieldset>
				<div class="col-md-9">
				  <label>Category :</label>
          <?php
           include('includes/variables.php');

          $sql   = "SELECT cid, category_name FROM tbl_category ORDER BY category_name ASC";
                      $query = mysqli_query($connect, $sql);

                      echo "<select class='form-control' name='category_name' id='category_name'>";
                while ($row = mysqli_fetch_array($query)) {
                    echo "<option value='" . $row['cid'] ."'>" . $row['category_name'] ."</option>";
                }
                echo "</select>";

          ?>
				  <br>
				  
				  <label>Attach multiple Files :</label>
				  <input class="files" name="user_files[]" type="file" multiple="multiple" >
                  <div class="height10"></div>
						
                  <div class="contents"></div>
                  <div class="height10"></div>
				</div>  
				  <br>
				  
				  	<div class="col-md-3">
						<div class="panel panel-default">
							<div class="panel-heading">Action</div>
								<div class="panel-body">
									<input type="submit" class="btn btn-primary" name="sub2" value="Upload Images" />
								</div>
						</div>
					</div>
                </fieldset>
              </form>
          </div>
          <div class="height10"></div>
          <?php
          // fetch all records
          $sql = "SELECT * FROM tbl_gallery WHERE 1 ";
          try {
            $stmt = $DB->prepare($sql);
            $stmt->execute();
            $images = $stmt->fetchAll();
          } catch (Exception $ex) {
            echo $ex->getMessage();
          }
          ?>
          
          <div class="height10"></div>
        </article>
        <div class="height10"></div>
        
      </div>
    </div>

  </body>
</html>
<?php

function errorMessage($str) {
  return '<div style="width:50%; margin:0 auto; color:#000; margin-top:10px; text-align:center;">' . $str . '</div>';
}

function successMessage($str) {
  return '<div style="width:50%; margin:0 auto; color:#000; margin-top:10px; text-align:center;">' . $str . '</div>';
}
?>

<?php include_once('includes/close_database.php'); ?>