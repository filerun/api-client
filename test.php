<?php
require __DIR__ . '/vendor/autoload.php';

$FileRun = new FileRun\API\Client([
	'url' => 'https://demo.filerun.co',
	'client_id' => 'FileRun0000000000000000000Mobile',
	'client_secret' => '0000000000000000NoSecret0000000000000000',
	'username' => 'admin',
	'password' => 'admin',
	'scope' => ['profile', 'list', 'upload', 'download', 'weblink', 'delete', 'share', 'admin', 'modify', 'metadata']
]);

//CONNECT
$rs = $FileRun->connect();
if (!$rs) {exit('Failed to connect: '.$FileRun->getError());}

echo 'Successfully connected<hr>';

//GET USER INFO
$userInfo = $FileRun->getUserInfo();
if (!$userInfo) {
	echo 'Failed to get user info: '.$FileRun->getError();
	exit();
}
echo 'Hello <b>'.$userInfo['name'].'</b>!<hr>';

//GET USER AVATAR
$rs = $FileRun->getAvatar();
if ($rs) {
	echo 'Profile image successfully downloaded:<br>';
	echo '<img src="data:image/png;base64,'.base64_encode($rs).'" width="80" /><hr>';
} else {
	exit('Failed to download profile image: '.$FileRun->getError());
}

//CREATE NEW FOLDER
$rs = $FileRun->createFolder(['path' => '/ROOT/HOME/', 'name' => 'API Test Folder']);
if ($rs && $rs['success']) {
	echo 'Folder "API Test Folder" successfully created in the user\'s home folder.<hr>';
} else {
	exit('Failed to create folder: '.$FileRun->getError());
}

//LIST FOLDER CONTENTS
$rs = $FileRun->getFolderList(['path' => '/ROOT/HOME', 'details' => ['uuid']]);
if ($rs && $rs['success']) {
	echo 'List contents of the user\'s home folder:<br>';
	echo '<div style="max-height:200px;padding:5px;overflow:auto;">';
	foreach($rs['data']['files'] as $item) {
		if ($item['filename'] == 'API Test Folder') {echo '*';}
		echo "&nbsp;&nbsp;".$item['filename'].'<br>';
	}
	echo '</div>';
	echo '<hr>';
} else {
	exit('Failed to retrieve list of files: '.$FileRun->getError());
}

//UPLOAD FILE
$data = 'This is the file contents. This is some unique data: '.time().'-'.rand();
$rs = $FileRun->uploadFile(['path' => '/ROOT/HOME/API Test Folder/MyUploadedFile.txt'], $data);
if ($rs && $rs['success']) {
	echo 'File "MyUploadedFile.txt" successfully uploaded inside "API Test Folder"<hr>';
} else {
	exit('Failed to upload file: '.$FileRun->getError());
}

//SEARCH FILES
$rs = $FileRun->searchFiles(['path' => '/ROOT/HOME', 'keyword' => 'MyUploaded']);
if ($rs && $rs['success']) {
	echo 'Searching home folder for keyword "MyUploaded":<br>';
	echo '<div style="max-height:200px;padding:5px;overflow:auto;">';
	foreach($rs['data']['files'] as $item) {
		if ($item['filename'] == 'MyUploadedFile.txt') {echo '*';}
		echo "&nbsp;&nbsp;".$item['path'].'<br>';
	}
	echo '</div><hr>';
} else {
	exit('Failed to retrieve search result: '.$FileRun->getError());
}

//RENAME FILE
$rs = $FileRun->rename(['path' => '/ROOT/HOME/API Test Folder/MyUploadedFile.txt', 'newName' => 'Renamed_MyUploadedFile.txt']);
if ($rs && $rs['success']) {
	echo '"MyUploadedFile.txt" successfully renamed to "Renamed_MyUploadedFile.txt".<hr>';
} else {
	exit('Failed to rename: '.$FileRun->getError());
}

$relativePath = '/ROOT/HOME/API Test Folder';
$fileName = 'Renamed_MyUploadedFile.txt';

//ADD STAR
$rs = $FileRun->star(['path' => $relativePath.'/'.$fileName]);
if ($rs && $rs['success']) {
	echo 'Star added to "'.$fileName.'"<hr>';
} else {
	exit('Failed to add star: '.$FileRun->getError());
}

//LIST STARRED FILES
$rs = $FileRun->getFolderList(['path' => '/STARRED', 'details' => ['uuid']]);
if ($rs && $rs['success']) {
	echo 'List starred files:<br>';
	echo '<div style="max-height:200px;padding:5px;overflow:auto;">';
	foreach($rs['data']['files'] as $item) {
		if ($item['filename'] == $fileName) {echo '*';}
		echo "&nbsp;&nbsp;".$item['path'].'<br>';
	}
	echo '</div>';
	echo '<hr>';
} else {
	exit('Failed to retrieve list of starred files: '.$FileRun->getError());
}

//REMOVE STAR
$rs = $FileRun->unstar(['path' => $relativePath.'/'.$fileName]);
if ($rs && $rs['success']) {
	echo 'Star removed from '.$fileName.'</a>';
	echo '<hr>';
} else {
	exit('Failed to remove star: '.$FileRun->getError());
}

//CREATE WEBLINK
$rs = $FileRun->weblink(['path' => $relativePath.'/'.$fileName]);
if ($rs && $rs['success']) {
	echo 'WebLink generated for '.$fileName.': <a href="'.$rs['data']['url'].'" target="_blank">'.$rs['data']['url'].'</a>';
	echo '<hr>';
} else {
	exit('Failed to get weblink: '.$FileRun->getError());
}

//GET METADATA
$rs = $FileRun->getMetadata(['path' => $relativePath.'/'.$fileName]);
if ($rs && $rs['success']) {
	echo 'Metadata for '.$fileName.':';
	echo '<div style="max-height:200px;padding:5px;overflow:auto;"><pre>';
	print_r($rs);
	echo '</pre></div><hr>';
} else {
	exit('Failed to get metadata: '.$FileRun->getError());
}

//DOWNLOAD FILE
$rs = $FileRun->downloadFile(['path' => $relativePath.'/'.$fileName]);
if ($rs) {
	echo 'Downloading file '.$fileName.':<br>';
	echo '<div style="border:1px solid silver;background-color:whitesmoke;padding:5px;">'.$rs.'</div><hr>';
} else {
	exit('Failed to download file: '.$FileRun->getError());
}

//DOWNLOAD THUMBNAIL
$rs = $FileRun->downloadThumbnail([
	'path' => $relativePath.'/'.$fileName,
	'width' => 400,
	'height' => 200,
	'g' => 'contain', //make sure its size fits in the above constrains
	'noCache' => true //do not save on the server
]);
if ($rs) {
	echo 'Thumbnail image successfully downloaded:<br>';
	echo '<img src="data:image/png;base64,'.base64_encode($rs).'" border="1" /><hr>';
} else {
	echo 'Failed to download thumbnail file: '.$FileRun->getError();
	echo '<hr>';
}


//SHARING FOLDER
$rs = $FileRun->share([
	'path' => $relativePath,
	'uid' => 1, //share with user ID 123
	//'gid' => 456, //share with group ID 456
	'anonymous' => 0,
	'upload' => 1,
	'download' => 1,
	'comment' => 1,
	'read_comments' => 1,
	'alter' => 0,
	'share' => 0,
	'alias' => 'My share'
]);
if ($rs && $rs['success']) {
	echo 'Folder successfully shared.<hr>';
} else {
	exit('Failed to share folder: '.$FileRun->getError());
}


//UNSHARING A FOLDER
$rs = $FileRun->unshare([
	'path' => $relativePath,
	'uid' => 1, //unshare with user ID 123
	//'gid' => 456, //unshare with group ID 456
]);
if ($rs && $rs['success']) {
	echo 'Folder successfully unshared.<hr>';
} else {
	exit('Failed to unshare folder: '.$FileRun->getError());
}

//DELETE FILE
$rs = $FileRun->delete(['path' => $relativePath.'/'.$fileName]);
if ($rs && $rs['success']) {
	echo 'File "'.$fileName.'" successfully moved to trash.<hr>';
} else {
	exit('Failed to move file to trash: '.$FileRun->getError());
}

//DELETE FOLDER
$rs = $FileRun->delete(['path' => $relativePath]);
if ($rs && $rs['success']) {
	echo 'Folder successfully moved to trash.<hr>';
} else {
	exit('Failed to move folder to trash: '.$FileRun->getError());
}