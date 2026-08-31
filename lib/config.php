<?php
class db{
	// public $host = "contentengine.coopymuhvosy.us-west-2.rds.amazonaws.com";
	// public $user = "sqladmin";
	// public $password = 'gZ4f$Y&5';
	// public $database = "think_tank";
	// public $connect;

	public $host = "localhost";
	public $user = "root";
	public $password = '';
	public $database = "think_tank";
	public $connect;
	
	function __construct() {
		$this->connect = mysqli_connect($this->host,$this->user,$this->password,$this->database);
		mysqli_set_charset($this->connect , 'utf8');
		mysqli_query($this->connect,'SET NAMES utf8mb4');
   }

   function get_rss_info($rss_id = 0){
		$query = "SELECT * FROM wh_cron_job_rss_link WHERE rss_id='$rss_id'";
		$result = mysqli_query($this->connect, $query) or die(mysqli_error($this->connect));;
		$no_rows = mysqli_num_rows($result);
		if($no_rows >0){
			return mysqli_fetch_assoc($result);;
		}else{
			return false;
		}
	}
   
	function feed_exists($title,$guid,$rss_id){
		if($title === "" || $guid === "" ){
			//reutrn true to ignore this feed because its title or guid is empty
			return true;
		}
		$guid = $this->escape($guid);
		$query = "SELECT * FROM wh_rss_content WHERE rss_id = $rss_id AND guid='$guid'";
		$result = mysqli_query($this->connect, $query) or die(mysqli_error($this->connect));;
		$no_rows = mysqli_num_rows($result);
		if($no_rows >0){
			return true;
		}else{
			return false;
		}
	}
	
	function escape($val){
		return mysqli_real_escape_string($this->connect,$val);
	}
	
	function insert_into_db($rss_id,$title,$description,$content,$guid,$pubDate,$issue_date,$category,$link,$author,$comments,$wfwCommentRss,$slashComments){
		// ignore posts with no content
		if (empty($content)) {
			return;
		}
		$query = "insert into wh_rss_content SET rss_id='$rss_id', title='$title',description='$description',content='$content',guid='$guid',pub_date='$issue_date',pubDate='$pubDate',category='$category',link='$link',author='$author',comments='$comments',wfw_commentrss='$wfwCommentRss',slash_comments='$slashComments'";
		
		$data = mysqli_query($this->connect, $query) or die(mysqli_error($this->connect));
		if($data){
			return true;
		}else{
			return false;
		}
	}

	function get_feeds(){
		$query = "SELECT * FROM wh_rss_content WHERE content LIKE '%<script%' ORDER BY `rss_content_id`  DESC";
		$result = mysqli_query($this->connect, $query) or die(mysqli_error($this->connect));;
		$no_rows = mysqli_num_rows($result);
		if($no_rows >0){
			$result = mysqli_fetch_all($result,MYSQLI_ASSOC);
			return $result;
		}else{
			echo "no-data";
		}
	}

	function get_feeds_by_guid($id){
		$query = "SELECT * FROM wh_rss_content WHERE guid='$id'";
		$result = mysqli_query($this->connect, $query) or die(mysqli_error($this->connect));;
		$no_rows = mysqli_num_rows($result);
		if($no_rows >0){
			$result = mysqli_fetch_all($result,MYSQLI_ASSOC);
			return $result;
		}else{
			echo "no-data";
		}
	}

	function update_feed($id,$content){
		$query = "update wh_rss_content set content = '$content' WHERE rss_content_id='$id' ";
		$result = mysqli_query($this->connect, $query) or die(mysqli_error($this->connect));;
		$no_rows = mysqli_num_rows($result);
		if($no_rows >0){
			$result = mysqli_fetch_all($result,MYSQLI_ASSOC);
			return $result;
		}else{
			echo "no-data";
		}
	}

	function get_feeds_vr_string($string){
		$query = "SELECT * FROM wh_rss_content WHERE title like '%$string%' or `description` like '%$string%' or content like '%$string%'";
		$result = mysqli_query($this->connect, $query) or die(mysqli_error($this->connect));;
		$no_rows = mysqli_num_rows($result);
		if($no_rows >0){
			$result = mysqli_fetch_all($result,MYSQLI_ASSOC);
			return $result;
		}else{
			echo "no-data";
		}
	}




}
$db = new db();
?>