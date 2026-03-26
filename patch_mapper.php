<?php
$filepath = 'app/Modules/AdminKernel/Domain/Security/PermissionMapperV2.php';
$content = file_get_contents($filepath);
$content = str_replace("'admins.profile.edit.view' => 'admins.profile.edit',\n", "", $content);
$content = str_replace("        'admins.profile.edit.view' => 'admins.profile.edit',\r\n", "", $content);
file_put_contents($filepath, $content);
