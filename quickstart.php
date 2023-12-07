<?php
/**
 * Quickstart plugin adds the Quickstart tab for an easy-to-use guide
 * and quick website setup.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/virtuosoft-dev/hcpp-quickstart
 * 
 */

 if ( ! class_exists( 'Quickstart') ) {
    class Quickstart {
        /**
         * Constructor, listen for the render events
         */
        public function __construct() {
            global $hcpp;
            $hcpp->quickstart = $this;
            $hcpp->add_action( 'hcpp_invoke_plugin', [ $this, 'hcpp_invoke_plugin' ] );
            $hcpp->add_action( 'hcpp_render_body', [ $this, 'hcpp_render_body' ] );
            $hcpp->add_action( 'hcpp_render_panel', [ $this, 'hcpp_render_panel' ] );
            $hcpp->add_action( 'hcpp_rebooted', [ $this, 'hcpp_rebooted' ] );
            $hcpp->add_action( 'hcpp_head', [ $this, 'hcpp_head' ] );
        }

        // Clean up the devstia_export_* on reboot
        public function hcpp_rebooted( $args ) {

            // Clean up /tmp/devstia_export_* files
            shell_exec('rm -rf /tmp/devstia_export_*');

            // Cycle through all users and remove /home/user/tmp/devstia_export_* folders
            global $hcpp;
            $users = $hcpp->run( "list-users json" );
            foreach( $users as $user => $details) {
                $command = "find /home/$user/tmp -maxdepth 1 -type d | egrep 'devstia_export_'";
                $folders = array_filter(explode("\n", shell_exec($command)));
                foreach( $folders as $folder ) {
                    $command = "rm -rf $folder";
                    shell_exec($command);
                }
            }

            // Remove from 
            return $args;
        }

        // Run elevated commands from the plugin
        public function hcpp_invoke_plugin( $args ) {
            if ( $args[0] == 'quickstart_export_dbs' ) return $this->quickstart_export_dbs( $args );
            if ( $args[0] == 'quickstart_export_zip' ) return $this->quickstart_export_zip( $args );
            return $args;
        }

        // Highly optimized for speed, scan revelent files for db password and return details as JSON
        public function quickstart_export_dbs( $args ) {
            $user = $args[1];
            $domain = $args[2];

            // Get a list of databases for the given user
            global $hcpp;
            $data = $hcpp->run( "list-databases " . $user . " json" );
            $databases = [];
            foreach ($data as $key => $value) {
                array_push( $databases, $value );
            }

            // Get a list of folders to scan for credentials
            $public_html = $hcpp->run( "list-web-domain " . $user . " '" . $domain . "' json " );
            if ( $public_html == NULL ) return $args;
            $public_html = $public_html[$domain]['DOCUMENT_ROOT'];
            $nodeapp = str_replace( '/public_html/', '/nodeapp/', $public_html );

            // Omit folders, and file extensions for scan
            $omit_folders = array( 'src', 'core', 'includes', 'nodeapp/public', 'build/public', '.git', 'versions', 'node_modules', 'wp-content', 'wp-includes', 'wp-admin', 'vendor' );
            $match_extensions = array( 'php', 'js', 'json', 'conf', 'config', 'jsx', 'ini', 'sh', 'xml', 'inc', 'ts', 'cfg', 'yml', 'yaml', 'py', 'rb', 'env' );

            // Get list of files to check from public_html folder
            $files = [];
            if ( is_dir( $public_html ) ) {

                // First get a list of all subfolders of public_html
                $omit_folders_pattern = implode('|', array_map('preg_quote', $omit_folders));
                $command = "find $public_html -type d | egrep -v '$omit_folders_pattern'";
                $subfolders = array_filter(explode("\n", shell_exec($command)));

                // Given a list of subfolders, get a list of all files that have the match extensions
                $match_extensions_pattern = implode('|', array_map('preg_quote', $match_extensions));
                foreach( $subfolders as $folder) {
                    $command = "find $folder -maxdepth 1 -type f | egrep '$match_extensions_pattern'";    
                    $f = array_filter(explode("\n", shell_exec($command)));
                    $files = array_merge( $files, $f );
                }

            }

            // Get list of files to check from nodeapp folder
            if ( is_dir( $nodeapp ) ) {

                // First get a list of all subfolders of nodeapp
                $omit_folders_pattern = implode('|', array_map('preg_quote', $omit_folders));
                $command = "find $nodeapp -type d | egrep -v '$omit_folders_pattern'";
                $subfolders = array_filter(explode("\n", shell_exec($command)));

                // Given a list of subfolders, get a list of all files that have the match extensions
                $match_extensions_pattern = implode('|', array_map('preg_quote', $match_extensions));
                foreach( $subfolders as $folder) {
                    $command = "find $folder -maxdepth 1 -type f | egrep '$match_extensions_pattern'";    
                    $f = array_filter(explode("\n", shell_exec($command)));
                    $files = array_merge( $files, $f );
                }
            }

            // Check the given files for mentions of database
            foreach( $files as $file ) {
                $content = file_get_contents( $file );
                $index = 0;
                foreach( $databases as $database ) {
                    if ( !isset( $database['REF_FILES'] ) ) $database['REF_FILES'] = [];
                    if ( strpos( $content, $database['DATABASE'] ) !== false ) {
                        $database['REF_FILES'][] = $file;
                    }
                    $databases[$index] = $database;
                    $index++;
                }
            }

            // Analyze dbs that have assoc. files, and extract the password for each database
            $found_dbs = [];
            foreach( $databases as $database ) {
                if ( !isset( $database['REF_FILES'] ) || count( $database['REF_FILES'] ) == 0 ) continue;

                // Search for the password in the first reference file
                $database['DBPASSWORD'] = "";
                foreach( $database['REF_FILES'] as $file) {
                    $content = file_get_contents( $file );
                    $content = $hcpp->delLeftMost( $content, $database['DATABASE'] );
                    $pwkeys = ["PASSWORD'", 'PASSWORD"', 'password"', "password'", 'password =', 'password='];
                    $fkey = "";
                    foreach( $pwkeys as $pwkey ) {
                        $keypos = strpos( $content, $pwkey );
                        if ( $keypos === false ) continue;
                        if ( $fkey == "" ) $fkey = $pwkey;
                        if ( $keypos < strpos( $content, $fkey ) ) $fkey = $pwkey;
                    }
                    if ( $fkey == "" ) continue;
                }
                if ( $fkey != "" ) {
                    $content = $hcpp->delLeftMost( $content, $fkey );
                    
                    // Find the first instance of a single quote or double quote, whichever comes first
                    $singleQuotePos = strpos($content, "'");
                    $doubleQuotePos = strpos($content, '"');
                    if ($singleQuotePos === false && $doubleQuotePos === false) {
                        // No quotes found
                        $qchar = '';
                    } elseif ($singleQuotePos === false) {
                        // Only double quote found
                        $qchar = '"';
                    } elseif ($doubleQuotePos === false) {
                        // Only single quote found
                        $qchar = "'";
                    } else {
                        // Both quotes found, get the first one
                        $qchar = $singleQuotePos < $doubleQuotePos ? "'" : '"';
                    }
                    if ( $qchar == '' ) continue;

                    // Parse out the password enclosed in the quotes $qchar
                    $content = $hcpp->delLeftMost( $content, $qchar );
                    $password = $hcpp->getLeftMost( $content, $qchar );
                    $database['DBPASSWORD'] = $password;
                    $found_dbs[] = $database;
                    continue;
                }
            }

            echo json_encode( $found_dbs, JSON_PRETTY_PRINT );
            return $args;
        }

        // Start the export process
        public function quickstart_export_zip( $args ) {

            // Move the manifest file to the user tmp folder
            global $hcpp;
            $json_file = $args[1];
            $content = file_get_contents( '/tmp/' . $json_file );
            $devstia_manifest = json_decode( $content, true );
            unlink( '/tmp/' . $json_file );
            $user = $devstia_manifest['user'];
            $domain = $devstia_manifest['domain'];
            $export_folder = '/home/' . $user . '/tmp/' . $hcpp->delRightMost( $json_file, '.json' );
            if ( !is_dir( $export_folder ) ) mkdir( $export_folder, true );
            file_put_contents( $export_folder . '/devstia_manifest.json', $content );
            $devstia_databases_folder = $export_folder . '/devstia_databases';

            // Dump databases to user tmp folder
            mkdir( $devstia_databases_folder, true );
            chmod( $devstia_databases_folder, 0751);
            foreach( $devstia_manifest['databases'] as $database ) {
                $db = $database['DATABASE'];
                $hcpp->run( "dump-database $user $db > \"$devstia_databases_folder/$db.sql\"" );
            }

            $public_html = $hcpp->run( "list-web-domain " . $user . " '" . $domain . "' json " );
            if ( $public_html == NULL ) return $args;
            $public_html = $public_html[$domain]['DOCUMENT_ROOT'];
            $nodeapp = str_replace( '/public_html/', '/nodeapp/', $public_html );
            $private = str_replace( '/public_html/', '/private/', $public_html );
            $cgi_bin = str_replace( '/public_html/', '/cgi-bin/', $public_html );
            $document_errors = str_replace( '/public_html/', '/document_errors/', $public_html );

            // Copy website folders to user tmp folder
            $command = "cp -r $public_html $export_folder ;";
            $command .= "cp -r $nodeapp $export_folder ;";
            $command .= "cp -r $private $export_folder ;";
            $command .= "cp -r $cgi_bin $export_folder ;";
            $command .= "cp -r $document_errors $export_folder ;";
            file_put_contents( "/tmp/test.txt", $command );
            shell_exec( $command );

            // Reset ownership, zip up contents, move to exports, and clean up
            $zip_file = "/home/$user/web/exports/" . $domain . $hcpp->getRightMost( $export_folder, 'devstia_export' ) . '.zip';
            $command = "chown -R $user:$user $export_folder && cd $export_folder ";
            $command .= "&& zip -r $export_folder.zip . && cd .. && rm -rf $export_folder ";
            $command .= "&& mkdir -p /home/$user/web/exports ";
            $command .= "&& mv $export_folder.zip $zip_file ";
            $command .= "&& chown -R $user:$user /home/$user/web/exports ";
            shell_exec( $command );
            return $args;
        }
        
        // Redirect to quickstart on login
        public function hcpp_head( $args ) {
            if ( !isset( $_GET['alt'] ) ) return $args;
            $content = $args['content'];
            if ( strpos( $content, 'LOGIN') === false ) return $args;
            $_SESSION['request_uri'] = '/list/web/?quickstart=main';
            return $args;
        }

        // Render the Quickstart body
        public function hcpp_render_body( $args ) {
            if ( !isset( $_GET['quickstart'] ) ) return $args;
            $authorized_pages = [
                'main',
                'import_export',
                'import',
                'export',
                'export_dbs',
                'export_now',
                'create',
                'remove_copy'
            ];

            // Sanitize the quickstart parameter, default to main
            $load = $_GET['quickstart'];
            $load = str_replace(array('/', '\\'), '', $load);
            if (empty($load) || !preg_match('/^[A-Za-z0-9_-]+$/', $load)) {
                $load = 'main';
            } 
            if ( !in_array( $load, $authorized_pages ) ) {
                $load = 'main';
            }

            // Check for valid user session or direct to login
            if(session_status() == PHP_SESSION_NONE){
                session_start();
            }
            if ( !isset( $_SESSION['user'] ) ) {
                header('Location: /login/?alt=1');
                exit;
            }

            // Load the requested page
            global $hcpp;
            ob_start();
            require( __DIR__ . '/pages/' . $load . '.php' );
            $page = ob_get_clean();
            $content = $args['content'];
            $footer = '<footer ' . $hcpp->delLeftMost( $content, '<footer ');
            $page = $hcpp->do_action('hcpp_quickstart_body', $page);
            $args['content'] = $page . $footer;    
            return $args;
        }

        // Render the Quickstart tab
        public function hcpp_render_panel( $args ) {
            $content = $args['content'];
            if ( !str_contains( $content, '<!-- Web tab -->' ) ) return $args;
            if ( str_contains( $content, 'class="top-bar-menu-link" href="/edit/user/?user=admin&' ) ) return $args;
            
            global $hcpp;
            $before = $hcpp->getLeftMost( $content, '<!-- Web tab -->');
            $after = '<!-- Web tab -->' . $hcpp->delLeftMost( $content, '<!-- Web tab -->');
            $active = isset($_GET['quickstart']) ? ' active' : '';
            if ( $active != '' ) {
                $after = str_replace( 'class="main-menu-item-link active"', 'class="main-menu-item-link"', $after);
            }
            $qs_tab = '<!-- Quickstart tab -->
            <li class="main-menu-item">
                <a class="main-menu-item-link' . $active . '" href="/list/web/?quickstart=main" title="Easy-to-use guide">
                    <p class="main-menu-item-label">QUICKSTART<i class="fas fa-flag-checkered"></i></p>
                    <ul class="main-menu-stats">
                        <li> easy-to-use guide</li>
                    </ul>
                </a>
            </li>';

            // Default to quickstart if logo is clicked
            $before = str_replace( '<a href="/" class="top-bar-logo"', '<a href="https://devstia.com/preview" target="_blank" class="top-bar-logo"', $before);
            
            // Customize help link
            $before = str_replace( 'href="https://hestiacp.com/docs/"', 'href="https://devstia.com/preview/support/"', $before );

            $content = $before . $qs_tab . $after;
            $args['content'] = $content;
            return $args;
        }
    }
    new Quickstart();
}


                