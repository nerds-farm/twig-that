<?php

if (!empty($_GET['action'])) {

    $dirs = wp_upload_dir();
    $basedir = str_replace('/', DIRECTORY_SEPARATOR, $dirs['basedir']) . DIRECTORY_SEPARATOR;
            
    switch ($_GET['action']) {
        case 'import':
            if (!empty($_FILES["zip"]["tmp_name"])) {
                //var_dump($_FILES); die();
                $target_file = $basedir . basename($_FILES["zip"]["name"]);
                $tmpdir = $basedir.'tmp';
                if (move_uploaded_file($_FILES["zip"]["tmp_name"], $target_file)) {
                    $zip = new \ZipArchive;
                    if ($zip->open($target_file) === TRUE) {
                        $zip->extractTo($tmpdir);
                        $zip->close();

                        $jsons = glob($tmpdir.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*.json');
                        foreach ($jsons as $json) {
                            //var_dump($json);
                            $jfolder = dirname($json);
                            //var_dump($jfolder);
                            $block = basename($jfolder);
                            //var_dump($block);
                            if ($block == 'src') { continue; }
                            $json_code = file_get_contents($json);
                            $args = json_decode($json_code, true);
                            //if (!empty($args['$schema'])) {
                                if (!empty($args['name'])) {
                                    //var_dump($args); die();
                                    // is a valid block
                                    list($domain, $block) = explode('/', $args['name'], 2);
                                    $dest = $this->get_ensure_blocks_dir($block);
                                    //var_dump($jfolder); var_dump($dest); die();
                                    $this->dir_copy($jfolder, $dest);
                                    $block_post = $this->get_block_post($block);
                                    if (!$block_post) {
                                        $block_post_id = $this->insert_block_post($block, $args);
                                    }
                                }
                            //}
                        }
                        echo '<div class="notice notice-success notice-alt">'.__('Blocks imported!', 'twig-that').'</div>';
                    }
                    // clean tmp
                    $this->dir_delete($tmpdir);
                    unlink($target_file);
                }
            }
            if (!empty($_GET['block'])) {
                $block = $_GET['block'];
                $block_post = $this->get_block_post($block);
                if (!$block_post) {
                    $args = $this->get_json_data($block);
                    $block_post_id = $this->insert_block_post($block, $args);
                }
                echo '<div class="notice notice-success notice-alt">'.__('Block imported!', 'twig-that').'</div>';
            }
            
            break;
        case 'export':
            
            // Make sure our zipping class exists
            if ( ! class_exists( 'ZipArchive' ) ) {
                    die( 'Cannot find class ZipArchive' );
            }

            $zip = new \ZipArchive();

            // Set the system path for our zip file
            $filename = 'blocks_' . date('Y-m-d') . '.zip';
            $filepath = $basedir . $filename;

            // Remove any existing file with that filename
            if ( file_exists( $filepath ) )
                    unlink( $filepath );

            // Create and open the zip file
            if ( ! $zip->open( $filepath, \ZipArchive::CREATE ) ){
                    die( 'Failed to create zip at ' . $filepath );
            }

            // Add any other files by directory
            $blocks = glob( $basedir  . 'blocks' . DIRECTORY_SEPARATOR .'*'. DIRECTORY_SEPARATOR .'*.*');
            //var_dump($blocks); die();
            foreach ( $blocks as $file ) {
                list($tmp, $local) = explode($basedir, $file, 2); 
                //var_dump($local);
                $zip->addFile( $file, $local );
            }

            $zip->close();
            
            $download_url = $dirs['baseurl']. '/'. $filename;
            echo '<div class="notice notice-success notice-alt">'.__('Blocks exported!', 'twig-that').' <a href="'.$download_url.'"><span class="dashicons dashicons-download"></span></a></div>';

            ?>
            <script>
                // Simulate an HTTP redirect:
                setTimeout(()=> {
                    let download = "<?php echo $download_url; ?>";
                    window.location.replace(download);
                }, 1000);
            </script>
            <?php
            # send the file to the browser as a download
            //header('Content-disposition: attachment; filename="'.basename($filepath).'"');
            //header('Content-type: application/zip');
            //readfile($filepath);
            break;
    }
}

?>

<h1>Blocks</h1>

<div class="card">
<h2>IMPORT</h2>
<p>Download some official Block examples: <a target="_blank" href="https://github.com/WordPress/block-development-examples?tab=readme-ov-file#block-development-examples"><span class="dashicons dashicons-download"></span></a></p>
<form action="?post_type=block&page=ttsettings&action=import" method="POST" enctype="multipart/form-data">
    <input type="file" name="zip">
    <button class="btn button" type="submit"><?php _e('Import'); ?></button>
</form>
</div>

<div class="card">
<h2>EXPORT</h2>
<a class="btn button" href="?post_type=block&page=ttsettings&action=export"><?php _e('Export'); ?></a>
</div>

<div class="card">
<h2>SYNC</h2>
<table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" id="icon" class="manage-column column-icon" style="width: 30px;">Icon</th>
                <th scope="col" id="title" class="manage-column column-title column-primary abbr="Titolo"><span>Titolo</span></th>
                <th scope="col" id="folder" class="manage-column column-folder">Folder</th>
                <th scope="col" id="actions" class="manage-column column-actions">Actions</th>
            </tr>
	</thead>

	<tbody id="the-list">
            <?php 
            $blocks = $this->get_blocks();
            foreach ($blocks as $ablock) {
                $block_slug = basename($ablock);
                $block = $this->get_json_data($block_slug);
                $block_post = $this->get_block_post($block_slug);
            ?>
            <tr id="post-<?php echo $block_post ? $block_post->ID : 'xxx'; ?>" class="iedit author-self type-block status-publish hentry">
                <td class="icon column-icon" data-colname="Icon">
                    <?php echo empty($block['icon']) ? '' : '<span class="dashicons dashicons-'.$block['icon'].'"></span> '; ?>
                </td>
                <td class="title column-title has-row-actions column-primary page-title" data-colname="Titolo">
                    <strong>
                    <?php if ($block_post) { ?><a class="row-title" href="<?php echo get_edit_post_link($block_post->ID); ?>" aria-label=""><?php } ?>
                        <?php echo $this->get_block_title($block); ?>
                    <?php if ($block_post) { ?></a><?php } ?>
                    </strong>
                </td>
                <td class="folder column-folder" data-colname="Folder">
                    <?php 
                    $tmp = explode('wp-content', dirname($ablock), 2);
                    echo end($tmp); 
                    ?>
                </td>	
                <td class="actions column-actions" data-colname="Actions">
                    <a class="btn button" href="?post_type=block&page=ttsettings&action=move&block=<?php echo $block_slug?>"><?php _e('Move'); ?></a>
                    <a class="btn button" href="?post_type=block&page=ttsettings&action=download&block=<?php echo $block_slug?>"><?php _e('Download'); ?></a>
                    <a class="btn button" href="?post_type=block&page=ttsettings&action=sync&block=<?php echo $block_slug?>"><?php _e('Sync'); ?></a>
                    <?php if (!$block_post) { ?>
                        <a class="btn button" href="?post_type=block&page=ttsettings&action=import&block=<?php echo $block_slug?>"><?php _e('Import'); ?></a>
                    <?php } ?>
                </td>		
            </tr>
            <?php
            }
            ?>
        </tbody>
</table>
</div>
<hr>
SYNC LOCAL
SYNC CLOUD
REBUILD
MOVE TO (THEME/UPLOADS)
