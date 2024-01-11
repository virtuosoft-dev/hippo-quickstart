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

        // Clean up the devstia_*
        public function hcpp_rebooted( $args ) {

            // Clean up /tmp/devstia_* files
            shell_exec('rm -rf /tmp/devstia_*');

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
            return $args;
        }

        // Run elevated commands from the plugin
        public function hcpp_invoke_plugin( $args ) {
            if ( $args[0] == 'quickstart_export_details' ) return $this->quickstart_export_details( $args );
            if ( $args[0] == 'quickstart_export_zip' ) return $this->quickstart_export_zip( $args );
            if ( $args[0] == 'quickstart_export_status' ) return $this->quickstart_export_status( $args );
            if ( $args[0] == 'quickstart_export_cancel' ) return $this->quickstart_export_cancel( $args );
            if ( $args[0] == 'quickstart_import_status' ) return $this->quickstart_import_status( $args );
            if ( $args[0] == 'quickstart_import_cancel' ) return $this->quickstart_import_cancel( $args );
            if ( $args[0] == 'quickstart_import_file' ) return $this->quickstart_import_file( $args );
            if ( $args[0] == 'quickstart_import_now' ) return $this->quickstart_import_now( $args );
            if ( $args[0] == 'quickstart_import_result' ) return $this->quickstart_import_result( $args );
            return $args;
        }

        // Return the result of the import process
        public function quickstart_import_result( $args ) {
            $import_pid = $args[1];
            $import_key = $args[2];
            $status = shell_exec('ps -p ' . $import_pid);
            $result_file = "/tmp/devstia_import_$import_key.result";
            if (strpos($status, $import_pid) === false) {
                if ( file_exists( $result_file ) ) {
                    $content = file_get_contents( $result_file );
                    unlink( $result_file );
                    echo $content;
                }else{
                    echo json_encode( [ 'status' => 'error', 'message' => 'Import failed. Please try again.' ] );
                }
            }else{
                if ( file_exists( $result_file ) ) {
                    $content = file_get_contents( $result_file );
                    echo $content;
                }else{
                    echo json_encode( [ 'status' => 'running', 'message' => 'Please wait. Importing website.' ] );
                }
            }
            return $args;
        }
 
        // Now import the given folder
        public function quickstart_import_now( $args ) {
            $import_key = $args[1];
            $request_file = "/tmp/devstia_import_$import_key.json";
            $result_file = "/tmp/devstia_import_$import_key.result";
            if ( !file_exists( $request_file ) ) {
                $result = json_encode( [ 'status' => 'error', 'message' => 'Request file not found.' ] );
                file_put_contents( $result_file, $result );
                chown ( $result_file, 'admin' );
                return $args;
            }
            $content = file_get_contents( $request_file );
            unlink ( $request_file );
            $request = json_decode( $content, true );

            // Allow plugins to modify the request
            global $hcpp;
            $request = $hcpp->do_action( 'quickstart_import_now_request', $request );
            $import_folder = $request['import_folder'];

            // Get the manifest file
            $manifest = $import_folder . '/devstia_manifest.json';
            if ( !file_exists( $manifest ) ) {
                $result = json_encode( [ 'status' => 'error', 'message' => 'Manifest file not found.' ] );
                file_put_contents( $result_file, $result );
                chown ( $result_file, 'admin' );
                return $args;
            }
            
            // Parse the manifest file
            try {
                $content = file_get_contents( $manifest );
                $devstia_manifest = json_decode( $content, true );
                $devstia_manifest = $hcpp->do_action( 'quickstart_import_now_manifest', $devstia_manifest ); // Allow plugin mods
                $orig_user = $devstia_manifest['user'];
                $orig_domain = $devstia_manifest['domain'];
                $orig_aliases = $devstia_manifest['alias'];
                $proxy_ext = $devstia_manifest['proxy_ext'];
            }catch( Exception $e ) {
                $result = json_encode( [ 'status' => 'error', 'message' => 'Error parsing manifest file.' ] );
                file_put_contents( $result_file, $result );
                chown ( $result_file, 'admin' );
                return $args;
            }

            // Create the domain
            $result = json_encode( [ 'status' => 'running', 'message' => "Please wait. Creating domain." ] );
            file_put_contents( $result_file, $result );
            $new_user = $request['user'];
            $new_domain = $request['v_domain'];
            $new_aliases = str_replace( "\r\n", ",", $request['v_aliases'] );
            $details = $hcpp->run('list-user-ips ' . $new_user . ' json');
            $first_ip = null;
            foreach ( $details as $ip => $ip_details ) {
                $first_ip = $ip;
                break;
            }
            $command = "add-web-domain $new_user $new_domain $first_ip no $new_aliases $proxy_ext";
            $result = $hcpp->run( $command );
            if ( $result != '' ) {
                $result = json_encode( [ 'status' => 'error', 'message' => $result ] );
                file_put_contents( $result_file, $result );
                chown ( $result_file, 'admin' );
                return $args;
            }
            
            // Copy the files

            // Get the destination folder
            $command = "list-web-domain $new_user $new_domain json";
            $detail = $hcpp->run( $command );
            $dest_folder = $detail[$new_domain]['DOCUMENT_ROOT'];
            $dest_folder = $hcpp->delRightMost( $dest_folder, 'public_html' );
            
            // Copy all subfolders in the import folder
            $result = json_encode( [ 'status' => 'running', 'message' => "Please wait. Copying files." ] );
            file_put_contents( $result_file, $result );
            chown ( $result_file, 'admin' );
            $folders = array_filter(glob($import_folder . '/*'), 'is_dir');
            $command = "";
            foreach( $folders as $folder ) {
                $subfolder = $hcpp->getRightMost( $folder, '/' );
                $command .= __DIR__ . '/abcopy ' . $folder . '/ ' . $dest_folder . "$subfolder/ ; ";
                $command .= "chown -R $new_user:$new_user " . $dest_folder . "$subfolder/ ; ";
            }
            $command = $hcpp->do_action( 'quickstart_import_copy_files', $command ); // Allow plugin mods
            shell_exec( $command );

            // Create the databases
            $orig_dbs = $devstia_manifest['databases'];
            $devstia_databases_folder = $import_folder . '/devstia_databases';
            if ( is_array( $orig_dbs ) && !empty( $orig_dbs ) ) {
                $result = json_encode( [ 'status' => 'running', 'message' => "Please wait. Creating databases." ] );
                chown ( $result_file, 'admin' );

                // Create
                foreach( $orig_dbs as $db ) {
                    $orig_db = $db['DATABASE'];
                    $orig_type = $db['TYPE'];
                    $orig_charset = $db['CHARSET'];
                    $ref_files = $db['ref_files'];

                    // Generate new credentials
                    $db_name = $hcpp->nodeapp->random_chars(5);
                    $db_password = $hcpp->nodeapp->random_chars(20);
                    $command = "add-database $new_user $db_name $db_name $db_password $orig_type localhost $orig_charset";
                    file_put_contents( "/tmp/database.txt", $command );
                    $result = $hcpp->run( $command );
                    if ( $result != '' ) {
                        $result = json_encode( [ 'status' => 'error', 'message' => $result ] );
                        file_put_contents( $result_file, $result );
                        chown ( $result_file, 'admin' );
                        return $args;
                    }
                }
            }
            
            return $args;
        }

        // Check the status of the import process
        public function quickstart_import_status( $args ) {
            $import_pid = $args[1];
            $import_key = $args[2];
            $status = shell_exec('ps -p ' . $import_pid);
            if (strpos($status, $import_pid) === false) {

                // Import is finished, check for manifest
                $manifest = '/tmp/devstia_import_' . $import_key . '/devstia_manifest.json';
                if ( file_exists( $manifest ) ) {
                    try {
                        $content = file_get_contents( $manifest );
                        $devstia_manifest = json_decode( $content, true );
                    } catch( Exception $e ) {
                        echo json_encode( [ 'status' => 'error', 'message' => 'Error parsing manifest file.' ] );
                    }
                    $message = 'Fill in options.';
                    if ( is_dir('/home/devstia') ) {
                        $message .= ' <i>Devstia Preview should use a <b>.dev.pw</b> TLD.</i>';
                    }
                    echo json_encode( [ 
                        'status' => 'finished', 
                        'message' => $message, 
                        'domain' => $devstia_manifest['domain'], 
                        'alias' => $devstia_manifest['alias']
                    ] );
                }else{
                    echo json_encode( [ 
                        'status' => 'error',
                        'message' => 'Import failed. Please try again.'
                    ] );
                }
            } else {
                // Import is still running
                echo json_encode( [ 'status' => 'running', 'message' => 'Please wait. Decompressing and analyzing files.' ] );
            }
            return $args;
        }

        // Import the website archive
        public function quickstart_import_file( $args ) {
            global $hcpp;
            $import_file = $args[1];
            $import_folder = $hcpp->getLeftMost( $import_file, '.' );
            if ( file_exists( $import_file ) ) {
                $command = 'unzip -o -q ' . $import_file . ' -d ' . $import_folder . ' ';
                $command .= '&& rm -rf ' . $import_file . ' ';
                $command .= '&& chown -R admin:admin ' . $import_folder;
                $command = $hcpp->do_action( 'quickstart_import_file_command', $command );
                shell_exec( $command );
            }
            return $args;
        }

        // Cancel the import process by killing the process, clean up files
        public function quickstart_import_cancel( $args ) {
            $import_pid = $args[1];
            $import_key = $args[2];
            shell_exec('kill -9 ' . $import_pid . ' ; rm -rf /tmp/devstia_import_' . $import_key . '*');
            echo json_encode( [ 'status' => 'cancelled' ] );
            return $args;
        }

        // Cancel the export process by killing the process
        public function quickstart_export_cancel( $args ) {
            $export_pid = $args[1];
            shell_exec('kill -9 ' . $export_pid);
            echo json_encode( [ 'status' => 'cancelled' ] );
            return $args;
        }

        // Check the status of the export process
        public function quickstart_export_status( $args ) {
            $export_pid = $args[1];
            $status = shell_exec('ps -p ' . $export_pid);
            if (strpos($status, $export_pid) === false) {
                // Export is finished
                echo json_encode( [ 'status' => 'finished' ] );
            } else {
                // Export is still running
                echo json_encode( [ 'status' => 'running' ] );
            }
            return $args;
        }
        
        // Highly optimized for speed, scan revelent files for db credentials, and migration
        // details (domain, aliases, user path) and return details as JSON
        public function quickstart_export_details( $args ) {
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
            $web_domain = $hcpp->run( "list-web-domain " . $user . " '" . $domain . "' json " );
            if ( $web_domain == NULL ) return $args;
            $public_html = $web_domain[$domain]['DOCUMENT_ROOT'];
            $nodeapp = str_replace( '/public_html/', '/nodeapp/', $public_html );

            // Gather web domain items for migration; mentions of domain, aliases, or user folder
            $aliases = $web_domain[$domain]['ALIAS'];
            $aliases = explode( ',', $aliases );
            $migrate_strings = [
                "/home/$user", 
                $domain
            ];
            $migrate_ref_files = [];
            if ( !empty( $aliases ) && $aliases[0] != '' ) {
                foreach( $aliases as $alias ) {
                    $migrate_strings[] = $alias;
                }
            }

            // Omit folders, and file extensions for scan
            $omit_folders = array( 'src', 'core', 'includes', 'public', 'current', 'content', 'core', 'uploads', 
                'logs', '.git', '.svn', '.hg', 'versions', 'node_modules', 'wp-content', 'wp-includes',
                'wp-admin', 'vendor', 'mw-config', 'extensions', 'maintenance', 'i18n', 'skins' );
            $match_extensions = array( 'php', 'ts', 'js', 'json', 'conf', 'config', 'jsx', 'ini', 'sh', 'xml', 'inc',
                'cfg', 'yml', 'yaml', 'py', 'rb', 'env' );

            // Get list of files to check from public_html folder
            $files = [];
            if ( is_dir( $public_html ) ) {

                // First get a list of all subfolders of public_html
                $omit_folders = array_map(function($folder) {
                    return '/' . $folder . '/';
                }, $omit_folders);
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
            
            // Check the given files 
            foreach( $files as $file ) {
                if ( strpos( $file, 'devstia_manifest.json' ) !== false ) continue;
                $content = file_get_contents( $file );
                $index = 0;

                // Check for mentions of database, domain, aliases, 
                foreach( $databases as $database ) {
                    if ( !isset( $database['ref_files'] ) ) $database['ref_files'] = [];
                    if ( strpos( $content, $database['DATABASE'] ) !== false ) {
                        $database['ref_files'][] = $file;// $hcpp->delLeftMost( $file, $domain );
                    }
                    $databases[$index] = $database;
                    $index++;
                }

                // Check for any of the migrate strings
                foreach( $migrate_strings as $m ) {
                    if ( !in_array( $file, $migrate_ref_files ) ) {
                        if ( strpos( $content, $m ) !== false ) {
                            $migrate_ref_files[] = $file;
                        }else{
                            // Check escape encoded version too
                            if ( strpos( $content, addcslashes( $m, "\/\n\r\0" ) ) !== false ) {
                                $migrate_ref_files[] = $file;
                            }
                        }   
                    }
                }
            }

            // Analyze dbs that have assoc. files, and extract the password for each database
            $found_dbs = [];
            $db_index = 0;
            foreach( $databases as $database ) {
                if ( !isset( $database['ref_files'] ) || count( $database['ref_files'] ) == 0 ) continue;

                // Search for the password in the first reference file
                $database['DBPASSWORD'] = "";
                $file_index = 0;
                foreach( $database['ref_files'] as $file) {
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

            // Append migrate files to the found_dbs array
            $found_dbs[] = [ 'ref_files' => $migrate_ref_files ];

            // Make ref_files relative paths
            foreach( $found_dbs as $key => $db ) {
                foreach( $db['ref_files'] as $key2 => $file ) {
                    $found_dbs[$key]['ref_files'][$key2] = "." . $hcpp->delLeftMost( $file, $domain );
                }
            }

            // Output the found databases and migrations
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
            $export_options = $devstia_manifest['export_options'];
            $export_adv_options = $devstia_manifest['export_adv_options'];
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

            // Copy website folders to user tmp folder, accounting for export options
            $abcopy = __DIR__ . '/abcopy';
            $exvc = ';';
            if ( strpos($export_options, 'exvc') !== false ) $exvc = ' true;';
            $command = '';
            if ( strpos($export_options, 'public_html') !== false ) $command .= "$abcopy $public_html $export_folder/public_html" . $exvc;
            if ( strpos($export_options, 'nodeapp') !== false ) $command .= "$abcopy $nodeapp $export_folder/nodeapp" . $exvc;
            if ( strpos($export_options, 'private') !== false ) $command .= "$abcopy $private $export_folder/private" . $exvc;
            if ( strpos($export_options, 'cgi_bin') !== false ) $command .= "$abcopy $cgi_bin $export_folder/cgi_bin" . $exvc;
            if ( strpos($export_options, 'document_errors') !== false ) $command .= "$abcopy $document_errors $export_folder/document_errors" . $exvc;

            // Reset ownership, zip up contents, move to exports, and clean up
            $zip_file = "/home/$user/web/exports/" . $devstia_manifest['zip_file'];
            $command .= "chown -R $user:$user $export_folder && cd $export_folder ";
            $command .= "&& zip -r $export_folder.zip . && cd .. && rm -rf $export_folder ";
            $command .= "&& mkdir -p /home/$user/web/exports ";
            $command .= "&& mv $export_folder.zip $zip_file ";
            $command .= "&& chown -R $user:$user /home/$user/web/exports ";
            $command = $hcpp->do_action( 'quickstart_export_zip', $command ); // Allow plugin mods
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
                'import_options',
                'import_export',
                'import_now',
                'import',
                'export',
                'export_view',
                'export_details',
                'export_options',
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
            $page = $hcpp->do_action('quickstart_' . $load, $page);
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
