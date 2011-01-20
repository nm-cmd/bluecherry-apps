<?php DEFINE('INDVR', true);

#lib
include("../lib/lib.php");  #common functions


#auth check
$current_user = new DVRUser();
$current_user->CheckStatus();
$current_user->StatusAction('viewer');
#/auth check
	
	Class LocalMonitor extends DVRData{
	public $data;
	public $card;

	public function __construct(){
		$this->data = false;
		if (!isset($_GET['id']))
			return;
		$id = intval($_GET['id']);
		$db = DVRDatabase::getInstance();
		$this->data = $db->DBFetchAll("SELECT * FROM Devices WHERE id='$id'");
	}
}

$monitor = new LocalMonitor;

if ($monitor->data == false) {
	print "No such device";
	exit;
}

$boundary = "myboundary";
$dev = $monitor->data[0]['device'];
$driver = $monitor->data[0]['driver'];

header("Cache-Control: no-cache");
header("Cache-Control: private");
header("Pragma: no-cache");

session_write_close();

$bch = bc_handle_get($dev, $driver);
if ($bch == false) {
	print "Failed to open $dev";
	exit;
}

if (isset($_GET['multipart']))
	$multi = true;
else
	$multi = false;

# Check to see if this device has a URL to get the MJPEG
$url = bc_get_mjpeg_url($bch);
if (!$url) {
	# Nope, so try to start up the local device
	if (bc_set_mjpeg($bch) == false) {
		print "Failed to set MJPEG on $dev";
		exit;
	}

	if (bc_handle_start($bch) == false) {
		print "Faled to start $dev";
		exit;
	}
} else {
	$multi = true;
}

if ($multi) {
        header("Content-type: multipart/x-mixed-replace; boundary=$boundary");
        print "\r\n--$boundary\r\n";
} else {
	header("Content-type: image/jpeg");
}

function print_image() {
	global $multi, $bch, $boundary;

	if (bc_buf_get($bch) == false)
		exit;

	if ($multi) {
		print "Content-type: image/jpeg\r\n";
		print "Content-size: " . bc_buf_size($bch) . "\r\n\r\n";
	}

	print bc_buf_data($bch);

	if ($multi)
		print "\r\n--$boundary\r\n";
}

# For multi, let's do some weird stuff
if ($multi) {
	set_time_limit(0);
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++)
		ob_end_flush();
	ob_implicit_flush(1);
}

# For URLs, we just pass through to curl
if ($url) {
	bc_handle_free($bch);
	bc_db_close();
	passthru("curl -s $url");
	exit;
}

do {
	print_image();
} while($multi);

bc_handle_free($bch);
?>
